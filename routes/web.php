<?php

use App\Http\Controllers\CourtController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/sportovi', [SportController::class, 'index'])->name('sports.index');
Route::get('/tereni/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
Route::get('/oprema', [EquipmentController::class, 'index'])->name('equipment.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
    Route::post('/rezervacije', [ReservationController::class, 'store'])->name('reservations.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
