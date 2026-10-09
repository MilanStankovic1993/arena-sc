<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReservationSeriesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $reservations, public bool $cancelled = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->cancelled ? 'Otkazana serija rezervacija' : 'Ponavljajuce rezervacije').' | Sportski centar Arena');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservations.series');
    }
}
