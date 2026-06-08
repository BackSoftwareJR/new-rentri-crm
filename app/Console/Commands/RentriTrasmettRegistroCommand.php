<?php

namespace App\Console\Commands;

use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Exceptions\RentriRegistroConformitaException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RentriTrasmettRegistroCommand extends Command
{
    protected $signature = 'rentri:trasmetti-registro
                            {--da= : Data inizio periodo YYYY-MM-DD}
                            {--a= : Data fine periodo YYYY-MM-DD}
                            {--dry-run : Mostra cosa verrebbe trasmesso senza inviare}
                            {--notify : Invia email admin con esito trasmissione}';

    protected $description = 'Trasmette al registro RENTRI i movimenti non trasmessi nel periodo indicato';

    public function handle(RentriRegistryServiceInterface $registry): int
    {
        [$periodoDa, $periodoA] = $this->resolvePeriod();

        $this->info(sprintf(
            'Periodo: %s → %s',
            $periodoDa->toDateString(),
            $periodoA->toDateString(),
        ));

        $payload = $registry->buildTransmissionPayload($periodoDa, $periodoA);
        $movimentiCount = $payload->metadata['count'];

        if ($movimentiCount === 0) {
            $this->warn('Nessun movimento da trasmettere nel periodo selezionato.');
            $this->maybeNotify('skipped', $periodoDa, $periodoA, 0, 'Nessun movimento da trasmettere.');

            return self::SUCCESS;
        }

        $this->line("Movimenti da trasmettere: {$movimentiCount}");

        if ($this->option('dry-run')) {
            $this->info('[dry-run] Trasmissione non eseguita.');
            $this->maybeNotify('dry-run', $periodoDa, $periodoA, $movimentiCount, 'Dry-run: nessuna trasmissione inviata.');

            return self::SUCCESS;
        }

        try {
            $transmissione = $registry->transmit($payload);
        } catch (RentriRegistroConformitaException $e) {
            $message = implode('; ', $e->errors);
            $this->error('Trasmissione bloccata — conformità non valida: '.$message);
            $this->maybeNotify('failed', $periodoDa, $periodoA, $movimentiCount, $message);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Trasmissione fallita: '.$e->getMessage());
            $this->maybeNotify('failed', $periodoDa, $periodoA, $movimentiCount, $e->getMessage());

            return self::FAILURE;
        }

        $protocollo = $transmissione->response_json['protocollo'] ?? '—';
        $this->info("Trasmissione completata — protocollo {$protocollo}, {$movimentiCount} movimenti.");
        $this->maybeNotify('success', $periodoDa, $periodoA, $movimentiCount, "Protocollo: {$protocollo}");

        return self::SUCCESS;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolvePeriod(): array
    {
        $da = $this->option('da');
        $a  = $this->option('a');

        if ($da && $a) {
            return [Carbon::parse($da)->startOfDay(), Carbon::parse($a)->endOfDay()];
        }

        $reference = now()->subQuarter();

        return [
            $reference->copy()->startOfQuarter()->startOfDay(),
            $reference->copy()->endOfQuarter()->endOfDay(),
        ];
    }

    private function maybeNotify(
        string $esito,
        Carbon $periodoDa,
        Carbon $periodoA,
        int $movimentiCount,
        string $detail,
    ): void {
        if (! $this->option('notify')) {
            return;
        }

        $recipient = (string) config('notifications.default_recipient', 'admin@example.com');

        if (blank($recipient)) {
            $this->warn('Notifica non inviata: NOTIFICATIONS_RECIPIENT non configurato.');

            return;
        }

        $subject = match ($esito) {
            'success' => '[RENTRI CRM] Trasmissione registro completata',
            'dry-run' => '[RENTRI CRM] Trasmissione registro — dry-run',
            'skipped' => '[RENTRI CRM] Trasmissione registro — nessun movimento',
            default   => '[RENTRI CRM] Trasmissione registro fallita',
        };

        try {
            Mail::raw(
                $subject."\n\n".
                'Periodo: '.$periodoDa->toDateString().' → '.$periodoA->toDateString()."\n".
                "Movimenti: {$movimentiCount}\n".
                "Esito: {$esito}\n".
                "Dettaglio: {$detail}\n\n".
                'Timestamp: '.now()->toIso8601String(),
                fn ($message) => $message->to($recipient)->subject($subject),
            );

            $this->line("Notifica inviata a {$recipient}.");
        } catch (\Throwable $e) {
            $this->warn('Notifica non inviata: '.$e->getMessage());
        }
    }
}
