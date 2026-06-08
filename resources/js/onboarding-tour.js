const STORAGE_KEY = 'seg-onboarding-tour-v1';

const STEPS = [
    {
        selector: '[data-tour="welcome"]',
        title: 'Benvenuto nel CRM RENTRI',
        body: 'Panoramica rapida delle funzioni principali per segreteria e trasmissione registro.',
    },
    {
        selector: '[data-tour="quick-actions"]',
        title: 'Azioni rapide',
        body: 'Collegamenti diretti a VFU, magazzino, trasporti e trasmissione RENTRI.',
    },
    {
        selector: '[data-tour="dashboard-widgets"]',
        title: 'Widget KPI',
        body: 'Trascina le sezioni per riordinare — l\'ordine viene salvato nel browser.',
    },
    {
        selector: '[data-tour="rentri-nav"]',
        title: 'Menu RENTRI',
        body: 'Formulari FIR, trasmissione registro MASE e impostazioni certificati.',
    },
    {
        selector: '[data-tour="rentri-shortcut"]',
        title: 'Trasmissione registro',
        body: 'Da qui avvii l\'invio periodico dei movimenti al registro cronologico nazionale.',
        altSelector: '[data-tour="rentri-trasmissione"]',
    },
];

export function initOnboardingTour() {
    if (localStorage.getItem(STORAGE_KEY) === 'done') {
        return;
    }

    const steps = resolveSteps();
    if (steps.length === 0) {
        return;
    }

    let index = 0;
    let overlay = document.getElementById('seg-onboarding-tour');

    if (! overlay) {
        overlay = buildOverlay();
        document.body.appendChild(overlay);
    }

    const titleEl = overlay.querySelector('[data-tour-title]');
    const bodyEl = overlay.querySelector('[data-tour-body]');
    const stepEl = overlay.querySelector('[data-tour-step]');
    const nextBtn = overlay.querySelector('[data-tour-next]');
    const skipBtn = overlay.querySelector('[data-tour-skip]');
    const spotlight = overlay.querySelector('[data-tour-spotlight]');

    const finish = () => {
        localStorage.setItem(STORAGE_KEY, 'done');
        overlay.hidden = true;
        document.body.classList.remove('seg-tour-active');
    };

    const showStep = (stepIndex) => {
        const step = steps[stepIndex];
        const target = document.querySelector(step.selector);

        if (! target) {
            if (stepIndex < steps.length - 1) {
                showStep(stepIndex + 1);

                return;
            }

            finish();

            return;
        }

        index = stepIndex;
        titleEl.textContent = step.title;
        bodyEl.textContent = step.body;
        stepEl.textContent = `${stepIndex + 1} / ${steps.length}`;
        nextBtn.textContent = stepIndex === steps.length - 1 ? 'Fine' : 'Avanti';
        overlay.hidden = false;
        document.body.classList.add('seg-tour-active');

        const rect = target.getBoundingClientRect();
        spotlight.style.top = `${Math.max(8, rect.top - 8)}px`;
        spotlight.style.left = `${Math.max(8, rect.left - 8)}px`;
        spotlight.style.width = `${rect.width + 16}px`;
        spotlight.style.height = `${rect.height + 16}px`;

        target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };

    nextBtn.addEventListener('click', () => {
        if (index >= steps.length - 1) {
            finish();

            return;
        }

        showStep(index + 1);
    });

    skipBtn.addEventListener('click', finish);

    window.addEventListener('resize', () => showStep(index));
    showStep(0);
}

function resolveSteps() {
    return STEPS.map((step) => {
        const alt = step.altSelector && document.querySelector(step.altSelector)
            ? step.altSelector
            : null;

        return { ...step, selector: alt ?? step.selector };
    }).filter((step) => document.querySelector(step.selector));
}

function buildOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'seg-onboarding-tour';
    overlay.className = 'seg-onboarding-tour';
    overlay.innerHTML = `
        <div class="seg-onboarding-tour-backdrop" aria-hidden="true"></div>
        <div class="seg-onboarding-tour-spotlight" data-tour-spotlight></div>
        <div class="seg-onboarding-tour-card" role="dialog" aria-labelledby="seg-tour-title" aria-describedby="seg-tour-body">
            <p class="seg-onboarding-tour-step" data-tour-step></p>
            <h2 id="seg-tour-title" data-tour-title></h2>
            <p id="seg-tour-body" data-tour-body></p>
            <div class="seg-onboarding-tour-actions">
                <button type="button" class="seg-btn seg-btn-ghost" data-tour-skip>Salta tour</button>
                <button type="button" class="seg-btn seg-btn-primary" data-tour-next>Avanti</button>
            </div>
        </div>
    `;

    return overlay;
}
