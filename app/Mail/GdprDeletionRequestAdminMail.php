<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GdprDeletionRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $reason,
        public readonly Carbon $scheduledAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[GDPR] Richiesta cancellazione account — '.$this->user->email,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.gdpr-deletion-request-admin',
        );
    }
}
