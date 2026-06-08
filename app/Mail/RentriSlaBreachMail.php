<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentriSlaBreachMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $breaches
     * @param  array<string, mixed>  $metrics
     */
    public function __construct(
        public array $breaches,
        public array $metrics,
        public int $periodDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RENTRI SLA fuori soglia — '.$this->periodDays.' giorni',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.rentri-sla-breach',
        );
    }
}
