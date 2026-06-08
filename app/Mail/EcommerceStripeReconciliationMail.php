<?php

namespace App\Mail;

use App\Models\EcommerceOrdine;
use App\Models\StripeWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcommerceStripeReconciliationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $reconciliation
     */
    public function __construct(
        public EcommerceOrdine $ordine,
        public StripeWebhookEvent $webhookEvent,
        public array $reconciliation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Riconciliazione Stripe — ordine #'.$this->ordine->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ecommerce-stripe-reconciliation',
        );
    }
}
