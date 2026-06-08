<?php

namespace App\Mail;

use App\Models\VfuRegistration;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BonificaPericolosiCompletataMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VfuRegistration $vfu,
        public ?Carbon $deadline,
        public bool $withinDeadline,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bonifica pericolosi completata — '.$this->vfu->targa,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.bonifica-pericolosi-completata',
        );
    }
}
