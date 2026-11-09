<?php

namespace App\Mail;

use App\Models\GroupMatchPlayer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupMatchMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GroupMatchPlayer $player,
        public string $kind,
        public ?string $verificationUrl = null,
    ) {
        $this->player = $player->withoutRelations();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'verify' => 'Potvrdi mesto u ekipi | Arena',
            'booked' => 'Termin je rezervisan, čekamo vas | Arena',
            'cancelled' => 'Grupni termin je otkazan | Arena',
            'closed' => 'Grupni termin nije potvrđen | Arena',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.group-match', with: [
            'match' => $this->player->groupMatch->loadMissing(['sport', 'reservation.court']),
        ]);
    }
}
