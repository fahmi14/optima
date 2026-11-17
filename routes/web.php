<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\TimController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MatrikTugasController;

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
