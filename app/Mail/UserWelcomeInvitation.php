<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  non-empty-string  $temporaryPassword  Plain-text temp password — used only to populate the email, never persisted.
     */
    public function __construct(
        public readonly User $user,
        public readonly string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Benvenuto in RENTRI CRM — Il tuo account è stato creato',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-welcome-invitation',
            with: [
                'loginUrl' => route('login'),
            ],
        );
    }
}
