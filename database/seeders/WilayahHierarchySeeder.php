<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Nks;
use App\Models\Sls;
use App\Models\SubSls;
use Illuminate\Database\Seeder;

class WilayahHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // Seeding contoh desa, sls, subsls, nks untuk Wonosobo, Kertek, Kepil, Mojotengah, Garung
        $samples = [
            'Wonosobo' => [
                'Wonolelo' => ['RT 001 RW 001', 'RT 002 RW 001', 'RT 001 RW 002'],
                'Jaraksari' => ['RT 001 RW 003', 'RT 002 RW 003', 'RT 003 RW 004'],
                'Pekuncen' => ['RT 001 RW 001', 'RT 002 RW 001'],
            ],
            'Kertek' => [
                'Bijiarum' => ['RT 001 RW 001', 'RT 001 RW 003', 'RT 002 RW 003'],
                'Recobanteng' => ['RT 001 RW 002', 'RT 002 RW 002'],
                'Sudungdewo' => ['RT 003 RW 001', 'RT 004 RW 001'],
            ],
            'Kepil' => [
                'Beran' => ['RT 001 RW 003', 'RT 002 RW 003', 'RT 003 RW 003'],
                'Gadingrejo' => ['RT 001 RW 001', 'RT 002 RW 001'],
            ],
            'Mojotengah' => [
                'Kalibeber' => ['RT 001 RW 002', 'RT 002 RW 002', 'RT 003 RW 002'],
                'Krasak' => ['RT 001 RW 001', 'RT 002 RW 001'],
            ],
            'Garung' => [
                'Siwuran' => ['RT 001 RW 001', 'RT 002 RW 001'],
                'Sendangsari' => ['RT 001 RW 002', 'RT 002 RW 002'],
            ],
        ];

        $nksCounter = 1;

        foreach ($samples as $namaKec => $desas) {
            $kec = Kecamatan::where('nama', $namaKec)->first();
            if (!$kec) continue;

            $dIndex = 1;
            foreach ($desas as $namaDesa => $slsList) {
                $kdDesa = $kec->kode . str_pad((string) $dIndex, 3, '0', STR_PAD_LEFT);
                $desa = Desa::firstOrCreate(
                    ['id_kec' => $kec->id, 'desa' => $namaDesa],
                    ['kd_desa' => $kdDesa]
                );
                $dIndex++;

                $sIndex = 1;
                foreach ($slsList as $namaSls) {
                    $idSls = str_pad((string) $sIndex, 4, '0', STR_PAD_LEFT);
                    $sls = Sls::firstOrCreate(
                        ['id_kec' => $kec->id, 'id_desa' => $desa->id, 'sls' => $namaSls],
                        ['id_sls' => $idSls]
                    );

                    // Buatkan Sub-SLS contoh
                    SubSls::firstOrCreate(
                        ['id_kec' => $kec->id, 'id_desa' => $desa->id, 'id_sls' => $idSls, 'subsls' => "{$namaSls} - Bagian 1"],
                        ['sls_id' => $sls->id]
                    );

                    // Buatkan NKS contoh
                    $kodeNks = '3307' . str_pad((string) $nksCounter, 6, '0', STR_PAD_LEFT);
                    Nks::firstOrCreate(
                        ['id_kec' => $kec->id, 'id_desa' => $desa->id, 'nks' => $kodeNks],
                        ['id_sls' => $idSls, 'sls' => $namaSls]
                    );
                    $nksCounter++;
                    $sIndex++;
                }
            }
        }
    }
}
