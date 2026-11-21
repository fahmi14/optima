<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\MasterWilayahController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MasterTransportController;
use App\Http\Controllers\MatriksAlokasiController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenugasanController;
use App\Http\Controllers\PenugasanWizardController;
use App\Http\Controllers\TimKerjaController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\TugasSayaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest / Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard Utama / Eksekutif
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/beban-organik-data', [DashboardController::class, 'bebanOrganikData'])->name('dashboard.beban_organik_data');
    Route::get('/dashboard/beban-mitra-data', [DashboardController::class, 'bebanMitraData'])->name('dashboard.beban_mitra_data');
    Route::get('/dashboard/alokasi-honor-data', [DashboardController::class, 'alokasiHonorData'])->name('dashboard.alokasi_honor_data');

    // Kalender Pekerjaan
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender.index');

    // Autocomplete API Wilayah Tugas (Kecamatan, Desa, SLS, Sub-SLS, NKS)
    Route::get('/api/wilayah/search', [MasterWilayahController::class, 'search'])->name('api.wilayah.search');

    // Chat (General & Privat)
    Route::get('/chat/users', [ChatController::class, 'users'])->name('chat.users');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');

    // Tugas Saya (Pegawai Organik & Mitra Statistik, juga tersedia untuk semua user)
    Route::get('/tugas-saya', [TugasSayaController::class, 'index'])->name('tugas-saya.index');
    Route::post('/tugas-saya/{penugasan}/progress', [TugasSayaController::class, 'updateProgress'])->name('tugas-saya.progress');
    Route::post('/tugas-saya/translok/{alokasi}', [TugasSayaController::class, 'updateTranslok'])->name('tugas-saya.translok.update');

    // Master Data (Khusus Admin IPDS)
    Route::middleware('role:admin')->group(function () {
        // Redirect /user lama ke /pegawai
        Route::redirect('/user', '/pegawai');

        // Master Data Pegawai Organik
        Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
        Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
        Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->name('pegawai.update');
        Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy');
        Route::get('/pegawai/template', [PegawaiController::class, 'downloadTemplate'])->name('pegawai.template');
        Route::post('/pegawai/import', [PegawaiController::class, 'import'])->name('pegawai.import');

        // Master Data Mitra Statistik (KEPKA Tahunan)
        Route::get('/mitra', [MitraController::class, 'index'])->name('mitra.index');
        Route::post('/mitra', [MitraController::class, 'store'])->name('mitra.store');
        Route::put('/mitra/{mitra}', [MitraController::class, 'update'])->name('mitra.update');
        Route::delete('/mitra/{mitra}', [MitraController::class, 'destroy'])->name('mitra.destroy');
        Route::get('/mitra/template', [MitraController::class, 'downloadTemplate'])->name('mitra.template');
        Route::post('/mitra/import', [MitraController::class, 'import'])->name('mitra.import');

        // Master Tim Kerja
        Route::get('/master/tim', [TimKerjaController::class, 'index'])->name('tim.index');
        Route::post('/master/tim', [TimKerjaController::class, 'store'])->name('tim.store');
        Route::put('/master/tim/{tim}', [TimKerjaController::class, 'update'])->name('tim.update');
        Route::delete('/master/tim/{tim}', [TimKerjaController::class, 'destroy'])->name('tim.destroy');

        // Master Wilayah (5 Level: Kecamatan, Desa, SLS, Sub-SLS, NKS)
        Route::get('/master/wilayah', [MasterWilayahController::class, 'index'])->name('master.wilayah.index');
        Route::post('/master/wilayah', [MasterWilayahController::class, 'store'])->name('master.wilayah.store');
        Route::put('/master/wilayah/{id}', [MasterWilayahController::class, 'update'])->name('master.wilayah.update');
        Route::delete('/master/wilayah/{id}', [MasterWilayahController::class, 'destroy'])->name('master.wilayah.destroy');
        Route::get('/master/wilayah/template/{tab}', [MasterWilayahController::class, 'downloadTemplate'])->name('master.wilayah.template');
        Route::post('/master/wilayah/import', [MasterWilayahController::class, 'import'])->name('master.wilayah.import');

        // Master Kecamatan (Legacy redirect to Master Wilayah)
        Route::get('/master/kecamatan', fn() => redirect()->route('master.wilayah.index'))->name('kecamatan.index');

        // Pengaturan SBML & Jam Kerja
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

        // Master Transport (Tarif Per Kecamatan, Luar Kab & Batas Maksimal)
        Route::get('/master/transport', [MasterTransportController::class, 'index'])->name('master.transport.index');
        Route::post('/master/transport', [MasterTransportController::class, 'store'])->name('master.transport.store');
        Route::put('/master/transport/{masterTransport}', [MasterTransportController::class, 'update'])->name('master.transport.update');
        Route::delete('/master/transport/{masterTransport}', [MasterTransportController::class, 'destroy'])->name('master.transport.destroy');
        Route::put('/master/transport-settings', [MasterTransportController::class, 'updateSettings'])->name('master.transport.settings');
        Route::get('/master/transport/template', [MasterTransportController::class, 'downloadTemplate'])->name('master.transport.template');
        Route::post('/master/transport/import', [MasterTransportController::class, 'import'])->name('master.transport.import');
    });

    // Kegiatan & Penugasan (Admin & Ketua Tim)
    Route::middleware('role:admin,katim')->group(function () {
        Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
        Route::post('/kegiatan/master', [KegiatanController::class, 'storeMaster'])->name('kegiatan.master.store');
        Route::put('/kegiatan/master/{master}', [KegiatanController::class, 'updateMaster'])->name('kegiatan.master.update');
        Route::delete('/kegiatan/master/{master}', [KegiatanController::class, 'destroyMaster'])->name('kegiatan.master.destroy');
        Route::get('/kegiatan/template', [KegiatanController::class, 'downloadTemplate'])->name('kegiatan.template');
        Route::post('/kegiatan/import', [KegiatanController::class, 'import'])->name('kegiatan.import');

        Route::post('/kegiatan/{master}/periode', [KegiatanController::class, 'storePeriode'])->name('kegiatan.periode.store');
        Route::get('/periode/{periode}', [KegiatanController::class, 'showPeriode'])->name('kegiatan.periode.show');
        Route::put('/periode/{periode}', [KegiatanController::class, 'updatePeriode'])->name('kegiatan.periode.update');
        Route::delete('/periode/{periode}', [KegiatanController::class, 'destroyPeriode'])->name('kegiatan.periode.destroy');

        Route::post('/periode/{periode}/sub', [KegiatanController::class, 'storeSubKegiatan'])->name('kegiatan.sub.store');
        Route::put('/sub/{sub}', [KegiatanController::class, 'updateSubKegiatan'])->name('kegiatan.sub.update');
        Route::delete('/sub/{sub}', [KegiatanController::class, 'destroySubKegiatan'])->name('kegiatan.sub.destroy');
        Route::post('/periode/{periode}/clone-sub', [KegiatanController::class, 'cloneSubKegiatan'])->name('kegiatan.sub.clone');

        // Distribusi Penugasan & Import Massal
        Route::get('/sub/{sub}/wizard', [PenugasanWizardController::class, 'create'])->name('penugasan.wizard');
        Route::get('/sub/{sub}/wizard/petugas-data', [PenugasanWizardController::class, 'petugasData'])->name('penugasan.wizard.petugas_data');
        Route::post('/sub/{sub}/wizard/petugas-details', [PenugasanWizardController::class, 'selectedPetugasDetails'])->name('penugasan.wizard.petugas_details');
        Route::post('/sub/{sub}/wizard/batch', [PenugasanWizardController::class, 'storeBatch'])->name('penugasan.wizard.store');
        Route::get('/sub/{sub}/wizard/template', [PenugasanWizardController::class, 'downloadTemplate'])->name('penugasan.wizard.template');
        Route::post('/sub/{sub}/penugasan', [PenugasanController::class, 'store'])->name('penugasan.store');
        Route::put('/penugasan/{penugasan}', [PenugasanController::class, 'update'])->name('penugasan.update');
        Route::post('/penugasan/{penugasan}/verify', [PenugasanController::class, 'verify'])->name('penugasan.verify');
        Route::delete('/penugasan/{penugasan}', [PenugasanController::class, 'destroy'])->name('penugasan.destroy');
        Route::get('/penugasan/template', [PenugasanController::class, 'downloadTemplate'])->name('penugasan.template');
        Route::post('/periode/{periode}/penugasan/import', [PenugasanController::class, 'import'])->name('penugasan.import');
    });

    // Rekap Transportasi Lokal & SPJ (Admin, Katim, Keuangan)
    Route::middleware('role:admin,katim,keuangan')->group(function () {
        Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
        Route::get('/transport/export', [TransportController::class, 'export'])->name('transport.export');
    });

    // Matriks Alokasi Petugas (Admin, Katim, Keuangan, Pimpinan)
    Route::middleware('role:admin,katim,keuangan,pimpinan')->group(function () {
        Route::get('/matriks-alokasi', [MatriksAlokasiController::class, 'index'])->name('matriks.alokasi.index');
    });

    Route::middleware('role:admin,keuangan')->group(function () {
        Route::post('/transport/{alokasi}/status', [TransportController::class, 'updateStatus'])->name('transport.status');
    });
});
