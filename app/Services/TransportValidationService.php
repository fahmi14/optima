<?php

namespace App\Services;

use App\Models\AlokasiTransport;
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

            foreach ($normalizedDates as $tgl) {
                AlokasiTransport::create([
                    'penugasan_id' => $penugasan->id,
                    'periode_kegiatan_id' => $periodeId,
                    'user_id' => $penugasan->user_id,
                    'kecamatan_id' => $kecamatanId ?? $penugasan->kecamatan_id,
                    'tgl_tugas' => $tgl,
                    'nominal' => $nominalPerDay,
                    'status_spj' => 'Draft',
                ]);
            }
        });
    }
}
