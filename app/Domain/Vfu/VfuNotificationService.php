<?php

namespace App\Domain\Vfu;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\VfuConsegnaAgenziaNotification;
use App\Mail\VfuRottamazioneProprietarioNotification;
use App\Models\Anagrafica;
use App\Models\VfuRegistration;
use App\Services\Pec\PecMailService;
use Illuminate\Support\Facades\Storage;

class VfuNotificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly PecMailService $pecMail,
    ) {}

    /**
     * Dispatch a notification to the designated agenzia when a VFU is sent over.
     * When NOTIFICATIONS_LIVE=true the email (with PDF attachment) is delivered via SMTP.
     * When stub/log mode the action is only logged and no SMTP call is made.
     */
    public function notifyConsegnaAgenzia(VfuRegistration $vfu, Anagrafica $agenzia): void
    {
        $recipient = $agenzia->email
            ?: (string) config('notifications.default_recipient');

        $this->notifications->dispatch(
            NotificationEvent::VfuConsegnaAgenzia,
            new VfuConsegnaAgenziaNotification($vfu, $agenzia),
            $recipient,
            [
                'targa'           => $vfu->targa,
                'telaio'          => $vfu->telaio,
                'agenzia_id'      => $agenzia->id,
                'agenzia'         => $agenzia->ragione_sociale,
                'data_accettazione' => $vfu->data_accettazione?->format('Y-m-d'),
            ],
        );
    }

    /**
     * Email the certificato di rottamazione PDF to the vehicle owner after rottamazione.
     * Skipped when no proprietario email is on record.
     * When NOTIFICATIONS_LIVE=true the email is delivered via SMTP; otherwise stub/log only.
     */
    public function notifyProprietario(VfuRegistration $vfu): void
    {
        if ($this->pecMail->isEnabled() && $this->proprietarioPec($vfu) !== null) {
            $this->notifyProprietarioViaPec($vfu);

            return;
        }

        $recipient = $this->proprietarioEmail($vfu);

        if ($recipient === null) {
            return;
        }

        $this->notifications->dispatch(
            NotificationEvent::VfuRottamato,
            new VfuRottamazioneProprietarioNotification($vfu),
            $recipient,
            [
                'targa'          => $vfu->targa,
                'telaio'         => $vfu->telaio,
                'proprietario'   => $vfu->proprietario,
                'rottamato_at'   => $vfu->rottamato_at?->format('Y-m-d H:i:s'),
                'recipient_type' => 'proprietario',
            ],
        );
    }

    private function notifyProprietarioViaPec(VfuRegistration $vfu): void
    {
        $recipient = $this->proprietarioPec($vfu);

        if ($recipient === null) {
            return;
        }

        $body        = view('mail.vfu-rottamazione-proprietario', ['vfu' => $vfu])->render();
        $subject     = 'Certificato di rottamazione — '.$vfu->targa;
        $attachments = [];

        try {
            $generator = app(CertificatoRottamazioneGeneratorService::class);
            $pdf       = $generator->generatePdf($vfu);
            $filename  = $generator->filename($vfu);
            $tmpPath   = 'tmp/pec-'.uniqid('', true).'.pdf';

            Storage::disk('local')->put($tmpPath, $pdf);

            $attachments[] = [
                'path' => Storage::disk('local')->path($tmpPath),
                'name' => $filename,
                'mime' => 'application/pdf',
            ];
        } catch (\Throwable) {
            // Invio senza allegato se la generazione PDF fallisce.
        }

        $this->pecMail->send($recipient, $subject, $body, $attachments);

        if (isset($tmpPath)) {
            Storage::disk('local')->delete($tmpPath);
        }
    }

    private function proprietarioEmail(VfuRegistration $vfu): ?string
    {
        $email = $vfu->email_proprietario;

        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        return null;
    }

    private function proprietarioPec(VfuRegistration $vfu): ?string
    {
        $pec = $vfu->pec_proprietario;

        if (is_string($pec) && filter_var($pec, FILTER_VALIDATE_EMAIL)) {
            return $pec;
        }

        return null;
    }
}
