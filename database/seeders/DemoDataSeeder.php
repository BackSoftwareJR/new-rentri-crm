<?php

namespace Database\Seeders;

use App\Enums\FirStato;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use Illuminate\Database\Seeder;

/**
 * Popola il CRM con dati demo realistici per walkthrough formativo.
 *
 * Gate: APP_DEMO_MODE=true oppure ambiente local.
 * Quando APP_DEMO_MODE=true il DemoContext è attivo: tutti i record con
 * HasDemoScope ricevono automaticamente is_demo=true e sono invisibili
 * all'applicazione in modalità produzione (is_demo=false).
 *
 * Idempotente: usa firstOrCreate / updateOrCreate su chiavi naturali stabili.
 * Non usa factory — le strutture vengono create direttamente.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $isDemoMode = (bool) config('demo.enabled', false);

        if (! app()->environment('local') && ! $isDemoMode) {
            return;
        }

        // ── Anagrafiche ───────────────────────────────────────────────────────

        $trasportatore = Anagrafica::updateOrCreate(
            ['piva' => '12345678901'],
            [
                'tipo'               => 'trasportatore',
                'ragione_sociale'    => 'Demo Trasporti S.r.l.',
                'codice_fiscale'     => '12345678901',
                'email'              => 'trasporti@demo.local',
                'telefono'           => '+39 02 1234567',
                'indirizzo'          => 'Via della Repubblica 12',
                'citta'              => 'Milano',
                'cap'                => '20121',
                'provincia'          => 'MI',
                'gestisce_trasporti' => true,
                'note'               => 'Trasportatore demo per walkthrough formativo.',
            ]
        );

        Authorization::updateOrCreate(
            ['anagrafica_id' => $trasportatore->id, 'numero' => 'AUT-DEMO-001'],
            [
                'rilasciata_il' => now()->subYear(),
                'scade_il'      => now()->addMonths(8),
                'tipo'          => 'trasporto_rifiuti',
            ]
        );

        $destinatario = Anagrafica::updateOrCreate(
            ['piva' => '98765432109'],
            [
                'tipo'               => 'impianto',
                'ragione_sociale'    => 'Demo Impianto Recupero S.p.A.',
                'codice_fiscale'     => '98765432109',
                'email'              => 'impianto@demo.local',
                'telefono'           => '+39 011 9876543',
                'indirizzo'          => 'Zona Industriale Est 45',
                'citta'              => 'Torino',
                'cap'                => '10134',
                'provincia'          => 'TO',
                'gestisce_trasporti' => false,
                'note'               => 'Impianto destinatario demo per walkthrough formativo.',
            ]
        );

        // ── Codici CER (devono esistere — inseriti da CodiceCerSeeder) ────────

        $cerVfu         = CodiceCer::where('codice', '16.01.04*')->first();
        $cerVfuPulito   = CodiceCer::where('codice', '16.01.04')->first();
        $cerOlio        = CodiceCer::where('codice', '16.01.07*')->first();

        // Fallback sicuro: usa il primo CER disponibile se i codici attesi mancano
        $fallback = CodiceCer::first();
        $cerVfu       ??= $fallback;
        $cerVfuPulito ??= $fallback;
        $cerOlio      ??= $fallback;

        if ($cerVfu === null) {
            $this->command?->warn('DemoDataSeeder: nessun CodiceCer trovato — eseguire CodiceCerSeeder prima.');

            return;
        }

        // ── VFU Registrations ─────────────────────────────────────────────────

        // VFU 1 — Accettato: appena consegnato, in attesa bonifica
        VfuRegistration::updateOrCreate(
            ['targa' => 'AB123CD'],
            [
                'tipo_veicolo'     => 'autovettura',
                'nazione'          => 'IT',
                'telaio'           => 'DEMO00000000001AB',
                'codice_motore'    => 'MOT-DEMO-001',
                'marca'            => 'Fiat',
                'modello'          => 'Punto',
                'nome'             => 'Mario',
                'cognome'          => 'Rossi',
                'proprietario'     => 'Rossi Mario',
                'codice_fiscale'   => 'RSSMRA80A01H501Z',
                'regione'          => 'Lombardia',
                'indirizzo'        => 'Via Roma 1',
                'comune'           => 'Milano',
                'provincia'        => 'MI',
                'data_nascita'     => '1980-01-01',
                'luogo_nascita'    => 'Milano',
                'stato'            => VfuStato::Accettato,
                'peso_kg'          => 1050.00,
                'data_consegna'    => now()->subDays(3)->toDateString(),
            ]
        );

        // VFU 2 — In bonifica: bonifica pericolosi avviata
        VfuRegistration::updateOrCreate(
            ['targa' => 'EF456GH'],
            [
                'tipo_veicolo'     => 'autovettura',
                'nazione'          => 'IT',
                'telaio'           => 'DEMO00000000002EF',
                'codice_motore'    => 'MOT-DEMO-002',
                'marca'            => 'Volkswagen',
                'modello'          => 'Golf',
                'nome'             => 'Giulia',
                'cognome'          => 'Bianchi',
                'proprietario'     => 'Bianchi Giulia',
                'codice_fiscale'   => 'BNCGLI85B42F205X',
                'regione'          => 'Piemonte',
                'indirizzo'        => 'Corso Torino 88',
                'comune'           => 'Torino',
                'provincia'        => 'TO',
                'data_nascita'     => '1985-02-02',
                'luogo_nascita'    => 'Torino',
                'stato'            => VfuStato::InBonifica,
                'peso_kg'          => 1340.00,
                'data_consegna'    => now()->subDays(10)->toDateString(),
            ]
        );

        // VFU 3 — Bonificato (completato): bonifica pericolosi completata, pronto rottamazione
        $vfuCompletato = VfuRegistration::updateOrCreate(
            ['targa' => 'IL789MN'],
            [
                'tipo_veicolo'     => 'furgone',
                'nazione'          => 'IT',
                'telaio'           => 'DEMO00000000003IL',
                'codice_motore'    => 'MOT-DEMO-003',
                'marca'            => 'Renault',
                'modello'          => 'Kangoo',
                'nome'             => 'Giuseppe',
                'cognome'          => 'Verdi',
                'proprietario'     => 'Verdi Giuseppe',
                'codice_fiscale'   => 'VRDGPP70C03F839W',
                'regione'          => 'Veneto',
                'indirizzo'        => 'Via Venezia 22',
                'comune'           => 'Padova',
                'provincia'        => 'PD',
                'data_nascita'     => '1970-03-03',
                'luogo_nascita'    => 'Padova',
                'stato'            => VfuStato::Bonificato,
                'peso_kg'          => 1580.00,
                'data_consegna'    => now()->subDays(20)->toDateString(),
            ]
        );

        // ── FIR Blocco (libro di blocchi vidimati RENTRI) ─────────────────────

        $firBlocco = FirBlocco::updateOrCreate(
            ['codice_blocco' => 'DEMO-BLK-2026-001'],
            [
                'num_iscr_sito'     => 'IT-DEMO-999999',
                'progressivo_ultimo' => 2,
            ]
        );

        // ── Trasporti ─────────────────────────────────────────────────────────

        // Trasporto 1 — in transito, con FIR vidimato
        $trasporto1 = Trasporto::updateOrCreate(
            // Chiave stabile tramite note demo — magazzino_svuotamento_id è nullable
            ['note' => '[DEMO] Trasporto demo 1 — con FIR'],
            [
                'codice_cer_id'              => $cerVfuPulito->id,
                'anagrafica_destinatario_id' => $destinatario->id,
                'quantita_kg'                => 1340.00,
                'stato'                      => TrasportoStato::InTransito,
            ]
        );

        // Trasporto 2 — completato, senza FIR
        $trasporto2 = Trasporto::updateOrCreate(
            ['note' => '[DEMO] Trasporto demo 2 — senza FIR'],
            [
                'codice_cer_id'              => $cerVfu->id,
                'anagrafica_destinatario_id' => $destinatario->id,
                'quantita_kg'                => 1050.00,
                'peso_destinazione_kg'       => 1040.00,
                'stato'                      => TrasportoStato::Completato,
            ]
        );

        // ── FIR collegato al trasporto 1 ──────────────────────────────────────

        Fir::updateOrCreate(
            [
                'codice_blocco' => $firBlocco->codice_blocco,
                'progressivo'   => 1,
            ],
            [
                'numero_fir'      => 'DEMO-BLK-2026-001/0001',
                'stato'           => FirStato::Vidimato,
                'trasporto_id'    => $trasporto1->id,
                'vidimato_at'     => now()->subDays(9),
                'peso_partenza_kg' => 1340.00,
            ]
        );

        Fir::updateOrCreate(
            [
                'codice_blocco' => $firBlocco->codice_blocco,
                'progressivo'   => 2,
            ],
            [
                'numero_fir'      => 'DEMO-BLK-2026-001/0002',
                'stato'           => FirStato::Firmato,
                'trasporto_id'    => $trasporto1->id,
                'vidimato_at'     => now()->subDays(5),
                'firmato_at'      => now()->subDays(4),
                'peso_partenza_kg' => 850.00,
            ]
        );

        // ── Movimenti registro (carico / scarico) ─────────────────────────────

        // Movimento 1 — carico: VFU bonificato entra in magazzino
        RegistroMovimento::firstOrCreate(
            [
                'source_type'    => VfuRegistration::class,
                'source_id'      => $vfuCompletato->id,
                'tipo'           => RegistroMovimentoTipo::Carico,
                'codice_cer_id'  => $cerVfuPulito->id,
            ],
            [
                'peso_kg'           => 1580.00,
                'data_movimento'    => now()->subDays(18),
                'note'              => '[DEMO] Carico VFU bonificato — Renault Kangoo IL789MN',
                'rentri_trasmesso'  => false,
            ]
        );

        // Movimento 2 — scarico: trasporto verso impianto
        RegistroMovimento::firstOrCreate(
            [
                'source_type'    => Trasporto::class,
                'source_id'      => $trasporto2->id,
                'tipo'           => RegistroMovimentoTipo::Scarico,
                'codice_cer_id'  => $cerVfu->id,
            ],
            [
                'peso_kg'           => 1050.00,
                'data_movimento'    => now()->subDays(15),
                'note'              => '[DEMO] Scarico trasporto 2 verso impianto recupero',
                'rentri_trasmesso'  => false,
            ]
        );

        // ── Magazzino — quantità realistiche per CER demo ────────────────────
        // MagazzinoRifiuto non ha HasDemoScope: è un registro globale delle
        // quantità fisiche in magazzino; i valori vengono aggiornati solo se
        // attualmente a zero (prima installazione).

        if ($cerVfu !== null) {
            MagazzinoRifiuto::where('codice_cer_id', $cerVfu->id)
                ->where('quantita_attuale_kg', 0)
                ->update([
                    'quantita_attuale_kg' => 2100.00,
                    'oldest_load_date'    => now()->subDays(30)->toDateString(),
                ]);
        }

        if ($cerVfuPulito !== null) {
            MagazzinoRifiuto::where('codice_cer_id', $cerVfuPulito->id)
                ->where('quantita_attuale_kg', 0)
                ->update([
                    'quantita_attuale_kg' => 3450.00,
                    'oldest_load_date'    => now()->subDays(20)->toDateString(),
                ]);
        }

        if ($cerOlio !== null) {
            MagazzinoRifiuto::where('codice_cer_id', $cerOlio->id)
                ->where('quantita_attuale_kg', 0)
                ->update([
                    'quantita_attuale_kg' => 320.00,
                    'oldest_load_date'    => now()->subDays(45)->toDateString(),
                ]);
        }

        $this->command?->info('DemoDataSeeder: dati demo creati/aggiornati con successo.');
        $this->command?->line('  → 2 anagrafiche (trasportatore + impianto)');
        $this->command?->line('  → 3 VFU registrations (accettato, in_bonifica, bonificato)');
        $this->command?->line('  → 1 FIR blocco + 2 FIR vidimati');
        $this->command?->line('  → 2 trasporti (con FIR / senza FIR)');
        $this->command?->line('  → 2 movimenti registro (carico + scarico)');
        $this->command?->line('  → magazzino: quantità CER aggiornate');
    }
}
