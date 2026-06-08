<?php

namespace App\Domain\Demo;

use App\Domain\Trasporti\TrasportoService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\SvuotamentoStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MagazzinoSvuotamento;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DemoSeedService
{
    public const BLOCCO_CODICE = 'DEMO-BLK-001';

    public const NUM_ISCR_SITO = 'DEMO-SITE-001';

    public const TRASPORTO_NOTE = 'Generato da rentri:demo-seed';

    public const SVUOTAMENTO_NOTE = 'Generato da rentri:demo-seed';

    public const MOVIMENTO_NOTE = 'Demo seed — bozza da trasmettere a RENTRI';

    public function __construct(
        private readonly DemoResetService $reset,
    ) {}

    public function isSeeded(): bool
    {
        return FirBlocco::query()
            ->where('codice_blocco', self::BLOCCO_CODICE)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function seed(): array
    {
        if ($this->isSeeded()) {
            return [
                'skipped'    => true,
                'seeded'     => true,
                'trasporto'  => $this->demoTrasporto()?->id,
                'svuotamento'=> $this->demoSvuotamento()?->id,
                'movimento'  => $this->demoMovimento()?->id,
            ];
        }

        return DB::transaction(function () {
            $settings = $this->seedRentriSettings();
            $blocco = $this->seedFirBlocco();
            $trasporto = $this->seedTrasporto();
            $movimento = $this->seedRegistroMovimento($trasporto);

            return [
                'skipped'            => false,
                'seeded'             => true,
                'rentri_settings'    => $settings->id,
                'fir_blocco'         => $blocco->id,
                'magazzino_svuotamento' => $trasporto->magazzino_svuotamento_id,
                'trasporto'          => $trasporto->id,
                'registro_movimento' => $movimento->id,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function freshSeed(): array
    {
        $this->reset->resetDemoData();

        return $this->seed();
    }

    /**
     * @return list<array{label: string, description: string, href: string, done: bool, mobile_href?: string}>
     */
    public function walkthroughSteps(): array
    {
        $settings = RentriSetting::instance();
        $trasporto = $this->demoTrasporto();
        $movimento = $this->demoMovimento();

        $certOk = filled($settings->cert_path_encrypted)
            && ($settings->cert_scadenza === null || $settings->cert_scadenza->isFuture());

        $trasportoHref = $trasporto
            ? route('segreteria.trasporti.show', $trasporto)
            : route('segreteria.trasporti');

        $registroHref = $movimento
            ? route('segreteria.registro-movimenti')
            : route('segreteria.rentri');

        $rentriSettingsBase = route('segreteria.impostazioni.rentri');

        return [
            [
                'label'       => '1. Preset sandbox RENTRI',
                'description' => 'Seleziona profilo operatore e applica preset demo',
                'href'        => $rentriSettingsBase.'?step=1',
                'done'        => filled($settings->num_iscr_sito) && $settings->ambiente === 'sandbox',
            ],
            [
                'label'       => '2. Certificato sandbox mTLS',
                'description' => 'Upload PKCS#12 MASE + verifica scadenza',
                'href'        => $rentriSettingsBase.'?step=2',
                'done'        => $certOk,
            ],
            [
                'label'       => '3. Blocchi FIR demo',
                'description' => 'Verifica blocco demo '.self::BLOCCO_CODICE,
                'href'        => route('segreteria.fir.blocchi'),
                'done'        => $this->isSeeded(),
            ],
            [
                'label'        => '4. Trasporto in preparazione',
                'description'  => 'Apri il trasporto demo per vidima FIR',
                'href'         => $trasportoHref,
                'mobile_href'  => route('operatore.vetrina'),
                'done'         => $trasporto !== null,
            ],
            [
                'label'        => '5. Vidima e firma xFIR',
                'description'  => 'Dal dettaglio trasporto: vidima → firma COSE',
                'href'         => $trasportoHref,
                'mobile_href'  => route('operatore.ricambi'),
                'done'         => $trasporto?->firCollegato?->vidimato_at !== null,
            ],
            [
                'label'       => '6. Trasmissione registro',
                'description' => 'Trasmetti il movimento bozza a RENTRI sandbox',
                'href'        => $registroHref,
                'done'        => $movimento?->rentri_trasmesso === true,
            ],
        ];
    }

    /**
     * @return array{completed: int, total: int, percent: int, cert_warning: ?string}
     */
    public function walkthroughProgress(): array
    {
        $steps = $this->walkthroughSteps();
        $completed = count(array_filter($steps, fn (array $step): bool => $step['done']));
        $total = count($steps);
        $settings = RentriSetting::instance();

        $certWarning = null;
        if ($settings->cert_scadenza?->isPast()) {
            $certWarning = 'Certificato mTLS scaduto — ricaricalo dalle Impostazioni RENTRI (step 2).';
        } elseif ($settings->cert_scadenza?->lte(now()->addDays(30))) {
            $certWarning = 'Certificato mTLS in scadenza il '.$settings->cert_scadenza->format('d/m/Y').'.';
        } elseif (blank($settings->cert_path_encrypted)) {
            $certWarning = 'Certificato mTLS non caricato — le chiamate API restano in stub locale.';
        }

        return [
            'completed'     => $completed,
            'total'         => $total,
            'percent'       => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'cert_warning'  => $certWarning,
        ];
    }

    public function demoTrasporto(): ?Trasporto
    {
        return Trasporto::query()
            ->where('note', self::TRASPORTO_NOTE)
            ->first();
    }

    public function demoSvuotamento(): ?MagazzinoSvuotamento
    {
        return MagazzinoSvuotamento::query()
            ->where('note_interne', self::SVUOTAMENTO_NOTE)
            ->first();
    }

    public function demoMovimento(): ?RegistroMovimento
    {
        return RegistroMovimento::query()
            ->where('note', self::MOVIMENTO_NOTE)
            ->first();
    }

    private function seedRentriSettings(): RentriSetting
    {
        $settings = RentriSetting::instance();
        $settings->fill([
            'ambiente'                  => 'sandbox',
            'cf'                        => '12345678901',
            'cf_operatore'              => 'RSSMRA80A01H501Z',
            'piva'                      => '12345678901',
            'ragione_sociale'           => 'Demo Autodemolizione RENTRI',
            'num_iscr_sito'             => self::NUM_ISCR_SITO,
            'onboarding_step_completed' => 1,
        ]);
        $settings->save();

        return $settings->fresh();
    }

    private function seedFirBlocco(): FirBlocco
    {
        return FirBlocco::firstOrCreate(
            [
                'codice_blocco' => self::BLOCCO_CODICE,
                'num_iscr_sito' => self::NUM_ISCR_SITO,
            ],
            ['progressivo_ultimo' => 0],
        );
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::query()->where('codice', '16.01.04')->first()
            ?? CodiceCer::factory()->create(['codice' => '16.01.04', 'descrizione' => 'Demo CER']);

        $destinatario = Anagrafica::firstOrCreate(
            ['email' => 'demo-impianto@rentri-demo.local'],
            [
                'tipo'            => 'impianto',
                'ragione_sociale' => 'Impianto Demo RENTRI',
                'piva'            => '98765432109',
                'telefono'        => '0000000000',
            ],
        );

        $trasportatore = Anagrafica::firstOrCreate(
            ['email' => 'demo-trasportatore@rentri-demo.local'],
            [
                'tipo'               => 'trasportatore',
                'ragione_sociale'    => 'Trasportatore Demo RENTRI',
                'gestisce_trasporti' => true,
                'piva'               => '11223344556',
                'telefono'           => '0000000001',
            ],
        );

        Authorization::factory()->create([
            'anagrafica_id' => $trasportatore->id,
            'scade_il'      => now()->addYear(),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $svuotamento = MagazzinoSvuotamento::create([
            'codice_cer_id'               => $cer->id,
            'anagrafica_id'                => $destinatario->id,
            'trasportatore_anagrafica_id' => $trasportatore->id,
            'trasportatore_omesso'        => false,
            'stato'                       => SvuotamentoStato::Richiesto,
            'quantita_kg'                 => 250,
            'quantita_impegnata_kg'       => 250,
            'note_interne'                => self::SVUOTAMENTO_NOTE,
            'user_id'                     => $user->id,
        ]);

        $trasporto = app(TrasportoService::class)->creaDaSvuotamento($svuotamento);
        $trasporto->update(['note' => self::TRASPORTO_NOTE]);

        return $trasporto->fresh();
    }

    private function seedRegistroMovimento(Trasporto $trasporto): RegistroMovimento
    {
        return RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $trasporto->codice_cer_id,
            'peso_kg'          => 250,
            'data_movimento'   => now()->subDay(),
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
            'note'             => self::MOVIMENTO_NOTE,
            'rentri_trasmesso' => false,
        ]);
    }
}
