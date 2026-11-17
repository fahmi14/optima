# Project OPTIMA - Sistem Manajemen Wilayah, Kegiatan & Matrik Tugas

Project Laravel ini dibuat berdasarkan spesifikasi alur desain pada diagram entitas:

## 1. Entitas & Struktur Data Sesuai Diagram

1. **Wilayah (CRUD + Import/Export Excel)**:
   - `id`, `nama`, `kab`, `kd_kab`, `kec`, `kd_kec`, `desa`, `kd_desa`, `sls`, `kd_sls`, `subsls`.
2. **Tim (CRUD + Import/Export Excel)**:
   - `id`, `nama`, `pj` (Penanggung Jawab).
3. **Pengguna / User (CRUD + Import/Export Excel)**:
   - `id`, `name`, `email`, `password`
   - `level`: `admin`, `operator`
   - `tipe`: `organik`, `mitra`
   - **Profil Organik**: `nip`, `jabatan`, `golongan`, `id_tim` (JSON array, bisa lebih dari 1 tim)
   - **Profil Mitra**: `nik`, `kecamatan`, `no_hp`
4. **Kegiatan (CRUD + Import/Export Excel)**:
   - `id`, `nama`, `tgl_mulai`, `tgl_selesai`, `id_tim`
   - `administrasi` (JSON): `honor`, `jenis_honor`, `sbml`, `jml_petugas`, `beban`, serta dinamis custom fields.
5. **Matrik Tugas (CRUD + Import/Export Excel)**:
   - `id`, `id_kegiatan`, `id_user`, `role` (JSON, seperti PCL, PML, Koseka, dll), `id_wilayah`, `tgl`, `beban`.

---

## 2. Fitur Utama

- **CRUD Lengkap**: Tambah, Lihat, Cari/Filter, Edit, dan Hapus pada seluruh master data.
- **Import & Export Excel (.xlsx)**:
  - Menggunakan library `phpoffice/phpspreadsheet`.
  - Export data ke file Excel siap cetak/analisis.
  - Import file Excel dengan mapping otomatis ke database.
- **Tampilan Modern & Responsif**:
  - Dibuat dengan Tailwind CSS & FontAwesome.
  - Form dinamis untuk atribut JSON (Role penugasan, custom administrasi kegiatan, relasi tim organik).
  - Dashboard statistik ringkasan penugasan & kegiatan.

---

## 3. Menjalankan Aplikasi di Laragon

### Opsi A: Lewat Laragon GUI
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All** (Apache / Nginx & MySQL).
3. Folder project berada di: `C:\laragon\www\optima\optima-app`.
4. Anda dapat mengaksesnya via browser di `http://optima-app.test` (atau via virtual host Laragon).

### Opsi B: Lewat Artisan Serve (Terminal)
Masuk ke direktori `optima-app`:
```bash
cd C:\laragon\www\optima\optima-app
php artisan serve
```
Akses di browser: `http://127.0.0.1:8000`

---

## 4. Akun Default (Seeder)

Database telah diisi data awal (*dummy data*) yang siap pakai:
- **Email Admin**: `admin@bps.go.id`
- **Password**: `password`
- **Level**: `admin` | **Tipe**: `organik`
