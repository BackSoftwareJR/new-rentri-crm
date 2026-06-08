<?php

namespace App\Mail;

use App\Models\Trasporto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrasportoGpsGeofenceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{latitude: float, longitude: float}  $position
     * @param  array{latitude: float, longitude: float}  $destination
     */
    public function __construct(
        public Trasporto $trasporto,
        public array $position,
        public array $destination,
        public float $distanceKm,
        public float $radiusKm,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alert geofencing GPS — trasporto #'.$this->trasporto->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.trasporto-gps-geofence-alert',
        );
    }
}
