<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SerbatoioSogliaAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $serbatoio
     */
    public function __construct(
        public array $serbatoio,
        public string $statoLabel,
    ) {}

    public function envelope(): Envelope
    {
        $codice = $this->serbatoio['codice'] ?? '—';

        return new Envelope(
            subject: 'Alert serbatoio '.$codice.' — '.$this->statoLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.serbatoio-soglia-alert',
        );
    }
}
