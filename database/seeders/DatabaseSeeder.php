<?php

namespace Database\Seeders;

use App\Models\AlokasiTransport;
use App\Models\Kecamatan;
use App\Models\MasterKegiatan;
use App\Models\Penugasan;
use App\Models\PeriodeKegiatan;
use App\Models\SubKegiatan;
use App\Models\TimKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 6 Tim Kerja BPS Kabupaten Wonosobo
        $timData = [
            ['kode' => 'SOSIAL', 'nama' => 'Tim Statistik Sosial', 'ketua_tim' => 'Rahmat Hidayat, S.ST'],
            ['kode' => 'PRODUKSI', 'nama' => 'Tim Statistik Produksi', 'ketua_tim' => 'Dewi Anggraini, M.Si'],
            ['kode' => 'DISTRIBUSI', 'nama' => 'Tim Statistik Distribusi', 'ketua_tim' => 'Eko Prasetyo, S.E'],
            ['kode' => 'NERWILIS', 'nama' => 'Tim Neraca Wilayah & Analisis Statistik', 'ketua_tim' => 'Siti Aminah, M.Stat'],
            ['kode' => 'IPDS', 'nama' => 'Tim IPDS', 'ketua_tim' => 'Ahmad Fauzi, S.Kom'],
            ['kode' => 'UMUM', 'nama' => 'Subbagian Umum', 'ketua_tim' => 'Tri Wahyuni, S.E'],
        ];

        $tims = [];
        foreach ($timData as $item) {
            $tims[$item['kode']] = TimKerja::create($item);
        }

        // 2. 15 Kecamatan di Kabupaten Wonosobo
        $kecamatansList = [
            'Wadaslintang', 'Kepil', 'Sapuran', 'Kaliwiro', 'Leksono',
            'Sukoharjo', 'Selomerto', 'Kalikajar', 'Kertek', 'Wonosobo',
            'Watumalang', 'Mojotengah', 'Garung', 'Kejajar', 'Kalibawang',
        ];

        $kecamatans = [];
        foreach ($kecamatansList as $idx => $namaKec) {
            $kode = '3307' . str_pad((string) (($idx + 1) * 10), 3, '0', STR_PAD_LEFT);
            $kecamatans[$namaKec] = Kecamatan::create([
                'kode' => $kode,
                'nama' => $namaKec,
            ]);
        }

        // 3. Akun Demo Lengkap (Semua 6 Role)
        // Admin (user: admin / pass: admin)
        User::create([
            'name' => 'admin',
            'username' => 'admin',
            'email' => 'admin@optima.local',
            'password' => Hash::make('admin'),
            'role' => 'admin',
            'tipe' => 'organik',
            'nip' => '198501012010011001',
            'jabatan' => 'Administrator Sistem (IPDS)',
            'tim_kerja_id' => $tims['IPDS']->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'kapasitas_maks' => 100,
        ]);

        // Pimpinan (Kepala BPS)
        User::create([
            'name' => 'Drs. Hendra Gunawan, M.M',
            'username' => 'pimpinan',
            'email' => 'pimpinan@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'pimpinan',
            'tipe' => 'organik',
            'nip' => '197004151994031002',
            'jabatan' => 'Kepala BPS Kabupaten Wonosobo',
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'kapasitas_maks' => 100,
        ]);

        // Ketua Tim Sosial
        User::create([
            'name' => 'Rahmat Hidayat, S.ST',
            'username' => 'katim.sosial',
            'email' => 'katim.sosial@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'katim',
            'tipe' => 'organik',
            'nip' => '198607102009021003',
            'jabatan' => 'Ketua Tim Statistik Sosial',
            'tim_kerja_id' => $tims['SOSIAL']->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'kapasitas_maks' => 100,
        ]);

        // Ketua Tim Produksi
        User::create([
            'name' => 'Dewi Anggraini, M.Si',
            'username' => 'katim.produksi',
            'email' => 'katim.produksi@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'katim',
            'tipe' => 'organik',
            'nip' => '198709222010012004',
            'jabatan' => 'Ketua Tim Statistik Produksi',
            'tim_kerja_id' => $tims['PRODUKSI']->id,
            'kecamatan_id' => $kecamatans['Selomerto']->id,
            'kapasitas_maks' => 100,
        ]);

        // Subbagian Umum / Keuangan
        User::create([
            'name' => 'Tri Wahyuni, S.E',
            'username' => 'keuangan',
            'email' => 'keuangan@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'keuangan',
            'tipe' => 'organik',
            'nip' => '198811052012122001',
            'jabatan' => 'Kepala Subbagian Umum / PPK',
            'tim_kerja_id' => $tims['UMUM']->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'kapasitas_maks' => 100,
        ]);

        // 3 Pegawai Organik
        $organik1 = User::create([
            'name' => 'Budi Santoso, S.Tr.Stat',
            'username' => 'organik1',
            'email' => 'organik1@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'organik',
            'tipe' => 'organik',
            'nip' => '199103032014031001',
            'jabatan' => 'Statistisi Ahli Pertama',
            'no_hp' => '081226001001',
            'tim_kerja_id' => $tims['SOSIAL']->id,
            'kecamatan_id' => $kecamatans['Kertek']->id,
            'kapasitas_maks' => 100,
        ]);

        $organik2 = User::create([
            'name' => 'Nadia Kusuma, S.ST',
            'username' => 'organik2',
            'email' => 'organik2@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'organik',
            'tipe' => 'organik',
            'nip' => '199306182016022001',
            'jabatan' => 'Statistisi Mahir',
            'no_hp' => '081226001002',
            'tim_kerja_id' => $tims['IPDS']->id,
            'kecamatan_id' => $kecamatans['Mojotengah']->id,
            'kapasitas_maks' => 100,
        ]);

        $organik3 = User::create([
            'name' => 'Fajar Nugroho, A.Md',
            'username' => 'organik3',
            'email' => 'organik3@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'organik',
            'tipe' => 'organik',
            'nip' => '199508122019021002',
            'jabatan' => 'Pranata Komputer Terampil',
            'no_hp' => '081226001003',
            'tim_kerja_id' => $tims['PRODUKSI']->id,
            'kecamatan_id' => $kecamatans['Garung']->id,
            'kapasitas_maks' => 100,
        ]);

        // 3 Mitra Statistik
        $mitra1 = User::create([
            'name' => 'Slamet Riyadi',
            'username' => 'mitra1',
            'email' => 'mitra1@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'mitra',
            'tipe' => 'mitra',
            'id_sobat' => '33072601001',
            'no_hp' => '085227110001',
            'tim_kerja_id' => $tims['SOSIAL']->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'kapasitas_maks' => 100,
        ]);

        $mitra2 = User::create([
            'name' => 'Rina Wulandari',
            'username' => 'mitra2',
            'email' => 'mitra2@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'mitra',
            'tipe' => 'mitra',
            'id_sobat' => '33072601002',
            'no_hp' => '085227110002',
            'tim_kerja_id' => $tims['SOSIAL']->id,
            'kecamatan_id' => $kecamatans['Kejajar']->id,
            'kapasitas_maks' => 100,
        ]);

        $mitra3 = User::create([
            'name' => 'Agus Setiawan',
            'username' => 'mitra3',
            'email' => 'mitra3@bps.go.id',
            'password' => Hash::make('password'),
            'role' => 'mitra',
            'tipe' => 'mitra',
            'id_sobat' => '33072601003',
            'no_hp' => '085227110003',
            'tim_kerja_id' => $tims['PRODUKSI']->id,
            'kecamatan_id' => $kecamatans['Sapuran']->id,
            'kapasitas_maks' => 100,
        ]);

        // 4. Master Kegiatan Sakernas Tahun 2026
        $sakernas = MasterKegiatan::create([
            'tim_kerja_id' => $tims['SOSIAL']->id,
            'nama' => 'Sakernas Tahun 2026',
            'tahun' => 2026,
            'deskripsi' => 'Survei Angkatan Kerja Nasional Tahun 2026 Kabupaten Wonosobo.',
        ]);

        // Periode Februari & Periode Agustus
        $periodeFeb = PeriodeKegiatan::create([
            'master_kegiatan_id' => $sakernas->id,
            'nama_periode' => 'Periode Februari',
            'tgl_mulai' => '2026-02-01',
            'tgl_selesai' => '2026-02-28',
            'pagu_transport' => 15000000,
        ]);

        $periodeAgt = PeriodeKegiatan::create([
            'master_kegiatan_id' => $sakernas->id,
            'nama_periode' => 'Periode Agustus',
            'tgl_mulai' => '2026-08-01',
            'tgl_selesai' => '2026-08-31',
            'pagu_transport' => 22000000,
        ]);

        // Sub-Kegiatan Custom untuk Periode Februari
        $tahapanFeb = [
            ['nama' => 'Updating', 'jenis_tahapan' => 'Lapangan', 'deadline_tahapan' => '2026-02-10', 'urutan' => 1],
            ['nama' => 'Pendataan', 'jenis_tahapan' => 'Lapangan', 'deadline_tahapan' => '2026-02-20', 'urutan' => 2],
            ['nama' => 'Pengolahan', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-02-24', 'urutan' => 3],
            ['nama' => 'Pengecekan Anomali', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-02-26', 'urutan' => 4],
            ['nama' => 'Run SQL', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-02-27', 'urutan' => 5],
            ['nama' => 'Publikasi', 'jenis_tahapan' => 'Administrasi', 'deadline_tahapan' => '2026-02-28', 'urutan' => 6],
        ];

        $subsFeb = [];
        foreach ($tahapanFeb as $t) {
            $subsFeb[$t['nama']] = SubKegiatan::create(array_merge($t, [
                'periode_kegiatan_id' => $periodeFeb->id,
            ]));
        }

        // Sub-Kegiatan Custom untuk Periode Agustus
        $tahapanAgt = [
            ['nama' => 'Updating', 'jenis_tahapan' => 'Lapangan', 'deadline_tahapan' => '2026-08-10', 'urutan' => 1],
            ['nama' => 'Pendataan', 'jenis_tahapan' => 'Lapangan', 'deadline_tahapan' => '2026-08-22', 'urutan' => 2],
            ['nama' => 'Pengolahan', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-08-26', 'urutan' => 3],
            ['nama' => 'Pengecekan Anomali', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-08-28', 'urutan' => 4],
            ['nama' => 'Run SQL', 'jenis_tahapan' => 'Teknis', 'deadline_tahapan' => '2026-08-29', 'urutan' => 5],
            ['nama' => 'Publikasi', 'jenis_tahapan' => 'Administrasi', 'deadline_tahapan' => '2026-08-31', 'urutan' => 6],
        ];

        foreach ($tahapanAgt as $t) {
            SubKegiatan::create(array_merge($t, [
                'periode_kegiatan_id' => $periodeAgt->id,
            ]));
        }

        // 5. Penugasan & Alokasi Transport Lokal
        $p1 = Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Updating']->id,
            'user_id' => $mitra1->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'target_volume' => 10,
            'satuan_volume' => 'BS',
            'realisasi_volume' => 10,
            'progres_persen' => 100,
            'bobot_poin' => 3,
            'deadline_tugas' => now()->addDays(2)->format('Y-m-d'),
            'status' => 'Menunggu Verifikasi',
        ]);

        AlokasiTransport::create([
            'penugasan_id' => $p1->id,
            'periode_kegiatan_id' => $periodeFeb->id,
            'user_id' => $mitra1->id,
            'kecamatan_id' => $kecamatans['Wonosobo']->id,
            'tgl_tugas' => now()->format('Y-m-d'),
            'nominal' => 150000,
            'status_spj' => 'Layak',
        ]);

        $p2 = Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Pendataan']->id,
            'user_id' => $mitra2->id,
            'kecamatan_id' => $kecamatans['Kejajar']->id,
            'target_volume' => 25,
            'satuan_volume' => 'Ruta',
            'realisasi_volume' => 15,
            'progres_persen' => 60,
            'bobot_poin' => 3,
            'deadline_tugas' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'Berjalan',
        ]);

        AlokasiTransport::create([
            'penugasan_id' => $p2->id,
            'periode_kegiatan_id' => $periodeFeb->id,
            'user_id' => $mitra2->id,
            'kecamatan_id' => $kecamatans['Kejajar']->id,
            'tgl_tugas' => now()->addDays(1)->format('Y-m-d'),
            'nominal' => 175000,
            'status_spj' => 'Draft',
        ]);

        Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Pengolahan']->id,
            'user_id' => $organik1->id,
            'kecamatan_id' => $kecamatans['Kertek']->id,
            'target_volume' => 20,
            'satuan_volume' => 'Dokumen',
            'realisasi_volume' => 10,
            'progres_persen' => 50,
            'bobot_poin' => 3,
            'deadline_tugas' => now()->addDays(1)->format('Y-m-d'),
            'status' => 'Berjalan',
        ]);

        Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Pengecekan Anomali']->id,
            'user_id' => $organik2->id,
            'kecamatan_id' => $kecamatans['Mojotengah']->id,
            'target_volume' => 18,
            'satuan_volume' => 'BS',
            'realisasi_volume' => 4,
            'progres_persen' => 22,
            'bobot_poin' => 5, // 90 poin -> Overload (merah) untuk demo indikator!
            'deadline_tugas' => now()->subDays(1)->format('Y-m-d'), // Overdue untuk demo KPI!
            'status' => 'Berjalan',
        ]);

        Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Run SQL']->id,
            'user_id' => $organik3->id,
            'kecamatan_id' => $kecamatans['Garung']->id,
            'target_volume' => 12,
            'satuan_volume' => 'Tabel',
            'realisasi_volume' => 12,
            'progres_persen' => 100,
            'bobot_poin' => 3,
            'deadline_tugas' => '2026-02-27',
            'status' => 'Selesai',
        ]);

        Penugasan::create([
            'sub_kegiatan_id' => $subsFeb['Pendataan']->id,
            'user_id' => $mitra3->id,
            'kecamatan_id' => $kecamatans['Sapuran']->id,
            'target_volume' => 15,
            'satuan_volume' => 'Ruta',
            'realisasi_volume' => 0,
            'progres_persen' => 0,
            'bobot_poin' => 3,
            'deadline_tugas' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'Belum Mulai',
        ]);
    }
}
