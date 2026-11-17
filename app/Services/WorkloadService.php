<?php

namespace App\Services;

use App\Models\Pengaturan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkloadService
{
    /**
     * Hitung jumlah hari kerja (Senin - Jumat) dalam satu bulan kalender.
     */
    public function getHariKerjaBulan(?Carbon $date = null): int
    {
        $date = $date ? $date->copy() : now();
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $hariKerja = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend()) {
                $hariKerja++;
            }
            $cursor->addDay();
        }

        return max(1, $hariKerja);
    }

    /**
     * Hitung total kuota jam kerja per bulan (8 jam x jumlah hari kerja per bulan).
     */
    public function getTotalJamKerjaBulan(?Carbon $date = null): float
    {
        $hariKerja = $this->getHariKerjaBulan($date);
        $jamPerHari = (float) Pengaturan::get('jam_kerja_per_hari', 8);

        return round($hariKerja * $jamPerHari, 1);
    }

    /**
     * Ambil batas SBML per bulan untuk mitra (dapat diupdate admin).
     */
    public function getSbmlMitra(): float
    {
        return (float) Pengaturan::get('sbml_mitra_per_bulan', 4000000);
    }

    /**
     * Hitung beban kerja aktif seorang pegawai/mitra per bulan.
     * Menggunakan akumulasi jam kerja dan indikator SBML / Jam Kerja.
     */
    public function calculateUserWorkload(User $user, ?Carbon $month = null): array
    {
        $month = $month ? $month->copy() : now();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $allTasks = $user->relationLoaded('penugasans')
            ? $user->penugasans
            : $user->penugasans()->with('subKegiatan')->get();

        // Filter tugas yang jatuh pada bulan ini (atau tugas aktif)
        $monthTasks = $allTasks->filter(function ($task) use ($startOfMonth, $endOfMonth) {
            $deadline = $task->deadline_tugas ? Carbon::parse($task->deadline_tugas) : null;
            if ($deadline) {
                return $deadline->between($startOfMonth, $endOfMonth);
            }
            return $task->status !== 'Selesai';
        });

        $totalJam = (float) $monthTasks->sum(fn ($t) => $t->durasiJam());
        $totalHonor = (float) $monthTasks->sum(function ($t) {
            if ($t->honor_total && (float) $t->honor_total > 0) {
                return (float) $t->honor_total;
            }
            $satuanHonor = (float) ($t->honor_satuan ?: ($t->subKegiatan?->honor_per_satuan ?? 0));
            return $satuanHonor * (int) $t->target_volume;
        });

        $hariKerja = $this->getHariKerjaBulan($month);
        $kuotaJam = $this->getTotalJamKerjaBulan($month);
        $sbml = $this->getSbmlMitra();

        $persentaseJam = $kuotaJam > 0 ? round(($totalJam / $kuotaJam) * 100, 1) : 0;
        $persentaseHonor = $sbml > 0 ? round(($totalHonor / $sbml) * 100, 1) : 0;

        $isJamAman = $totalJam <= $kuotaJam;
        $isHonorAman = ($user->tipe === 'mitra') ? ($totalHonor <= $sbml) : true;

        if ($user->tipe === 'mitra') {
            if (!$isJamAman || !$isHonorAman) {
                $status = 'Overload';
                $warna = 'merah';
                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                $barClass = 'bg-rose-500';
            } elseif ($persentaseJam > 80 || $persentaseHonor > 80) {
                $status = 'Mendekati Batas';
                $warna = 'kuning';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $barClass = 'bg-amber-500';
            } else {
                $status = 'Aman';
                $warna = 'hijau';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $barClass = 'bg-emerald-500';
            }
        } else {
            // Organik: indikator utama jam kerja
            if (!$isJamAman) {
                $status = 'Overload';
                $warna = 'merah';
                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                $barClass = 'bg-rose-500';
            } elseif ($persentaseJam > 80) {
                $status = 'Padat';
                $warna = 'kuning';
                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                $barClass = 'bg-amber-500';
            } else {
                $status = 'Aman';
                $warna = 'hijau';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $barClass = 'bg-emerald-500';
            }
        }

        return [
            'total_poin' => (int) round($totalJam), // kompatibilitas
            'total_jam' => $totalJam,
            'kuota_jam' => $kuotaJam,
            'hari_kerja' => $hariKerja,
            'persentase_jam' => $persentaseJam,
            'is_jam_aman' => $isJamAman,

            'total_honor' => $totalHonor,
            'sbml' => $sbml,
            'persentase_honor' => $persentaseHonor,
            'is_honor_aman' => $isHonorAman,

            'kapasitas_maks' => (int) $kuotaJam,
            'persentase' => $persentaseJam,
            'bar_persen' => min(100, $persentaseJam),
            'jumlah_tugas_aktif' => $monthTasks->where('status', '!=', 'Selesai')->count(),
            'status' => $status,
            'warna' => $warna,
            'badge_class' => $badgeClass,
            'bar_class' => $barClass,
        ];
    }

    /**
     * Ambil peta beban kerja seluruh pegawai/mitra dengan filter opsional.
     */
    public function getWorkloadMap(?int $timKerjaId = null, ?string $tipe = null, ?Carbon $month = null): Collection
    {
        $query = User::with(['timKerja', 'kecamatan', 'penugasans.subKegiatan'])->orderBy('name');

        if ($timKerjaId) {
            $query->where('tim_kerja_id', $timKerjaId);
        }

        if ($tipe && in_array($tipe, ['organik', 'mitra'], true)) {
            $query->where('tipe', $tipe);
        }

        return $query->get()->map(function (User $user) use ($month) {
            return [
                'user' => $user,
                'workload' => $this->calculateUserWorkload($user, $month),
            ];
        })->sortByDesc(fn ($item) => $item['workload']['persentase'])->values();
    }
}
