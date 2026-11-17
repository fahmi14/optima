<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\TimController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MatrikTugasController;

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

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Wilayah
    Route::prefix('wilayah')->name('wilayah.')->group(function () {
        Route::get('/export', [WilayahController::class, 'export'])->name('export');
        Route::post('/import', [WilayahController::class, 'import'])->name('import');
    });
    Route::resource('wilayah', WilayahController::class);

    // Tim
    Route::prefix('tim')->name('tim.')->group(function () {
        Route::get('/export', [TimController::class, 'export'])->name('export');
        Route::post('/import', [TimController::class, 'import'])->name('import');
    });
    Route::resource('tim', TimController::class);

    // Pengguna (User, Organik, Mitra)
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/export', [UserController::class, 'export'])->name('export');
        Route::post('/import', [UserController::class, 'import'])->name('import');
    });
    Route::resource('user', UserController::class);

    // Kegiatan
    Route::prefix('kegiatan')->name('kegiatan.')->group(function () {
        Route::get('/export', [KegiatanController::class, 'export'])->name('export');
        Route::post('/import', [KegiatanController::class, 'import'])->name('import');
    });
    Route::resource('kegiatan', KegiatanController::class);

    // Matrik Tugas
    Route::prefix('matrik')->name('matrik.')->group(function () {
        Route::get('/export', [MatrikTugasController::class, 'export'])->name('export');
        Route::post('/import', [MatrikTugasController::class, 'import'])->name('import');
    });
    Route::resource('matrik', MatrikTugasController::class);
});
