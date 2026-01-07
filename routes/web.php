<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SdbController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SdbVisitController;
use App\Http\Controllers\SdbLetterController;
use App\Http\Controllers\SdbImportController; // BARU: Import Controller

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Semua logika bisnis aplikasi terpusat di sini.
| Route dikelompokkan berdasarkan fitur dan hak akses.
|
*/

// Redirect root ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// --- DASHBOARD & GENERAL ACCESS ---
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Utama
    Route::get('/dashboard', [SdbController::class, 'index'])->name('dashboard');
    Route::get('/sdb-filtered', [SdbController::class, 'getFilteredData'])->name('sdb.filtered');
    Route::get('/sdb-attention', [SdbController::class, 'getAttentionRequired'])->name('sdb.attention');

    Route::get('/api/notifications', [SdbController::class, 'getNotifications'])->name('api.notifications');

    // Profile Management
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // --- FASE 5: EXPORT DATA (Admin & Super Admin) ---
    // PENTING: Route ini HARUS di atas route dengan wildcard {sdbUnit}
    Route::get('/sdb/export', [SdbImportController::class, 'export'])
        ->name('sdb.export');

    // --- SDB CORE OPERATIONS ---
    // Menggunakan Group Controller untuk menghindari penulisan ulang [SdbController::class]
    Route::controller(SdbController::class)->group(function () {

        // 1. Read Data
        Route::get('/sdb/{sdbUnit}', 'show')->name('sdb.show');
        Route::get('/sdb/{sdbUnit}/history', 'getHistory')->name('sdb.history');

        // 2. Create Physical Unit (Admin)
        Route::post('/sdb', 'store')->name('sdb.store');

        // 3. Sewa Baru (Explicit Action) - [BARU]
        // Digunakan saat mengisi unit yang statusnya kosong
        Route::post('/sdb/{sdbUnit}/rent', 'storeRental')->name('sdb.rent');

        // 4. Perpanjangan (Explicit Action)
        Route::post('/sdb/{id}/extend', 'extendRental')->name('sdb.extend-rental');

        // 5. Koreksi Data (Explicit Action)
        // Hanya untuk edit typo, bukan ganti sewa
        Route::put('/sdb/{sdbUnit}', 'update')->name('sdb.update');

        // 6. Akhiri Sewa / Kosongkan Unit (Explicit Action)
        Route::delete('/sdb/{sdbUnit}', 'destroy')->name('sdb.destroy');
    });

    // --- OPERATIONAL: VISITS ---
    Route::post('/sdb/{sdbUnit}/visit', [SdbVisitController::class, 'store'])->name('sdb.visit.store');

    // --- OPERATIONAL: LETTERS ---
    Route::get('/sdb/{sdbUnit}/print-letter', [SdbLetterController::class, 'print'])
        ->name('sdb.print-letter');
});

// --- SUPER ADMIN ZONE ---
// Area khusus untuk manajemen sistem, user, dan audit log.
Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {

    // 1. User Management (CRUD Staff)
    Route::resource('users', UserController::class);

    // 2. System Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // 3. FASE 5: IMPORT DATA EXCEL (SUPER ADMIN ONLY)
    Route::post('/import/upload', [SdbImportController::class, 'upload'])->name('import.upload');
    Route::post('/import/execute', [SdbImportController::class, 'execute'])->name('import.execute');
    Route::get('/import/cancel', [SdbImportController::class, 'cancel'])->name('import.cancel');
});

require __DIR__ . '/auth.php';
