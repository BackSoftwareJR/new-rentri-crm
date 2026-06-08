<?php

namespace App\Domain\Legacy;

use App\Domain\Anagrafiche\AnagraficaService;
use App\Domain\Audit\ActivityLogService;
use App\Domain\Magazzino\CodiceCerService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\EcommerceProdotto;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Schema;

class LegacyImportService
{
    /** @var list<string> */
    public const ENTITIES = ['anagrafiche', 'codici_cer', 'vfu', 'movimenti', 'ricambi'];

    /** @var list<string> */
    public const SYNC_ENTITIES = ['codici_cer', 'anagrafiche', 'movimenti'];

    /** @return array<string, string> */
    public static function entityLabels(): array
    {
        return [
            'anagrafiche' => 'Anagrafiche',
            'codici_cer'  => 'Codici CER',
            'vfu'         => 'VFU',
            'movimenti'   => 'Movimenti registro',
            'ricambi'     => 'Ricambi e-commerce',
        ];
    }

    /**
     * @return list<array{entity: string, label: string, count: int, status: string}>
     */
    public function reportRows(): array
    {
        return collect($this->report())
            ->map(fn (int $count, string $entity) => [
                'entity' => $entity,
                'label'  => self::entityLabels()[$entity] ?? $entity,
                'count'  => $count,
                'status' => $count > 0 ? 'imported' : 'empty',
            ])
            ->values()
            ->all();
    }

    public function __construct(
        private AnagraficaService $anagrafiche,
        private CodiceCerService $codiciCer,
    ) {}

    public function defaultFixturePath(string $entity): string
    {
        $this->assertEntity($entity);

        return database_path('fixtures/legacy/'.$entity.$this->fixtureExtension($entity));
    }

    private function fixtureExtension(string $entity): string
    {
        return in_array($entity, ['codici_cer', 'movimenti'], true) ? '.json' : '.csv';
    }

    /**
     * @return array{
     *   entity: string,
     *   dry_run: bool,
     *   processed: int,
     *   imported: int,
     *   skipped: int,
     *   errors: list<string>
     * }
     */
    public function import(string $entity, string $filePath, bool $dryRun = false, ?int $limit = null): array
    {
        $this->assertEntity($entity);

        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException('File non leggibile: '.$filePath);
        }

        $result = match ($entity) {
            'anagrafiche' => $this->importAnagrafiche($filePath, $dryRun, $limit),
            'codici_cer'  => $this->importCodiciCer($filePath, $dryRun, $limit),
            'vfu'         => $this->importVfu($filePath, $dryRun, $limit),
            'movimenti'   => $this->importMovimenti($filePath, $dryRun, $limit),
            'ricambi'     => $this->importRicambi($filePath, $dryRun, $limit),
        };

        app(ActivityLogService::class)->record(
            'legacy',
            $dryRun ? 'Import legacy '.$entity.' (dry-run)' : 'Import legacy '.$entity.' completato',
            properties: [
                'entity'        => $entity,
                'dry_run'       => $dryRun,
                'processed'     => $result['processed'],
                'imported'      => $result['imported'],
                'skipped'       => $result['skipped'],
                'errors_count'  => count($result['errors']),
                'file'          => $filePath,
            ],
        );

        return $result;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function importAnagrafiche(string $filePath, bool $dryRun, ?int $limit): array
    {
        $result = $this->emptyResult('anagrafiche', $dryRun);
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return $result;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;
            $data = $this->mapCsvRow($header, $row);

            if ($this->anagraficaRowIsInvalid($data, $result)) {
                continue;
            }

            if ($this->anagraficaExists($data)) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            $this->anagrafiche->create([
                'tipo'            => $data['tipo'],
                'ragione_sociale' => $data['ragione_sociale'],
                'piva'            => $data['piva'] ?: null,
                'email'           => $data['email'] ?: null,
                'citta'           => $data['citta'] ?: null,
                'note'            => isset($data['legacy_id']) && $data['legacy_id'] !== ''
                    ? 'legacy_id:'.$data['legacy_id']
                    : null,
            ]);

            $result['imported']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Sync incrementale anagrafiche: insert nuovi, update email/citta se legacy_id già presente.
     *
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncAnagrafiche(string $filePath, bool $dryRun = false, ?int $limit = null): array
    {
        $result = $this->emptySyncResult('anagrafiche', $dryRun);
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return $result;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;
            $data = $this->mapCsvRow($header, $row);

            if ($this->anagraficaRowIsInvalid($data, $result)) {
                continue;
            }

            $legacyId = trim($data['legacy_id'] ?? '');
            $existing = $legacyId !== ''
                ? Anagrafica::query()->where('note', 'legacy_id:'.$legacyId)->first()
                : null;

            if ($existing !== null) {
                $email = $data['email'] ?: null;
                $citta = $data['citta'] ?: null;
                $changed = $existing->email !== $email || $existing->citta !== $citta;

                if ($changed) {
                    if (! $dryRun) {
                        $existing->update(['email' => $email, 'citta' => $citta]);
                    }
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }

                continue;
            }

            if ($this->anagraficaExists($data)) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            $this->anagrafiche->create([
                'tipo'            => $data['tipo'],
                'ragione_sociale' => $data['ragione_sociale'],
                'piva'            => $data['piva'] ?: null,
                'email'           => $data['email'] ?: null,
                'citta'           => $data['citta'] ?: null,
                'note'            => $legacyId !== '' ? 'legacy_id:'.$legacyId : null,
            ]);

            $result['imported']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Sync incrementale codici CER: insert nuovi, update descrizione/categoria se codice esiste.
     *
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncCodiciCer(string $filePath, bool $dryRun = false, ?int $limit = null): array
    {
        $result = $this->emptySyncResult('codici_cer', $dryRun);
        $json = file_get_contents($filePath);

        if ($json === false) {
            throw new \RuntimeException('Impossibile leggere il file JSON.');
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = json_decode($json, true);

        if (! is_array($rows)) {
            throw new \InvalidArgumentException('JSON non valido: atteso array di oggetti.');
        }

        foreach ($rows as $index => $row) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;

            if (! is_array($row)) {
                $result['errors'][] = 'Riga '.$index.': oggetto non valido.';

                continue;
            }

            $codice = trim((string) ($row['codice'] ?? ''));

            if ($codice === '') {
                $result['errors'][] = 'Riga '.$index.': codice mancante.';

                continue;
            }

            $categoria = (string) ($row['categoria'] ?? 'altro');
            if (! in_array($categoria, ['pericoloso', 'altro'], true)) {
                $categoria = 'altro';
            }

            $descrizione = (string) ($row['descrizione'] ?? $codice);
            $um = (string) ($row['um'] ?? 'kg');
            $existing = CodiceCer::query()->where('codice', $codice)->first();

            if ($existing !== null) {
                $changed = $existing->descrizione !== $descrizione
                    || $existing->categoria !== $categoria
                    || $existing->um !== $um;

                if ($changed) {
                    if (! $dryRun) {
                        $existing->update([
                            'descrizione' => $descrizione,
                            'categoria'   => $categoria,
                            'um'          => $um,
                        ]);
                    }
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            $this->codiciCer->create([
                'codice'      => $codice,
                'descrizione' => $descrizione,
                'categoria'   => $categoria,
                'um'          => $um,
                'limite_kg'   => isset($row['limite_kg']) ? (float) $row['limite_kg'] : null,
                'attivo'      => true,
            ]);

            $result['imported']++;
        }

        return $result;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}
     */
    private function emptySyncResult(string $entity, bool $dryRun): array
    {
        return [
            'entity'    => $entity,
            'dry_run'   => $dryRun,
            'processed' => 0,
            'imported'  => 0,
            'updated'   => 0,
            'skipped'   => 0,
            'errors'    => [],
        ];
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function importVfu(string $filePath, bool $dryRun, ?int $limit): array
    {
        $result = $this->emptyResult('vfu', $dryRun);
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return $result;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;
            $data = $this->mapCsvRow($header, $row);

            if ($this->vfuRowIsInvalid($data, $result)) {
                continue;
            }

            if ($this->vfuExists($data)) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            VfuRegistration::create([
                'tipo_veicolo'  => 'Autovettura',
                'nazione'       => 'Italia',
                'targa'         => strtoupper($data['targa']),
                'telaio'        => strtoupper($data['telaio'] !== '' ? $data['telaio'] : 'LEG-'.$data['legacy_id']),
                'marca'         => $data['marca'],
                'modello'       => $data['modello'],
                'proprietario'  => $data['proprietario'] ?: null,
                'stato'         => VfuStato::from($data['stato']),
                'peso_kg'       => (float) ($data['peso_kg'] !== '' ? $data['peso_kg'] : 0),
                'data_consegna' => $data['data_consegna'] !== '' ? $data['data_consegna'] : null,
                'note'          => isset($data['legacy_id']) && $data['legacy_id'] !== ''
                    ? 'legacy_id:'.$data['legacy_id']
                    : null,
            ]);

            $result['imported']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function importRicambi(string $filePath, bool $dryRun, ?int $limit): array
    {
        $result = $this->emptyResult('ricambi', $dryRun);
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return $result;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;
            $data = $this->mapCsvRow($header, $row);

            if ($this->ricambioRowIsInvalid($data, $result)) {
                continue;
            }

            if ($this->ricambioExists($data)) {
                $result['skipped']++;

                continue;
            }

            $vfuId = $this->resolveVfuIdFromTarga($data['targa_vfu'] ?? '', $result);

            if ($vfuId === false) {
                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            EcommerceProdotto::create([
                'codice'              => $data['codice'],
                'nome'                => $data['nome'],
                'descrizione'         => $this->ricambioDescrizione($data),
                'categoria'           => $data['categoria'] !== '' ? $data['categoria'] : 'generico',
                'prezzo'              => (float) ($data['prezzo'] !== '' ? $data['prezzo'] : 0),
                'giacenza'            => max(0, (int) ($data['giacenza'] !== '' ? $data['giacenza'] : 0)),
                'vfu_registration_id' => $vfuId,
                'attivo'              => true,
            ]);

            $result['imported']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function importMovimenti(string $filePath, bool $dryRun, ?int $limit): array
    {
        $result = $this->emptyResult('movimenti', $dryRun);
        $json = file_get_contents($filePath);

        if ($json === false) {
            throw new \RuntimeException('Impossibile leggere il file JSON.');
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = json_decode($json, true);

        if (! is_array($rows)) {
            throw new \InvalidArgumentException('JSON non valido: atteso array di oggetti.');
        }

        foreach ($rows as $index => $row) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;

            if (! is_array($row)) {
                $result['errors'][] = 'Riga '.$index.': oggetto non valido.';

                continue;
            }

            $payload = $this->resolveMovimentoRow($row, $index, $result);

            if ($payload === null) {
                continue;
            }

            if ($this->movimentoExists($payload['legacy_id'])) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            RegistroMovimento::create([
                'tipo'            => $payload['tipo'],
                'codice_cer_id'   => $payload['codice_cer_id'],
                'peso_kg'         => $payload['peso_kg'],
                'data_movimento'  => $payload['data_movimento'],
                'note'            => $payload['note'],
                'source_type'     => null,
                'source_id'       => null,
                'rentri_trasmesso'=> false,
            ]);

            $result['imported']++;
        }

        return $result;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function importCodiciCer(string $filePath, bool $dryRun, ?int $limit): array
    {
        $result = $this->emptyResult('codici_cer', $dryRun);
        $json = file_get_contents($filePath);

        if ($json === false) {
            throw new \RuntimeException('Impossibile leggere il file JSON.');
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = json_decode($json, true);

        if (! is_array($rows)) {
            throw new \InvalidArgumentException('JSON non valido: atteso array di oggetti.');
        }

        foreach ($rows as $index => $row) {
            if ($limit !== null && $result['processed'] >= $limit) {
                break;
            }

            $result['processed']++;

            if (! is_array($row)) {
                $result['errors'][] = 'Riga '.$index.': oggetto non valido.';

                continue;
            }

            $codice = trim((string) ($row['codice'] ?? ''));

            if ($codice === '') {
                $result['errors'][] = 'Riga '.$index.': codice mancante.';

                continue;
            }

            if (CodiceCer::query()->where('codice', $codice)->exists()) {
                $result['skipped']++;

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            $categoria = (string) ($row['categoria'] ?? 'altro');
            if (! in_array($categoria, ['pericoloso', 'altro'], true)) {
                $categoria = 'altro';
            }

            $this->codiciCer->create([
                'codice'      => $codice,
                'descrizione' => (string) ($row['descrizione'] ?? $codice),
                'categoria'   => $categoria,
                'um'          => (string) ($row['um'] ?? 'kg'),
                'limite_kg'   => isset($row['limite_kg']) ? (float) $row['limite_kg'] : null,
                'attivo'      => true,
            ]);

            $result['imported']++;
        }

        return $result;
    }

    /**
     * @param  list<string|null>  $header
     * @param  list<string|null>  $row
     * @return array<string, string>
     */
    private function mapCsvRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $i => $key) {
            if ($key === null || $key === '') {
                continue;
            }
            $mapped[$key] = trim((string) ($row[$i] ?? ''));
        }

        return $mapped;
    }

    /**
     * @param  array<string, string>  $data
     * @param  array{errors: list<string>}  $result
     */
    private function anagraficaRowIsInvalid(array $data, array &$result): bool
    {
        $tipo = $data['tipo'] ?? '';
        $ragione = $data['ragione_sociale'] ?? '';

        if ($ragione === '') {
            $result['errors'][] = 'Riga senza ragione_sociale ignorata.';

            return true;
        }

        if ($tipo === '' || ! in_array($tipo, Anagrafica::TIPI, true)) {
            $result['errors'][] = 'Tipo non valido per '.$ragione.': '.$tipo;

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function anagraficaExists(array $data): bool
    {
        $piva = $data['piva'] ?? '';

        if ($piva !== '') {
            return Anagrafica::query()->where('piva', $piva)->exists();
        }

        return Anagrafica::query()
            ->where('ragione_sociale', $data['ragione_sociale'])
            ->where('tipo', $data['tipo'])
            ->exists();
    }

    /**
     * @param  array<string, string>  $data
     * @param  array{errors: list<string>}  $result
     */
    private function vfuRowIsInvalid(array $data, array &$result): bool
    {
        $targa = $data['targa'] ?? '';
        $marca = $data['marca'] ?? '';
        $modello = $data['modello'] ?? '';
        $stato = $data['stato'] ?? '';

        if ($targa === '') {
            $result['errors'][] = 'Riga senza targa ignorata.';

            return true;
        }

        if ($marca === '' || $modello === '') {
            $result['errors'][] = 'Marca/modello mancanti per targa '.$targa.'.';

            return true;
        }

        if ($stato === '' || VfuStato::tryFrom($stato) === null) {
            $result['errors'][] = 'Stato non valido per '.$targa.': '.$stato;

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function vfuExists(array $data): bool
    {
        $targa = strtoupper($data['targa']);

        if (VfuRegistration::query()->where('targa', $targa)->exists()) {
            return true;
        }

        $telaio = strtoupper(trim($data['telaio'] ?? ''));

        if ($telaio !== '' && VfuRegistration::query()->where('telaio', $telaio)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{errors: list<string>}  $result
     * @return array{legacy_id: string, tipo: RegistroMovimentoTipo, codice_cer_id: int, peso_kg: float, data_movimento: string, note: string}|null
     */
    private function resolveMovimentoRow(array $row, int $index, array &$result): ?array
    {
        $legacyId = trim((string) ($row['legacy_id'] ?? ''));

        if ($legacyId === '') {
            $result['errors'][] = 'Riga '.$index.': legacy_id mancante.';

            return null;
        }

        $tipoRaw = trim((string) ($row['tipo'] ?? ''));
        $tipo = RegistroMovimentoTipo::tryFrom($tipoRaw);

        if ($tipo === null) {
            $result['errors'][] = 'Riga '.$index.': tipo non valido ('.$tipoRaw.').';

            return null;
        }

        $codiceCer = trim((string) ($row['codice_cer'] ?? ''));

        if ($codiceCer === '') {
            $result['errors'][] = 'Riga '.$index.': codice_cer mancante.';

            return null;
        }

        $cer = CodiceCer::query()->where('codice', $codiceCer)->first();

        if ($cer === null) {
            $result['errors'][] = 'Riga '.$index.': codice CER non trovato ('.$codiceCer.').';

            return null;
        }

        $pesoKg = (float) ($row['peso_kg'] ?? 0);

        if ($pesoKg <= 0) {
            $result['errors'][] = 'Riga '.$index.': peso_kg non valido.';

            return null;
        }

        $dataRaw = trim((string) ($row['data_movimento'] ?? ''));

        if ($dataRaw === '') {
            $result['errors'][] = 'Riga '.$index.': data_movimento mancante.';

            return null;
        }

        try {
            $dataMovimento = \Illuminate\Support\Carbon::parse($dataRaw)->toDateTimeString();
        } catch (\Throwable) {
            $result['errors'][] = 'Riga '.$index.': data_movimento non valida ('.$dataRaw.').';

            return null;
        }

        $noteExtra = trim((string) ($row['note'] ?? ''));
        $note = 'legacy_id:'.$legacyId.($noteExtra !== '' ? ' — '.$noteExtra : '');

        return [
            'legacy_id'       => $legacyId,
            'tipo'            => $tipo,
            'codice_cer_id'   => $cer->id,
            'peso_kg'         => $pesoKg,
            'data_movimento'  => $dataMovimento,
            'note'            => $note,
        ];
    }

    private function movimentoExists(string $legacyId): bool
    {
        return RegistroMovimento::query()
            ->where('note', 'like', 'legacy_id:'.$legacyId.'%')
            ->exists();
    }

    /**
     * @param  array<string, string>  $data
     * @param  array{errors: list<string>}  $result
     */
    private function ricambioRowIsInvalid(array $data, array &$result): bool
    {
        $codice = $data['codice'] ?? '';
        $nome = $data['nome'] ?? '';

        if ($codice === '') {
            $result['errors'][] = 'Riga senza codice ignorata.';

            return true;
        }

        if ($nome === '') {
            $result['errors'][] = 'Nome mancante per codice '.$codice.'.';

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function ricambioExists(array $data): bool
    {
        return EcommerceProdotto::query()->where('codice', $data['codice'])->exists();
    }

    /**
     * @param  array<string, string>  $data
     */
    private function ricambioDescrizione(array $data): ?string
    {
        $legacyId = $data['legacy_id'] ?? '';
        $descrizione = $data['descrizione'] ?? '';

        if ($legacyId === '' && $descrizione === '') {
            return null;
        }

        if ($legacyId === '') {
            return $descrizione;
        }

        return $descrizione !== ''
            ? 'legacy_id:'.$legacyId.' — '.$descrizione
            : 'legacy_id:'.$legacyId;
    }

    /**
     * @param  array{errors: list<string>}  $result
     * @return int|null|false null se targa vuota, false se targa non trovata
     */
    private function resolveVfuIdFromTarga(string $targa, array &$result): int|null|false
    {
        $targa = strtoupper(trim($targa));

        if ($targa === '') {
            return null;
        }

        $vfu = VfuRegistration::query()->where('targa', $targa)->first();

        if ($vfu === null) {
            $result['errors'][] = 'VFU non trovato per targa '.$targa.'.';

            return false;
        }

        return $vfu->id;
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}
     */
    private function emptyResult(string $entity, bool $dryRun): array
    {
        return [
            'entity'    => $entity,
            'dry_run'   => $dryRun,
            'processed' => 0,
            'imported'  => 0,
            'skipped'   => 0,
            'errors'    => [],
        ];
    }

    private function assertEntity(string $entity): void
    {
        if (! in_array($entity, self::ENTITIES, true)) {
            throw new \InvalidArgumentException(
                'Entità non supportata: '.$entity.'. Valori: '.implode(', ', self::ENTITIES)
            );
        }
    }

    /**
     * @return array<string, int>
     */
    public function report(): array
    {
        return [
            'anagrafiche' => $this->countLegacyRows('anagrafiche', fn () => Anagrafica::query()->where('note', 'like', 'legacy_id:%')->count()),
            'codici_cer'  => Schema::hasTable('codici_cer') ? $this->countImportedCodiciCer() : 0,
            'vfu'         => $this->countLegacyRows('vfu_registrations', fn () => VfuRegistration::query()->where('note', 'like', 'legacy_id:%')->count()),
            'movimenti'   => $this->countLegacyRows('registro_movimenti', fn () => RegistroMovimento::query()->where('note', 'like', 'legacy_id:%')->count()),
            'ricambi'     => $this->countLegacyRows('ecommerce_prodotti', fn () => EcommerceProdotto::query()->where('descrizione', 'like', 'legacy_id:%')->count()),
        ];
    }

    private function countLegacyRows(string $table, callable $count): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $count();
    }

    public function reportTotal(): int
    {
        return array_sum($this->report());
    }

    private function countImportedCodiciCer(): int
    {
        $path = database_path('fixtures/legacy/codici_cer.json');

        if (! is_readable($path)) {
            return 0;
        }

        $json = file_get_contents($path);

        if ($json === false) {
            return 0;
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = json_decode($json, true);

        if (! is_array($rows)) {
            return 0;
        }

        $codici = collect($rows)
            ->map(fn (array $row) => trim((string) ($row['codice'] ?? '')))
            ->filter()
            ->all();

        if ($codici === []) {
            return 0;
        }

        return CodiceCer::query()->whereIn('codice', $codici)->count();
    }
}
