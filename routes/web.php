<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');
Route::get('/uploads/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('public-storage.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/rezervisi-termin', BookingController::class)->name('booking.index');
Route::get('/rezervisi-termin/pregled', [BookingController::class, 'availability'])
    ->middleware('throttle:120,1')
    ->name('booking.availability');
Route::get('/o-nama', AboutController::class)->name('about');
Route::get('/sportovi', [SportController::class, 'index'])->name('sports.index');
Route::get('/tereni/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
Route::get('/cenovnik', PriceListController::class)->name('price-list.index');
Route::get('/oprema', [EquipmentController::class, 'index'])->name('equipment.index');
Route::get('/dogadjaji', [EventController::class, 'index'])->name('events.index');
Route::get('/dogadjaji/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/kontakt', ContactMessageController::class)
    ->middleware('throttle:5,1')
    ->name('contact.store');
Route::post('/rezervacije', [ReservationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('reservations.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ReservationController::class, 'index'])->name('dashboard');
    Route::post('/rezervacije/{reservation}/otkazi', [ReservationController::class, 'cancel'])->name('reservations.cancel');
});

require __DIR__.'/auth.php';
