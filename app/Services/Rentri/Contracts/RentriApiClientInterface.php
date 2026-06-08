<?php

namespace App\Services\Rentri\Contracts;

use App\Models\RentriTransazione;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;

interface RentriApiClientInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function request(string $method, string $endpoint, array $payload = []): array;

    public function trackTransaction(string $tipoApi, string $method, string $endpoint, array $payload = []): RentriTransazione;

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array;

    /**
     * @return array<string, mixed>
     */
    public function fetchCodificheCer(): array;

    /**
     * @return array<string, mixed>
     */
    public function fetchFirBlocchi(): array;

    /**
     * @return array<string, mixed>
     */
    public function submitFirVidima(RentriFirVidimaRequest $request): array;

    /**
     * @return array<string, mixed>
     */
    public function waitFirVidimaResult(string $transazioneId): array;

    /**
     * @return array<string, mixed>
     */
    public function submitRegistroTrasmissione(RentriRegistroTrasmissioneRequest $request): array;

    /**
     * @return array<string, mixed>
     */
    public function waitRegistroTrasmissioneResult(string $transazioneId): array;

    /**
     * @return array<string, mixed>
     */
    public function submitXfirFirmato(RentriXfirTrasmissioneRequest $request): array;

    /**
     * @return array<string, mixed>
     */
    public function waitXfirTrasmissioneResult(string $transazioneId): array;

    /**
     * @return array<string, mixed>
     */
    public function replayTransazione(RentriTransazione $transazione): array;

    /**
     * Lookup an operator on RENTRI by CF or P.IVA.
     * Returns registration info or a not-found indicator.
     *
     * @return array{iscritto: bool, numero_iscrizione: string|null, validita_autorizzazione: string|null, ragione_sociale: string|null, raw: array<string, mixed>}
     */
    public function lookupOperatore(string $cfOrPiva): array;
}
