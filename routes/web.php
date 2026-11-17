<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\PenugasanController;
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

    // Kalender Pekerjaan
    Route::get('/kalender', [KalenderController::class, 'index'])->name('kalender.index');

    // Chat (General & Privat)
    Route::get('/chat/users', [ChatController::class, 'users'])->name('chat.users');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');

    // Tugas Saya (Pegawai Organik & Mitra Statistik, juga tersedia untuk semua user)
    Route::get('/tugas-saya', [TugasSayaController::class, 'index'])->name('tugas-saya.index');
    Route::post('/tugas-saya/{penugasan}/progress', [TugasSayaController::class, 'updateProgress'])->name('tugas-saya.progress');

    // Master Data (Khusus Admin IPDS)
    Route::middleware('role:admin')->group(function () {
        // Master Pengguna (Organik & Mitra)
        Route::get('/user', [UserController::class, 'index'])->name('user.index');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::get('/user/template/organik', [UserController::class, 'downloadTemplateOrganik'])->name('user.template.organik');
        Route::get('/user/template/mitra', [UserController::class, 'downloadTemplateMitra'])->name('user.template.mitra');
        Route::post('/user/import/organik', [UserController::class, 'importOrganik'])->name('user.import.organik');
        Route::post('/user/import/mitra', [UserController::class, 'importMitra'])->name('user.import.mitra');

        // Master Tim Kerja
        Route::get('/master/tim', [TimKerjaController::class, 'index'])->name('tim.index');
        Route::post('/master/tim', [TimKerjaController::class, 'store'])->name('tim.store');
        Route::put('/master/tim/{tim}', [TimKerjaController::class, 'update'])->name('tim.update');
        Route::delete('/master/tim/{tim}', [TimKerjaController::class, 'destroy'])->name('tim.destroy');

        // Master Kecamatan
        Route::get('/master/kecamatan', [KecamatanController::class, 'index'])->name('kecamatan.index');
        Route::post('/master/kecamatan', [KecamatanController::class, 'store'])->name('kecamatan.store');
        Route::put('/master/kecamatan/{kecamatan}', [KecamatanController::class, 'update'])->name('kecamatan.update');
        Route::delete('/master/kecamatan/{kecamatan}', [KecamatanController::class, 'destroy'])->name('kecamatan.destroy');
    });

    // Kegiatan & Penugasan (Admin & Ketua Tim)
    Route::middleware('role:admin,katim')->group(function () {
        Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
        Route::post('/kegiatan/master', [KegiatanController::class, 'storeMaster'])->name('kegiatan.master.store');
        Route::delete('/kegiatan/master/{master}', [KegiatanController::class, 'destroyMaster'])->name('kegiatan.master.destroy');
        Route::get('/kegiatan/template', [KegiatanController::class, 'downloadTemplate'])->name('kegiatan.template');
        Route::post('/kegiatan/import', [KegiatanController::class, 'import'])->name('kegiatan.import');

        Route::post('/kegiatan/{master}/periode', [KegiatanController::class, 'storePeriode'])->name('kegiatan.periode.store');
        Route::get('/periode/{periode}', [KegiatanController::class, 'showPeriode'])->name('kegiatan.periode.show');
        Route::delete('/periode/{periode}', [KegiatanController::class, 'destroyPeriode'])->name('kegiatan.periode.destroy');

        Route::post('/periode/{periode}/sub', [KegiatanController::class, 'storeSubKegiatan'])->name('kegiatan.sub.store');
        Route::put('/sub/{sub}', [KegiatanController::class, 'updateSubKegiatan'])->name('kegiatan.sub.update');
        Route::delete('/sub/{sub}', [KegiatanController::class, 'destroySubKegiatan'])->name('kegiatan.sub.destroy');
        Route::post('/periode/{periode}/clone-sub', [KegiatanController::class, 'cloneSubKegiatan'])->name('kegiatan.sub.clone');

        // Distribusi Penugasan & Import Massal
        Route::post('/sub/{sub}/penugasan', [PenugasanController::class, 'store'])->name('penugasan.store');
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

    Route::middleware('role:admin,keuangan')->group(function () {
        Route::post('/transport/{alokasi}/status', [TransportController::class, 'updateStatus'])->name('transport.status');
    });
});
