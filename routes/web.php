<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SdbController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [SdbController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // SDB Routes
    Route::get('/sdb/{sdbUnit}', [SdbController::class, 'show'])->name('sdb.show');
    Route::post('/sdb', [SdbController::class, 'store'])->name('sdb.store');

    // BARU: Tambahkan kembali route untuk UPDATE (PUT) dan DELETE
    Route::put('/sdb/{sdbUnit}', [SdbController::class, 'update'])->name('sdb.update');
    Route::delete('/sdb/{sdbUnit}', [SdbController::class, 'destroy'])->name('sdb.destroy');

    // HAPUS: Route ini sudah tidak relevan dan bisa dihapus
    // Route::post('/generate-grid', [SdbController::class, 'generateGrid'])->name('generate.grid');

    Route::get('/sdb-filtered', [SdbController::class, 'getFilteredData'])->name('sdb.filtered');
    Route::post('/sdb/{sdbUnit}/extend-rental', [SdbController::class, 'extendRental'])->name('sdb.extend-rental');
});

require __DIR__ . '/auth.php';
