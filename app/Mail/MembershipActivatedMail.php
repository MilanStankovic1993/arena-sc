<?php

namespace App\Mail;

use App\Models\UserMembership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserMembership $membership,
    ) {
        $this->membership->loadMissing(['user', 'membershipPlan.sport']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Članarina je aktivirana | Sportski Centar Arena',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.memberships.activated',
        );
    }
}
