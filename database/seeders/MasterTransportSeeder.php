<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\MasterTransport;
use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class MasterTransportSeeder extends Seeder
{
    public function run(): void
    {
        // Set nilai pengaturan default jika belum ada
        Pengaturan::firstOrCreate(
            ['kunci' => 'maks_transport_dalam_kab'],
            ['label' => 'Batas Maksimal Transport Lokal Dalam Kabupaten per Hari (Rp)', 'nilai' => '170000']
        );
        Pengaturan::firstOrCreate(
            ['kunci' => 'transport_luar_kab'],
            ['label' => 'Tarif Perjalanan Dinas Luar Kabupaten per Hari (Rp)', 'nilai' => '370000']
        );

        $cols = [
            'Wadaslintang', 'Kepil', 'Sapuran', 'Kalibawang', 'Kaliwiro',
            'Leksono', 'Sukoharjo', 'Selomerto', 'Kalikajar', 'Kertek',
            'Wonosobo', 'Watumalang', 'Mojotengah', 'Garung', 'Kejajar',
        ];

        // Matriks Tarif Transport Lokal Kab. Wonosobo 2025 (sesuai PDF)
        $matrix = [
            'Wadaslintang' => [50000, 170000, 170000, 170000, 120000, 170000, 170000, 170000, 170000, 170000, 170000, 170000, 170000, 170000, 170000],
            'Kepil'        => [170000, 50000, 80000, 130000, 170000, 170000, 170000, 160000, 110000, 120000, 170000, 170000, 170000, 170000, 170000],
            'Sapuran'      => [170000, 80000, 50000, 80000, 140000, 150000, 170000, 130000, 70000, 80000, 120000, 170000, 140000, 160000, 170000],
            'Kalibawang'   => [170000, 130000, 80000, 50000, 100000, 120000, 160000, 120000, 110000, 130000, 160000, 170000, 170000, 170000, 170000],
            'Kaliwiro'     => [170000, 170000, 140000, 100000, 50000, 90000, 110000, 110000, 140000, 160000, 140000, 170000, 160000, 170000, 170000],
            'Leksono'      => [170000, 170000, 150000, 120000, 90000, 50000, 70000, 60000, 120000, 110000, 80000, 100000, 110000, 120000, 160000],
            'Sukoharjo'    => [170000, 170000, 170000, 160000, 110000, 70000, 50000, 90000, 160000, 150000, 120000, 170000, 150000, 160000, 170000],
            'Selomerto'    => [170000, 160000, 130000, 120000, 110000, 60000, 90000, 50000, 100000, 90000, 70000, 110000, 90000, 110000, 150000],
            'Kalikajar'    => [170000, 110000, 70000, 110000, 140000, 120000, 160000, 100000, 50000, 60000, 90000, 160000, 110000, 130000, 170000],
            'Kertek'       => [170000, 120000, 80000, 130000, 160000, 110000, 150000, 90000, 60000, 50000, 80000, 140000, 100000, 120000, 160000],
            'Wonosobo'     => [170000, 170000, 120000, 160000, 140000, 80000, 120000, 70000, 90000, 80000, 50000, 120000, 80000, 100000, 130000],
            'Watumalang'   => [170000, 170000, 170000, 170000, 170000, 100000, 170000, 110000, 160000, 140000, 120000, 50000, 100000, 130000, 170000],
            'Mojotengah'   => [170000, 170000, 140000, 170000, 160000, 110000, 150000, 90000, 110000, 100000, 80000, 100000, 50000, 70000, 100000],
            'Garung'       => [170000, 170000, 160000, 170000, 170000, 120000, 160000, 110000, 130000, 120000, 100000, 130000, 70000, 50000, 70000],
            'Kejajar'      => [170000, 170000, 170000, 170000, 170000, 160000, 170000, 150000, 170000, 160000, 130000, 170000, 100000, 70000, 50000],
        ];

        $kecByName = Kecamatan::all()->keyBy(fn ($k) => strtolower(trim($k->nama)));

        foreach ($matrix as $asalName => $values) {
            $asal = $kecByName->get(strtolower($asalName));
            if (!$asal) continue;

            foreach ($values as $idx => $nominal) {
                $tujuanName = $cols[$idx] ?? null;
                $tujuan = $tujuanName ? $kecByName->get(strtolower($tujuanName)) : null;
                if (!$tujuan) continue;

                MasterTransport::updateOrCreate(
                    [
                        'kecamatan_asal_id' => $asal->id,
                        'kecamatan_tujuan_id' => $tujuan->id,
                    ],
                    ['nominal' => $nominal]
                );
            }
        }
    }
}
