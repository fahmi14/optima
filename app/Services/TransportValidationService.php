<?php

namespace App\Services;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Pengaturan;
use App\Models\Penugasan;
use App\Models\PeriodeKegiatan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransportValidationService
{
    /**
     * Validasi Anti-Double SPJ dan Cek Sisa Pagu Periode Kegiatan.
     *
     * @param  array<int, string>  $dates  Daftar tanggal format YYYY-MM-DD
     * @throws InvalidArgumentException
     */
    public function validateAllocation(
        int $userId,
        int $periodeKegiatanId,
        array $dates,
        float $nominalPerDay,
        ?int $ignorePenugasanId = null
    ): void {
        if (empty($dates)) {
            throw new InvalidArgumentException('Minimal satu tanggal tugas transport harus diisi.');
        }

        if ($nominalPerDay <= 0) {
            throw new InvalidArgumentException('Nominal transport per hari harus lebih dari 0.');
        }

        $normalizedDates = array_values(array_unique(array_map(
            fn ($d) => Carbon::parse($d)->format('Y-m-d'),
            $dates
        )));

        // 1. Cek bentrok tanggal (Anti-Double SPJ) lintas semua kegiatan/tim kerja
        $conflictQuery = AlokasiTransport::with(['periodeKegiatan.masterKegiatan'])
            ->where('user_id', $userId)
            ->whereIn('tgl_tugas', $normalizedDates);

        if ($ignorePenugasanId !== null) {
            $conflictQuery->where('penugasan_id', '!=', $ignorePenugasanId);
        }

        $conflicts = $conflictQuery->get();
        if ($conflicts->isNotEmpty()) {
            $user = User::find($userId);
            $userName = $user ? $user->name : "ID {$userId}";
            $first = $conflicts->first();
            $tglStr = $first->tgl_tugas->format('d/m/Y');
            $kegStr = $first->periodeKegiatan?->masterKegiatan?->nama ?? 'kegiatan lain';

            throw new InvalidArgumentException(
                "Double SPJ ditolak: {$userName} sudah memiliki alokasi transport pada tanggal {$tglStr} ({$kegStr})."
            );
        }

        // 2. Cek Sisa Pagu pada Periode Kegiatan
        $periode = PeriodeKegiatan::findOrFail($periodeKegiatanId);
        $existingQuery = AlokasiTransport::where('periode_kegiatan_id', $periodeKegiatanId)
            ->where('status_spj', '!=', 'Ditolak');

        if ($ignorePenugasanId !== null) {
            $existingQuery->where('penugasan_id', '!=', $ignorePenugasanId);
        }

        $terpakai = (float) $existingQuery->sum('nominal');
        $tambahan = $nominalPerDay * count($normalizedDates);
        $pagu = (float) $periode->pagu_transport;

        if (($terpakai + $tambahan) > $pagu) {
            $sisa = max(0, $pagu - $terpakai);
            throw new InvalidArgumentException(
                sprintf(
                    'Melebihi pagu transport periode %s. Sisa pagu: Rp %s, dibutuhkan: Rp %s.',
                    $periode->nama_periode,
                    number_format($sisa, 0, ',', '.'),
                    number_format($tambahan, 0, ',', '.')
                )
            );
        }
    }

    /**
     * Simpan alokasi transport lokal untuk suatu penugasan setelah lolos validasi.
     *
     * @param  array<int, string>  $dates
     */
    public function allocateForPenugasan(
        Penugasan $penugasan,
        array $dates,
        float $nominalPerDay,
        ?int $kecamatanId = null
    ): void {
        $periodeId = $penugasan->subKegiatan->periode_kegiatan_id;
        $normalizedDates = array_values(array_unique(array_map(
            fn ($d) => Carbon::parse($d)->format('Y-m-d'),
            $dates
        )));

        DB::transaction(function () use ($penugasan, $periodeId, $normalizedDates, $nominalPerDay, $kecamatanId) {
            $this->validateAllocation(
                $penugasan->user_id,
                $periodeId,
                $normalizedDates,
                $nominalPerDay
            );

            foreach ($normalizedDates as $idx => $tgl) {
                AlokasiTransport::create([
                    'penugasan_id' => $penugasan->id,
                    'periode_kegiatan_id' => $periodeId,
                    'user_id' => $penugasan->user_id,
                    'kecamatan_id' => $kecamatanId ?? $penugasan->kecamatan_id,
                    'hari_ke' => $idx + 1,
                    'tgl_tugas' => $tgl,
                    'nominal' => $nominalPerDay,
                    'tujuan_terisi' => true,
                    'status_spj' => 'Draft',
                ]);
            }
        });
    }

    /**
     * Alokasi translok fleksibel:
     * - Bisa beberapa hari berdasarkan jumlah_hari (tanpa tanggal/tujuan dulu -> petugas wajib mengisi di Tugas Saya)
     * - Atau berdasarkan tanggal tugas yang sudah ditentukan
     * - Mendukung 1 hari beberapa kecamatan
     */
    public function allocateFlexibleForPenugasan(
        Penugasan $penugasan,
        int $jumlahHari,
        array $dates,
        float $nominalPerDay,
        array $options = []
    ): void {
        $periodeId = $penugasan->subKegiatan->periode_kegiatan_id;
        $jenisPerjalanan = $options['jenis_perjalanan'] ?? 'dalam_kab';
        $asalPerjalanan = $options['asal_perjalanan'] ?? ($jenisPerjalanan === 'luar_kab' ? 'Kabupaten Wonosobo' : 'Kecamatan Wonosobo');
        $tujuanLuarKab = $options['tujuan_luar_kab'] ?? null;
        $ruteKecamatanIds = array_values(array_filter(array_map('intval', (array) ($options['rute_kecamatan_ids'] ?? []))));
        $kecamatanId = $options['kecamatan_id'] ?? (!empty($ruteKecamatanIds) ? end($ruteKecamatanIds) : null);

        $hasDestination = ($jenisPerjalanan === 'dalam_kab' && !empty($ruteKecamatanIds))
            || ($jenisPerjalanan === 'luar_kab' && !empty($tujuanLuarKab));

        $normalizedDates = array_values(array_unique(array_map(
            fn ($d) => Carbon::parse($d)->format('Y-m-d'),
            array_filter($dates)
        )));

        $totalDays = !empty($normalizedDates) ? count($normalizedDates) : max(1, $jumlahHari);

        // Jika nominalPerDay > 0, validasi sisa pagu periode
        if ($nominalPerDay > 0) {
            $periode = PeriodeKegiatan::findOrFail($periodeId);
            $terpakai = (float) AlokasiTransport::where('periode_kegiatan_id', $periodeId)
                ->where('status_spj', '!=', 'Ditolak')
                ->sum('nominal');
            $tambahan = $nominalPerDay * $totalDays;
            $pagu = (float) $periode->pagu_transport;
            if ($pagu > 0 && ($terpakai + $tambahan) > $pagu) {
                $sisa = max(0, $pagu - $terpakai);
                throw new InvalidArgumentException(
                    sprintf(
                        'Melebihi pagu transport periode %s. Sisa pagu: Rp %s, dibutuhkan: Rp %s.',
                        $periode->nama_periode,
                        number_format($sisa, 0, ',', '.'),
                        number_format($tambahan, 0, ',', '.')
                    )
                );
            }
        }

        // Jika ada tanggal spesifik, cek Anti-Double SPJ
        if (!empty($normalizedDates)) {
            $conflicts = AlokasiTransport::with(['periodeKegiatan.masterKegiatan'])
                ->where('user_id', $penugasan->user_id)
                ->whereIn('tgl_tugas', $normalizedDates)
                ->get();

            if ($conflicts->isNotEmpty()) {
                $user = User::find($penugasan->user_id);
                $userName = $user ? $user->name : "ID {$penugasan->user_id}";
                $first = $conflicts->first();
                $tglStr = $first->tgl_tugas->format('d/m/Y');
                $kegStr = $first->periodeKegiatan?->masterKegiatan?->nama ?? 'kegiatan lain';

                throw new InvalidArgumentException(
                    "Double SPJ ditolak: {$userName} sudah memiliki alokasi transport pada tanggal {$tglStr} ({$kegStr})."
                );
            }
        }

        DB::transaction(function () use (
            $penugasan,
            $periodeId,
            $totalDays,
            $normalizedDates,
            $nominalPerDay,
            $kecamatanId,
            $jenisPerjalanan,
            $asalPerjalanan,
            $tujuanLuarKab,
            $ruteKecamatanIds,
            $hasDestination
        ) {
            for ($i = 0; $i < $totalDays; $i++) {
                $tgl = $normalizedDates[$i] ?? null;
                $tujuanTerisi = $hasDestination && ($tgl !== null);

                AlokasiTransport::create([
                    'penugasan_id' => $penugasan->id,
                    'periode_kegiatan_id' => $periodeId,
                    'user_id' => $penugasan->user_id,
                    'kecamatan_id' => $kecamatanId,
                    'hari_ke' => $i + 1,
                    'jenis_perjalanan' => $jenisPerjalanan,
                    'asal_perjalanan' => $asalPerjalanan,
                    'tujuan_luar_kab' => $jenisPerjalanan === 'luar_kab' ? $tujuanLuarKab : null,
                    'rute_kecamatan_ids' => $jenisPerjalanan === 'dalam_kab' ? $ruteKecamatanIds : null,
                    'tujuan_terisi' => $tujuanTerisi,
                    'tgl_tugas' => $tgl,
                    'nominal' => $hasDestination ? $nominalPerDay : 0,
                    'status_spj' => 'Draft',
                ]);
            }
        });
    }

    /**
     * Hitung nominal harian otomatis dari Master Transport (dalam kab max 170rb, luar kab 370rb).
     */
    public function calculateNominalFromMaster(string $jenisPerjalanan, ?int $asalKecId, array $ruteTujuanIds): float
    {
        if ($jenisPerjalanan === 'luar_kab') {
            return (float) Pengaturan::get('transport_luar_kab', 370000);
        }

        $maksDalamKab = (float) Pengaturan::get('maks_transport_dalam_kab', 170000);
        $wonosoboId = Kecamatan::whereRaw('LOWER(nama) = ?', ['wonosobo'])->value('id');
        $prevId = $asalKecId ?: $wonosoboId;

        $sum = 0.0;
        foreach ($ruteTujuanIds as $nextId) {
            $nextId = (int) $nextId;
            if (!$nextId || !$prevId) continue;
            $tarif = (float) MasterTransport::where('kecamatan_asal_id', $prevId)
                ->where('kecamatan_tujuan_id', $nextId)
                ->value('nominal');
            $sum += $tarif;
            $prevId = $nextId;
        }

        return min($sum, $maksDalamKab);
    }
}
