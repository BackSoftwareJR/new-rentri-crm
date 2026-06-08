<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessKpiBreachMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $breaches
     * @param  array<string, mixed>  $comparison
     */
    public function __construct(
        public array $breaches,
        public array $comparison,
        public string $periodKey,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KPI business sotto soglia — '.$this->comparison['label'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.business-kpi-breach',
        );
    }
}
