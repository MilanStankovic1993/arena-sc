<?php

namespace App\Mail;

use App\Models\UserMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserMembership $membership,
        public int $daysBeforeExpiry,
    ) {
        $this->membership->loadMissing(['user', 'membershipPlan.sport']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Članarina uskoro istice | Sportski Centar Arena',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memberships.expiring-soon',
        );
    }
}
