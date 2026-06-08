<?php

namespace App\Mail;

use App\Models\StripeDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StripeDisputeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly StripeDispute $dispute,
        public readonly string $eventType,
    ) {}

    public function envelope(): Envelope
    {
        $label = match ($this->eventType) {
            'charge.dispute.created' => '[ALERT] Nuova dispute Stripe',
            'charge.dispute.updated' => '[INFO] Dispute Stripe aggiornata',
            'charge.dispute.closed'  => '[INFO] Dispute Stripe chiusa',
            default                  => '[INFO] Evento dispute Stripe',
        };

        return new Envelope(
            subject: $label.' — '.$this->dispute->stripe_dispute_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.stripe-dispute-admin',
        );
    }
}
