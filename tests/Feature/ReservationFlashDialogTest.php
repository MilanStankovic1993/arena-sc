<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFlashDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_page_shows_success_status_as_popup_dialog(): void
    {
        $this
            ->withSession(['status' => 'Uspesno ste rezervisali termin.'])
            ->get(route('booking.index'))
            ->assertOk()
            ->assertSee('arena-flash-dialog__panel--success', false)
            ->assertSee('data-flash-close', false)
            ->assertSee('href="'.route('booking.index').'"', false)
            ->assertSee('Rezervacija je evidentirana')
            ->assertSee('Uspesno ste rezervisali termin.');
    }

    public function test_booking_validation_errors_are_visible_as_popup_dialog(): void
    {
        $this
            ->followingRedirects()
            ->from(route('booking.index'))
            ->post(route('reservations.store'), [])
            ->assertOk()
            ->assertSee('arena-flash-dialog__panel--error', false)
            ->assertSee('data-flash-close', false)
            ->assertSee('Rezervacija nije prosla')
            ->assertSee('Izaberi teren pre potvrde rezervacije.');
    }
}
