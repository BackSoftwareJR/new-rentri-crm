@if (\App\Support\Demo\DemoContext::isActive())
    @php
        $liveSandbox = \App\Support\Demo\DemoContext::usesLiveSandboxApi();
        $offline = \App\Support\Demo\DemoContext::offlineNoHttp();
        $settings = \App\Models\RentriSetting::instance();
        $hasCert = filled($settings->cert_path_encrypted);
    @endphp
    <div class="demo-banner" role="status" aria-live="polite">
        @if (\App\Support\Demo\DemoContext::isSessionDemoActive())
            <strong>Palestra operativa</strong> — scope demo attivo in sessione.
            @if ($offline)
                API MASE disabilitate (RENTRI_DEMO_NO_HTTP). Solo fixture locali.
            @elseif ($liveSandbox)
                Collegamento a <strong>demoapi.rentri.gov.it</strong> (mai produzione).
                @if ($hasCert)
                    Certificato sandbox caricato — CER/FIR da RENTRI DEMO.
                @else
                    <strong>Carica certificato sandbox</strong> in Impostazioni RENTRI per integrazione reale.
                @endif
            @else
                API solo sandbox MASE (demoapi.rentri.gov.it). I dati produzione sono nascosti.
            @endif
        @elseif (\App\Support\Demo\DemoContext::isDeployDemo())
            <strong>Modalità DEMO</strong> — i dati RENTRI/FIR non sono produzione.
            @if ($offline)
                API MASE disabilitate (RENTRI_DEMO_NO_HTTP).
            @elseif ($liveSandbox)
                Integrazione live verso <strong>demoapi.rentri.gov.it</strong>.
                @unless ($hasCert)
                    Caricare certificato sandbox MASE per sincronizzare CER e FIR.
                @endunless
            @else
                Solo sandbox MASE (demoapi.rentri.gov.it).
            @endif
        @endif
    </div>
@endif
