<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $context = 'customer',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rezervacija je otkazana | Sportski centar Arena',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.cancelled',
        );
    }
}
