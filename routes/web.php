<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/rezervisi-termin', BookingController::class)->name('booking.index');
Route::get('/rezervisi-termin/pregled', [BookingController::class, 'availability'])->name('booking.availability');
Route::get('/o-nama', AboutController::class)->name('about');
Route::get('/sportovi', [SportController::class, 'index'])->name('sports.index');
Route::get('/tereni/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
Route::get('/oprema', [EquipmentController::class, 'index'])->name('equipment.index');
Route::get('/dogadjaji', [EventController::class, 'index'])->name('events.index');
Route::get('/dogadjaji/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/kontakt', ContactMessageController::class)->name('contact.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
    Route::post('/rezervacije', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/rezervacije/{reservation}/otkazi', [ReservationController::class, 'cancel'])->name('reservations.cancel');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
