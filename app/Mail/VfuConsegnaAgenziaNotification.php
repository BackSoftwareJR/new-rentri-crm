<?php

namespace App\Mail;

use App\Domain\Vfu\CertificatoRottamazioneGeneratorService;
use App\Models\Anagrafica;
use App\Models\VfuRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VfuConsegnaAgenziaNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VfuRegistration $vfu,
        public readonly Anagrafica $agenzia,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pratica VFU — Consegna ad agenzia: '.$this->vfu->targa,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vfu-consegna-agenzia',
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        try {
            $generator = app(CertificatoRottamazioneGeneratorService::class);
            $pdf       = $generator->generatePdf($this->vfu);
            $filename  = $generator->filename($this->vfu);

            return [
                Attachment::fromData(static fn () => $pdf, $filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable) {
            // If certificate generation fails (e.g., state mismatch), send without attachment.
            return [];
        }
    }
}
