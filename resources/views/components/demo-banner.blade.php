@if (\App\Support\Demo\DemoContext::isActive())
    <div class="demo-banner" role="status" aria-live="polite">
        @if (\App\Support\Demo\DemoContext::isSessionDemoActive())
            <strong>Palestra operativa</strong> — scope demo attivo in sessione.
            API solo sandbox MASE (demoapi.rentri.gov.it). I dati produzione sono nascosti.
        @elseif (\App\Support\Demo\DemoContext::isDeployDemo())
            <strong>Modalità DEMO</strong> — i dati RENTRI/FIR non sono produzione.
            @if (config('demo.rentri.offline_no_http'))
                API MASE disabilitate (RENTRI_DEMO_NO_HTTP).
            @else
                Solo sandbox MASE (demoapi.rentri.gov.it).
            @endif
        @endif
    </div>
@endif
