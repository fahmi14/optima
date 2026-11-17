<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Tim;
use App\Models\Organik;
use App\Models\Mitra;
use App\Models\Kegiatan;
use App\Models\MatrikTugas;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tims
        $timSosial = Tim::create([
            'nama' => 'Tim Statistik Sosial',
            'pj' => 'Budi Santoso, S.ST'
        ]);

        $timProduksi = Tim::create([
            'nama' => 'Tim Statistik Produksi',
            'pj' => 'Dewi Rahmawati, M.Si'
        ]);

        $timIpds = Tim::create([
            'nama' => 'Tim IPDS',
            'pj' => 'Ahmad Fauzi, S.Kom'
        ]);

        // 2. Users (Admin, Organik, Mitra)
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@bps.go.id',
            'password' => Hash::make('password'),
            'level' => 'admin',
            'tipe' => 'organik',
        ]);
        Organik::create([
            'user_id' => $admin->id,
            'nama' => 'Administrator Sistem',
            'nip' => '198501012010011001',
            'jabatan' => 'Pranata Komputer Ahli Muda',
            'golongan' => 'III/c',
            'id_tim' => [$timIpds->id],
        ]);

        $organikUser1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@bps.go.id',
            'password' => Hash::make('password'),
            'level' => 'operator',
            'tipe' => 'organik',
        ]);
        Organik::create([
            'user_id' => $organikUser1->id,
            'nama' => 'Budi Santoso, S.ST',
            'nip' => '198802022011011002',
            'jabatan' => 'Statistisi Ahli Pertama',
            'golongan' => 'III/b',
            'id_tim' => [$timSosial->id, $timIpds->id],
        ]);

        $mitraUser1 = User::create([
            'name' => 'Rina Wijaya',
            'email' => 'rina.wijaya@gmail.com',
            'password' => Hash::make('password'),
            'level' => 'operator',
            'tipe' => 'mitra',
        ]);
        Mitra::create([
            'user_id' => $mitraUser1->id,
            'nama' => 'Rina Wijaya',
            'nik' => '3201015502950001',
            'kecamatan' => 'Cibinong',
            'no_hp' => '081234567890',
        ]);

        $mitraUser2 = User::create([
            'name' => 'Slamet Riyadi',
            'email' => 'slamet.riyadi@gmail.com',
            'password' => Hash::make('password'),
            'level' => 'operator',
            'tipe' => 'mitra',
        ]);
        Mitra::create([
            'user_id' => $mitraUser2->id,
            'nama' => 'Slamet Riyadi',
            'nik' => '3201021208920002',
            'kecamatan' => 'Citeureup',
            'no_hp' => '081398765432',
        ]);

        // 3. Wilayah
        $wilayah1 = Wilayah::create([
            'nama' => 'RT 01 RW 02 Kel. Pakansari',
            'kab' => 'Kabupaten Bogor',
            'kd_kab' => '01',
            'kec' => 'Cibinong',
            'kd_kec' => '010',
            'desa' => 'Pakansari',
            'kd_desa' => '001',
            'sls' => 'RT 001 RW 002',
            'kd_sls' => '0001',
            'subsls' => '00',
        ]);

        $wilayah2 = Wilayah::create([
            'nama' => 'RT 03 RW 01 Kel. Tengah',
            'kab' => 'Kabupaten Bogor',
            'kd_kab' => '01',
            'kec' => 'Cibinong',
            'kd_kec' => '010',
            'desa' => 'Tengah',
            'kd_desa' => '002',
            'sls' => 'RT 003 RW 001',
            'kd_sls' => '0002',
            'subsls' => '00',
        ]);

        $wilayah3 = Wilayah::create([
            'nama' => 'RT 02 RW 05 Desa Puspasari',
            'kab' => 'Kabupaten Bogor',
            'kd_kab' => '01',
            'kec' => 'Citeureup',
            'kd_kec' => '020',
            'desa' => 'Puspasari',
            'kd_desa' => '001',
            'sls' => 'RT 002 RW 005',
            'kd_sls' => '0001',
            'subsls' => '00',
        ]);

        // 4. Kegiatan
        $kegiatan1 = Kegiatan::create([
            'nama' => 'Survei Sosial Ekonomi Nasional (SUSENAS) Maret',
            'tgl_mulai' => '2026-03-01',
            'tgl_selesai' => '2026-03-31',
            'id_tim' => $timSosial->id,
            'administrasi' => [
                'honor' => 3500000,
                'jenis_honor' => 'OB (Orang Bulan)',
                'sbml' => 4000000,
                'jml_petugas' => 20,
                'beban' => 10,
            ],
        ]);

        $kegiatan2 = Kegiatan::create([
            'nama' => 'Survei Pertanian Terintegrasi (SITASI)',
            'tgl_mulai' => '2026-05-01',
            'tgl_selesai' => '2026-05-25',
            'id_tim' => $timProduksi->id,
            'administrasi' => [
                'honor' => 2800000,
                'jenis_honor' => 'Dokumen',
                'sbml' => 3000000,
                'jml_petugas' => 15,
                'beban' => 25,
            ],
        ]);

        // 5. Matrik Tugas
        MatrikTugas::create([
            'id_kegiatan' => $kegiatan1->id,
            'id_user' => $mitraUser1->id,
            'role' => ['PCL (Pencacah Lapangan)'],
            'id_wilayah' => $wilayah1->id,
            'tgl' => '2026-03-05',
            'beban' => 10,
        ]);

        MatrikTugas::create([
            'id_kegiatan' => $kegiatan1->id,
            'id_user' => $organikUser1->id,
            'role' => ['PML (Pengawas Lapangan)'],
            'id_wilayah' => $wilayah1->id,
            'tgl' => '2026-03-10',
            'beban' => 10,
        ]);

        MatrikTugas::create([
            'id_kegiatan' => $kegiatan2->id,
            'id_user' => $mitraUser2->id,
            'role' => ['PCL (Pencacah Lapangan)', 'Pemeriksa'],
            'id_wilayah' => $wilayah3->id,
            'tgl' => '2026-05-12',
            'beban' => 25,
        ]);
    }
}
