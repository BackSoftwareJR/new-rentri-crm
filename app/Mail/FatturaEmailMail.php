<?php

namespace App\Mail;

use App\Models\Fattura;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FatturaEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Fattura $fattura,
        public readonly string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fattura '.$this->fattura->numero_fattura,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.fattura-email',
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $filename = basename($this->pdfPath);

        return [
            Attachment::fromStorageDisk('local', $this->pdfPath)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
}
