<?php

namespace App\Mail;

use App\Domain\Vfu\CertificatoRottamazioneGeneratorService;
use App\Models\VfuRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VfuRottamazioneProprietarioNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly VfuRegistration $vfu,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Certificato di rottamazione — '.$this->vfu->targa,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.vfu-rottamazione-proprietario',
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
            return [];
        }
    }
}
