<?php

namespace App\Http\Livewire\Settings;

use App\Domain\Demo\DemoRentriPresetService;
use App\Domain\Rentri\RentriCertPreviewService;
use App\Domain\Rentri\RentriConnectionStatusService;
use App\Domain\Rentri\RentriLiveModeService;
use App\Domain\Rentri\RentriOnboardingService;
use App\Domain\Rentri\RentriProdReadinessService;
use App\Domain\Rentri\RentriProductionCertValidationService;
use App\Domain\Rentri\RentriProductionSwitchService;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Domain\Rentri\RentriSandboxValidationService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\RentriSetting;
use App\Support\Demo\DemoContext;
use App\Support\UploadValidation;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Impostazioni RENTRI')]
class RentriSettings extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $step = 1;

    public string $ambiente = 'sandbox';

    public string $cf = '';

    public string $cf_operatore = '';

    public string $piva = '';

    public string $ragione_sociale = '';

    public string $num_iscr_sito = '';

    public string $note_operatore = '';

    public string $selectedOperatorPreset = 'default';

    public $certificato;

    public string $cert_password = '';

    public $firma_certificato;

    public string $firma_cert_password = '';

    /** @var array<string, mixed>|null */
    public ?array $healthStatus = null;

    public ?int $lastCodificheCount = null;

    /** @var array<string, mixed>|null */
    public ?array $sandboxValidationResult = null;

    /** @var array<string, mixed>|null */
    public ?array $productionCertValidationResult = null;

    public bool $onboardingComplete = false;

    public bool $certModalOpen = false;

    public string $certModalKind = 'mtls';

    public function mount(RentriOnboardingService $onboarding): void
    {
        $settings = RentriSetting::instance();
        $this->authorize('view', $settings);

        $this->fillFromSettings($settings, $onboarding);

        $requestedStep = (int) request()->query('step', 0);
        if ($requestedStep >= 1 && $requestedStep <= RentriOnboardingService::TOTAL_STEPS) {
            $this->goToStep($requestedStep);
        }
    }

    public function goToStep(int $step): void
    {
        $max = $this->onboardingComplete
            ? RentriProdReadinessService::PRODUCTION_STEP
            : min($this->step, RentriSetting::instance()->onboarding_step_completed + 1);

        if ($step >= 1 && $step <= $max) {
            $this->step = $step;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function saveOperatorData(RentriOnboardingService $onboarding): void
    {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $validated = $this->validate([
            'ambiente'        => ['required', 'in:sandbox,produzione'],
            'cf'              => ['required', 'string', 'max:16'],
            'cf_operatore'    => ['required', 'string', 'max:16'],
            'piva'            => ['required', 'string', 'max:20'],
            'ragione_sociale' => ['required', 'string', 'max:200'],
            'num_iscr_sito'   => ['required', 'string', 'max:50'],
        ]);

        $onboarding->saveOperatorData($validated);
        $this->fillFromSettings(RentriSetting::instance()->fresh(), $onboarding);
        $this->step = 2;

        session()->flash('success', 'Dati operatore salvati. Procedi con il certificato interoperabilità.');
    }

    public function uploadCertificato(
        RentriOnboardingService $onboarding,
        RentriCertificateServiceInterface $certificates,
    ): void {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $this->validate([
            'certificato'   => UploadValidation::certificateRules(),
            'cert_password' => ['required', 'string', 'min:4', 'max:128'],
        ]);

        try {
            $onboarding->saveCertificate($this->certificato, $this->cert_password, $certificates);
        } catch (\InvalidArgumentException $e) {
            $this->addError('certificato', $e->getMessage());

            return;
        }

        $this->reset(['certificato', 'cert_password']);
        $this->fillFromSettings(RentriSetting::instance()->fresh(), $onboarding);
        $this->step = 3;

        session()->flash('success', 'Certificato PKCS#12 salvato in storage sicuro. Esegui il test connessione.');
    }

    public function uploadFirmaCertificato(RentriFirmaCertificateServiceInterface $firmaCertificates): void
    {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $this->validate([
            'firma_certificato'   => UploadValidation::certificateRules(),
            'firma_cert_password' => ['required', 'string', 'min:4', 'max:128'],
        ]);

        try {
            $firmaCertificates->upload($this->firma_certificato, $this->firma_cert_password);
        } catch (\InvalidArgumentException $e) {
            $this->addError('firma_certificato', $e->getMessage());

            return;
        }

        $this->reset(['firma_certificato', 'firma_cert_password']);
        session()->flash('success', 'Certificato firma remota xFIR salvato (distinto da interoperabilità mTLS).');
    }

    public function runHealthCheck(
        RentriOnboardingService $onboarding,
        RentriApiClientInterface $apiClient,
    ): void {
        $this->testConnection($onboarding, $apiClient);
    }

    public function runSandboxValidation(
        RentriSandboxValidationService $validation,
        RentriApiClientInterface $apiClient,
    ): void {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $this->sandboxValidationResult = $validation->run($apiClient, $settings);

        if ($this->sandboxValidationResult['codifiche_count'] !== null) {
            $this->lastCodificheCount = $this->sandboxValidationResult['codifiche_count'];
        }

        $overall = $this->sandboxValidationResult['overall'] ?? 'fail';

        session()->flash(
            $overall === 'ok' ? 'success' : ($overall === 'warn' ? 'warning' : 'error'),
            match ($overall) {
                'ok'   => 'Validazione sandbox MASE completata con successo.',
                'warn' => 'Validazione sandbox parziale — verificare prerequisiti live.',
                default => 'Validazione sandbox fallita — correggere gli step in errore.',
            },
        );
    }

    public function runProductionCertValidation(
        RentriProductionCertValidationService $validation,
        RentriApiClientInterface $apiClient,
    ): void {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $this->productionCertValidationResult = $validation->run($apiClient, $settings);

        if ($this->productionCertValidationResult['codifiche_count'] !== null) {
            $this->lastCodificheCount = $this->productionCertValidationResult['codifiche_count'];
        }

        $overall = $this->productionCertValidationResult['overall'] ?? 'fail';

        session()->flash(
            $overall === 'ok' ? 'success' : ($overall === 'warn' ? 'warning' : 'error'),
            match ($overall) {
                'ok'   => 'Validazione certificato produzione MASE completata con successo.',
                'warn' => 'Validazione produzione parziale — verificare prerequisiti live.',
                default => 'Validazione produzione fallita — correggere gli step in errore.',
            },
        );
    }

    public function testConnection(
        RentriOnboardingService $onboarding,
        RentriApiClientInterface $apiClient,
    ): void {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        try {
            $usesStub = $this->resolvesApiStub($settings);

            if ($usesStub) {
                $settings = $onboarding->runHealthCheck($apiClient);
                $this->lastCodificheCount = null;
                $message = DemoContext::isSessionDemoActive() && blank($settings->cert_path_encrypted)
                    ? 'Health check stub — caricare certificato sandbox per chiamate live demoapi.'
                    : 'Health check completato in modalità stub.';
                session()->flash('success', $message);
            } else {
                $result = $onboarding->testConnection($apiClient);
                $settings = RentriSetting::instance()->fresh();
                $this->lastCodificheCount = $result['codifiche_count'];
                session()->flash('success', sprintf(
                    'Connessione RENTRI verificata. Codifiche CER disponibili: %d.',
                    $result['codifiche_count'],
                ));
            }
        } catch (RentriApiException $e) {
            $this->addError('health', $e->getMessage());

            return;
        } catch (\RuntimeException $e) {
            $this->addError('health', $e->getMessage());

            return;
        }

        $this->fillFromSettings($settings, $onboarding);
    }

    public function applySandboxPreset(
        DemoRentriPresetService $presetService,
        RentriOnboardingService $onboarding,
    ): void {
        if (! DemoContext::isActive()) {
            $this->addError('preset', 'Preset sandbox disponibile solo in modalità demo / palestra operativa.');

            return;
        }

        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $this->validate([
            'selectedOperatorPreset' => ['required', 'string'],
        ]);

        $presetService->applySandboxPreset($onboarding, $this->selectedOperatorPreset);
        $this->fillFromSettings(RentriSetting::instance()->fresh(), $onboarding);
        $this->step = 2;

        $profile = collect($presetService->operatorProfiles())
            ->firstWhere('key', $this->selectedOperatorPreset);

        $label = $profile['label'] ?? $this->selectedOperatorPreset;

        session()->flash('success', "Preset sandbox «{$label}» applicato. Carica il certificato interoperabilità sandbox e avvia il test connessione.");
    }

    public function testSandboxConnection(
        RentriOnboardingService $onboarding,
        RentriApiClientInterface $apiClient,
    ): void {
        if (! DemoContext::isActive()) {
            $this->addError('health', 'Test sandbox disponibile solo in modalità demo.');

            return;
        }

        $this->testConnection($onboarding, $apiClient);
    }

    public function closeCertModal(): void
    {
        $this->certModalOpen = false;
    }

    public function openCertModal(string $kind): void
    {
        if (! in_array($kind, ['mtls', 'firma'], true)) {
            return;
        }

        $this->certModalKind = $kind;
        $this->certModalOpen = true;
    }

    public function saveNoteOperatore(): void
    {
        if (! DemoContext::isActive()) {
            return;
        }

        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $validated = $this->validate([
            'note_operatore' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings->update(['note_operatore' => $validated['note_operatore'] ?: null]);

        session()->flash('success', 'Note operatore demo salvate.');
    }

    public function enableLiveMode(RentriLiveModeService $liveMode): void
    {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        $liveMode->enable($settings, $userId);
        $this->fillFromSettings(RentriSetting::instance()->fresh(), app(RentriOnboardingService::class));

        session()->flash('success', 'Modalità API live attivata. Eseguire preflight e smoke test prima delle operazioni MASE.');
    }

    public function revertLiveMode(RentriLiveModeService $liveMode): void
    {
        $settings = RentriSetting::instance();
        $this->authorize('update', $settings);

        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        $liveMode->revertToStub($settings, $userId);
        $this->fillFromSettings(RentriSetting::instance()->fresh(), app(RentriOnboardingService::class));

        session()->flash('success', 'Override live disattivato — ripristinata modalità stub da configurazione.');
    }

    public function render(
        RentriOnboardingService $onboarding,
        RentriConnectionStatusService $connectionStatus,
        RentriCertPreviewService $certPreview,
        RentriProdReadinessService $prodReadiness,
        RentriProductionSwitchService $productionSwitch,
        RentriRuntimeModeService $runtimeMode,
        RentriSandboxValidationService $sandboxValidation,
        RentriProductionCertValidationService $productionCertValidation,
    ): View {
        $settings = RentriSetting::instance();

        return $this->segreteriaView(
            'livewire.settings.rentri-settings',
            [
                'settings'         => $settings,
                'onboarding'       => $onboarding,
                'connectionStatus' => $connectionStatus->resolve($settings),
                'certPreviews'     => $certPreview->previews($settings),
                'apiStub'              => $this->resolvesApiStub($settings),
                'firmaStub'            => $runtimeMode->isFirmaStub($settings),
                'rentriApiModeLabel'   => $runtimeMode->apiModeDisplayLabel($settings),
                'rentriApiModeVariant' => $runtimeMode->apiModeDisplayVariant($settings),
                'liveEnabled'          => $runtimeMode->isLiveEnabled($settings),
                'prodChecklist'    => $prodReadiness->checklist($settings),
                'canEnableLive'    => $prodReadiness->canEnableLiveMode($settings),
                'prodSummary'      => $prodReadiness->summary($settings),
                'productionSwitchChecklist' => $productionSwitch->unifiedChecklist($settings),
                'productionSwitchSummary'   => $productionSwitch->summary($settings),
                'productionSwitchReady'       => $productionSwitch->canSwitchToProduction($settings),
                'productionActive'            => $productionSwitch->isProductionActive($settings),
                'productionRunbookPath'         => $productionSwitch->runbookRelativePath(),
                'demoActive'       => DemoContext::isActive(),
                'sessionDemo'      => DemoContext::isSessionDemoActive(),
                'operatorProfiles' => app(DemoRentriPresetService::class)->operatorProfiles(),
                'sandboxPreset'    => app(DemoRentriPresetService::class)->sandboxDefaults($this->selectedOperatorPreset),
                'sandboxValidationPrerequisites' => $sandboxValidation->prerequisiteSteps($settings),
                'sandboxBaseUrl'   => $sandboxValidation->sandboxBaseUrl(),
                'demoapiDocsUrl'   => $sandboxValidation->demoapiDocsUrl(),
                'productionCertValidationPrerequisites' => $productionCertValidation->prerequisiteSteps($settings),
                'productionBaseUrl' => $productionCertValidation->productionBaseUrl(),
                'productionValidationDoc' => $productionCertValidation->validationDocPath(),
                'productionValidationRunbook' => $productionCertValidation->runbookDocPath(),
            ],
            'rentri-impostazioni',
            'Impostazioni RENTRI',
        );
    }

    private function fillFromSettings(RentriSetting $settings, RentriOnboardingService $onboarding): void
    {
        $this->ambiente = $settings->ambiente ?? 'sandbox';
        $this->cf = $settings->cf ?? '';
        $this->cf_operatore = $settings->cf_operatore ?? '';
        $this->piva = $settings->piva ?? '';
        $this->ragione_sociale = $settings->ragione_sociale ?? '';
        $this->num_iscr_sito = $settings->num_iscr_sito ?? '';
        $this->note_operatore = $settings->note_operatore ?? '';
        $this->healthStatus = $settings->last_health_status;
        $this->onboardingComplete = $onboarding->isComplete($settings);
        $this->step = $onboarding->currentStep($settings);
    }

    private function resolvesApiStub(RentriSetting $settings): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        if (DemoContext::isSessionDemoActive()) {
            return blank($settings->cert_path_encrypted);
        }

        return app(RentriRuntimeModeService::class)->isApiStub($settings);
    }
}
