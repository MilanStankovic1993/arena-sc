<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReservationDeletedMail extends Mailable
{
    use Queueable;

    // Keep a snapshot: queue delivery must work even after deletion or restoration.
    public function __construct(public array $snapshot) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Obrisana rezervacija #'.$this->snapshot['id'].' | Sportski centar Arena');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservations.deleted');
    }
}
