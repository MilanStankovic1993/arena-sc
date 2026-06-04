<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminReservationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public string $mode = 'created',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mode === 'cancelled'
                ? 'Otkazana rezervacija | Arena SC'
                : 'Nova rezervacija | Arena SC',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservations.admin-notification',
        );
    }
}
