<?php

namespace App\Mail;

use App\Models\MudDichiarazione;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MudInvioTelematicoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MudDichiarazione $dichiarazione,
        public string $protocollo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MUD inviato — anno '.$this->dichiarazione->anno_riferimento,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.mud-invio-telematico',
        );
    }
}
