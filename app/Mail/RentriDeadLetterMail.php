<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentriDeadLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $transazioneId,
        public string $errore,
        public ?string $codiceErrore = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RENTRI dead-letter — transazione #'.$this->transazioneId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.rentri-dead-letter',
        );
    }
}
