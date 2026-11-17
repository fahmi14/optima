<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class WorkloadService
{
    /**
     * Hitung beban kerja aktif seorang pegawai/mitra.
     * Beban dihitung dari akumulasi (bobot_poin * target_volume) tugas yang belum Selesai.
     */
    public function calculateUserWorkload(User $user): array
    {
        $activeTasks = $user->relationLoaded('penugasans')
            ? $user->penugasans->where('status', '!=', 'Selesai')
            : $user->penugasans()->where('status', '!=', 'Selesai')->get();

        $totalPoin = (int) $activeTasks->sum(fn ($task) => (int) $task->bobot_poin * (int) $task->target_volume);
        $kapasitas = max(1, (int) ($user->kapasitas_maks ?: 100));
        $persentase = round(($totalPoin / $kapasitas) * 100, 1);

        if ($persentase <= 60) {
            $status = 'Aman';
            $warna = 'hijau';
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $barClass = 'bg-emerald-500';
        } elseif ($persentase <= 85) {
            $status = 'Padat';
            $warna = 'kuning';
            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
            $barClass = 'bg-amber-500';
        } else {
            $status = 'Overload';
            $warna = 'merah';
            $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
            $barClass = 'bg-rose-500';
        }

        return [
            'total_poin' => $totalPoin,
            'kapasitas_maks' => $kapasitas,
            'persentase' => $persentase,
            'bar_persen' => min(100, $persentase),
            'jumlah_tugas_aktif' => $activeTasks->count(),
            'status' => $status,
            'warna' => $warna,
            'badge_class' => $badgeClass,
            'bar_class' => $barClass,
        ];
    }

    /**
     * Ambil peta beban kerja seluruh pegawai/mitra dengan filter opsional.
     */
    public function getWorkloadMap(?int $timKerjaId = null, ?string $tipe = null): Collection
    {
        $query = User::with(['timKerja', 'kecamatan', 'penugasans' => function ($q) {
            $q->where('status', '!=', 'Selesai');
        }])->orderBy('name');

        if ($timKerjaId) {
            $query->where('tim_kerja_id', $timKerjaId);
        }

        if ($tipe && in_array($tipe, ['organik', 'mitra'], true)) {
            $query->where('tipe', $tipe);
        }

        return $query->get()->map(function (User $user) {
            return [
                'user' => $user,
                'workload' => $this->calculateUserWorkload($user),
            ];
        })->sortByDesc(fn ($item) => $item['workload']['persentase'])->values();
    }
}
