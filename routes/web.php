<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Operatore\OperatoreApiController;
use App\Http\Controllers\Operatore\OperatorePwaManifestController;
use App\Http\Controllers\Operatore\SmontaggioRicambioPhotoController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Admin\ApplicationLogExportController;
use App\Http\Controllers\Admin\AuditExportDownloadController;
use App\Http\Livewire\Admin\AuditIndex;
use App\Http\Livewire\Admin\LogsIndex;
use App\Http\Livewire\Admin\HaStatusPage;
use App\Http\Livewire\Admin\PenTestPrepPage;
use App\Http\Livewire\Admin\SitiIndex;
use App\Http\Livewire\Admin\TrashIndex;
use App\Http\Livewire\Admin\UsersIndex;
use App\Http\Livewire\Shop\ShopCart;
use App\Http\Livewire\Shop\ShopCheckout;
use App\Http\Livewire\Shop\ShopIndex;
use App\Http\Livewire\Shop\ShopProdotto;
use App\Http\Livewire\Admin\RentriStatusWidget;
use App\Http\Livewire\Admin\WafStatusPage;
use App\Http\Livewire\Operatore\Bonifica;
use App\Http\Livewire\Operatore\BonificaWizard;
use App\Http\Livewire\Operatore\Dashboard as OperatoreDashboard;
use App\Http\Livewire\Operatore\Profilo;
use App\Http\Livewire\Operatore\Ricambi;
use App\Http\Livewire\Operatore\Smontaggio;
use App\Http\Livewire\Operatore\SmontaggioWizard;
use App\Http\Livewire\Operatore\VetrinaIndex;
use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaForm;
use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficheIndex;
use App\Http\Livewire\Segreteria\Anagrafiche\AnagraficaShow;
use App\Http\Livewire\Segreteria\CodiciCer\CodiceCerForm;
use App\Http\Livewire\Segreteria\CodiciCer\CodiciCerIndex;
use App\Http\Livewire\Segreteria\Dashboard as SegreteriaDashboard;
use App\Http\Livewire\Segreteria\Fatturazione\FatturaForm;
use App\Http\Livewire\Segreteria\Fatturazione\FattureIndex;
use App\Http\Livewire\Segreteria\Fatturazione\FatturaShow;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceCarrello;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceIndex;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceOrdineShow;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceProdottoShow;
use App\Http\Livewire\Segreteria\Fir\FirBlocchiIndex;
use App\Http\Livewire\Segreteria\Fir\FirIndex;
use App\Http\Livewire\Segreteria\Magazzino\MagazzinoIndex;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentiIndex;
use App\Http\Livewire\Segreteria\Magazzino\SerbatoioShow;
use App\Http\Livewire\Segreteria\Report\BilancioCerIndex;
use App\Http\Livewire\Segreteria\Mud\MudIndex;
use App\Http\Livewire\Segreteria\Mud\MudShow;
use App\Http\Livewire\Segreteria\Rentri;
use App\Http\Livewire\Segreteria\Rentri\RentriTransazioneShow;
use App\Http\Livewire\Segreteria\Rentri\RentriTransazioniIndex;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentoShow;
use App\Http\Livewire\Segreteria\Trasporti\TrasportiIndex;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoForm;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Http\Livewire\Segreteria\Vfu\VfuAccettazioneWizard;
use App\Http\Livewire\Segreteria\Vfu\VfuIndex;
use App\Http\Livewire\Segreteria\Vfu\VfuShow;
use App\Http\Livewire\Settings\NotificationSettingsPage;
use App\Http\Livewire\Settings\RentriSettings;
use App\Http\Livewire\Settings\SecuritySettingsPage;
use App\Http\Livewire\Settings\SettingsHub;
use App\Http\Controllers\Webhooks\StripeEcommerceWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/stripe/ecommerce', StripeEcommerceWebhookController::class)
    ->name('webhooks.stripe.ecommerce');

Route::get('/api/version', VersionController::class)->name('api.version');
Route::get('/health', HealthController::class)->name('health.check');

Route::redirect('/', '/login');

Route::middleware('shop.enabled')->group(function () {
    Route::get('/shop', ShopIndex::class)->name('shop.index');
    Route::get('/shop/carrello', ShopCart::class)->name('shop.carrello');
    Route::get('/shop/checkout', ShopCheckout::class)->name('shop.checkout');
    Route::get('/shop/{prodotto}', ShopProdotto::class)->name('shop.prodotto');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/login/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.challenge');
    Route::post('/login/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:'.config('two-factor.challenge_throttle', '5,1'))
        ->name('two-factor.challenge.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:segreteria|admin|editor', 'demo.scope', 'sito.scope', 'two_factor.enforced'])
    ->prefix('segreteria')
    ->name('segreteria.')
    ->group(function () {
        Route::get('/', SegreteriaDashboard::class)->name('dashboard');
        Route::get('/anagrafiche', AnagraficheIndex::class)->name('anagrafiche');
        Route::get('/anagrafiche/nuovo', AnagraficaForm::class)->name('anagrafiche.create');
        Route::get('/anagrafiche/{anagrafica}', AnagraficaShow::class)->name('anagrafiche.show');
        Route::get('/anagrafiche/{anagrafica}/modifica', AnagraficaForm::class)->name('anagrafiche.edit');
        Route::get('/codici-cer', CodiciCerIndex::class)->name('codici-cer.index');
        Route::get('/codici-cer/nuovo', CodiceCerForm::class)->name('codici-cer.create');
        Route::get('/codici-cer/{codiceCer}/modifica', CodiceCerForm::class)->name('codici-cer.edit');
        Route::get('/vfu', VfuIndex::class)->name('vfu.index');
        Route::get('/vfu/nuovo', VfuAccettazioneWizard::class)->name('vfu.create');
        Route::get('/vfu/{vfuRegistration}', VfuShow::class)->name('vfu.show');
        Route::get('/vfu/{vfuRegistration}/modifica', VfuAccettazioneWizard::class)->name('vfu.edit');
        Route::get('/magazzino', MagazzinoIndex::class)->name('magazzino');
        Route::get('/magazzino/{codiceCer}', SerbatoioShow::class)->name('magazzino.show');
        Route::get('/registro-movimenti', RegistroMovimentiIndex::class)->name('registro-movimenti');
        Route::get('/registro/{movimento}', RegistroMovimentoShow::class)->name('registro.show');
        Route::get('/report/bilancio-cer', BilancioCerIndex::class)->name('report.bilancio-cer');
        Route::get('/trasporti', TrasportiIndex::class)->name('trasporti');
        Route::get('/trasporti/nuovo', TrasportoForm::class)->name('trasporti.create');
        Route::get('/trasporti/{trasporto}', TrasportoShow::class)->name('trasporti.show');
        Route::get('/fir', FirIndex::class)->name('fir');
        Route::get('/fir/blocchi', FirBlocchiIndex::class)->name('fir.blocchi');
        Route::get('/rentri', Rentri::class)->name('rentri');
        Route::get('/rentri/transazioni', RentriTransazioniIndex::class)->name('rentri.transazioni');
        Route::get('/rentri/transazioni/{transazione}', RentriTransazioneShow::class)->name('rentri.transazioni.show');
        Route::get('/fatture', FattureIndex::class)->name('fatture.index');
        Route::get('/fatture/nuova', FatturaForm::class)->name('fatture.create');
        Route::get('/fatture/{fattura}', FatturaShow::class)->name('fatture.show');
        Route::get('/fatture/{fattura}/modifica', FatturaForm::class)->name('fatture.edit');

        Route::get('/ecommerce', EcommerceIndex::class)->name('ecommerce');
        Route::get('/ecommerce/prodotti/{prodotto}', EcommerceProdottoShow::class)->name('ecommerce.prodotti.show');
        Route::get('/ecommerce/carrello', EcommerceCarrello::class)->name('ecommerce.carrello');
        Route::get('/ecommerce/ordini/{ordine}', EcommerceOrdineShow::class)->name('ecommerce.ordini.show');
        Route::get('/mud', MudIndex::class)->name('mud');
        Route::get('/mud/{dichiarazione}', MudShow::class)->name('mud.show');
        // Unified Settings Hub
        Route::get('/impostazioni', SettingsHub::class)->name('impostazioni');
        // Individual settings pages kept for backward compatibility
        Route::get('/impostazioni/rentri', RentriSettings::class)->name('impostazioni.rentri');
        Route::get('/impostazioni/notifiche', NotificationSettingsPage::class)->name('impostazioni.notifiche');
        Route::get('/impostazioni/sicurezza', SecuritySettingsPage::class)->name('impostazioni.sicurezza');
    });

Route::middleware(['auth', 'role:operatore|admin|editor', 'demo.scope', 'sito.scope'])
    ->prefix('operatore')
    ->name('operatore.')
    ->group(function () {
        Route::get('/', OperatoreDashboard::class)->name('dashboard');
        Route::get('/bonifica', Bonifica::class)->name('bonifica');
        Route::get('/bonifica/{vfu}', BonificaWizard::class)->name('bonifica.wizard');
        Route::get('/smontaggio', Smontaggio::class)->name('smontaggio');
        Route::get('/smontaggio/{vfu}', SmontaggioWizard::class)->name('smontaggio.wizard');
        Route::get('/ricambi', Ricambi::class)->name('ricambi');
        Route::get('/vetrina', VetrinaIndex::class)->name('vetrina');
        Route::get('/profilo', Profilo::class)->name('profilo');

        Route::get('/manifest.webmanifest', OperatorePwaManifestController::class)->name('manifest');

        Route::get('/ricambi/{ricambio}/foto', [SmontaggioRicambioPhotoController::class, 'show'])
            ->withoutMiddleware(['role:operatore|admin|editor'])
            ->middleware('role:operatore|segreteria|admin|editor')
            ->name('ricambi.foto');

        Route::prefix('api')->name('api.')->middleware('throttle:60,1')->group(function () {
            Route::get('/bonifica', [OperatoreApiController::class, 'bonifica'])->name('bonifica');
            Route::get('/ricambi', [OperatoreApiController::class, 'ricambi'])->name('ricambi');
            Route::get('/vetrina', [OperatoreApiController::class, 'vetrina'])->name('vetrina');
        });
    });

Route::middleware(['auth', 'role:admin', 'two_factor.enforced'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/utenti', UsersIndex::class)->name('users');
        Route::get('/cestino', TrashIndex::class)->name('trash');
        Route::get('/siti', SitiIndex::class)->name('siti');
        Route::get('/audit', AuditIndex::class)->name('audit');
        Route::get('/logs', LogsIndex::class)->name('logs');
        Route::get('/logs/export', ApplicationLogExportController::class)->name('logs.export');
        Route::get('/pen-test-prep', PenTestPrepPage::class)->name('pen-test-prep');
        Route::get('/waf-status', WafStatusPage::class)->name('waf-status');
        Route::get('/ha-status', HaStatusPage::class)->name('ha-status');
        Route::get('/rentri-status', RentriStatusWidget::class)->name('rentri-status');
        Route::get('/audit/exports/{run}/download', AuditExportDownloadController::class)
            ->name('audit.export.download');
        Route::redirect('/horizon', '/horizon')->name('horizon');
    });
