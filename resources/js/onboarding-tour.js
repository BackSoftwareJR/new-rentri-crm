const STORAGE_KEY = 'seg-onboarding-tour-v2';

const STEPS = [
    {
        selector: '[data-tour="quick-actions"]',
        title: 'Azioni rapide',
        body: 'Da qui accedi subito a VFU, magazzino, trasporti e trasmissione RENTRI — le operazioni che usi ogni giorno.',
    },
    {
        selector: '[data-tour="dashboard-widgets"]',
        title: 'Widget KPI',
        body: 'Trascina le sezioni per riordinare la dashboard. L\'ordine viene salvato automaticamente nel browser.',
    },
    {
        selector: '[data-tour="rentri-nav"]',
        title: 'Area RENTRI',
        body: 'Formulari FIR, trasmissione registro MASE e impostazioni certificati operatore.',
    },
    {
        selector: '[data-tour="rentri-shortcut"]',
        title: 'Trasmetti registro',
        body: 'Collegamento diretto per inviare i movimenti al registro cronologico nazionale.',
        altSelector: '[data-tour="rentri-trasmissione"]',
    },
];

let activeHighlight = null;

export function initOnboardingTour() {
    if (localStorage.getItem(STORAGE_KEY) === 'done') {
        return;
    }

    const steps = resolveSteps();
    if (steps.length === 0) {
        return;
    }

    let overlay = document.getElementById('seg-onboarding-tour');

    if (overlay?.dataset.bound === 'true') {
        return;
    }

    if (! overlay) {
        overlay = buildOverlay();
        document.body.appendChild(overlay);
    }

    overlay.dataset.bound = 'true';

    const welcomePanel = overlay.querySelector('[data-tour-welcome]');
    const stepPanel = overlay.querySelector('[data-tour-step-panel]');
    const titleEl = overlay.querySelector('[data-tour-title]');
    const bodyEl = overlay.querySelector('[data-tour-body]');
    const stepEl = overlay.querySelector('[data-tour-step]');
    const progressEl = overlay.querySelector('[data-tour-progress]');
    const nextBtn = overlay.querySelector('[data-tour-next]');
    const backBtn = overlay.querySelector('[data-tour-back]');
    const skipBtn = overlay.querySelector('[data-tour-skip]');
    const closeBtn = overlay.querySelector('[data-tour-close]');
    const startBtn = overlay.querySelector('[data-tour-start]');
    const backdrop = overlay.querySelector('[data-tour-backdrop]');

    let index = 0;

    const clearHighlight = () => {
        if (activeHighlight) {
            activeHighlight.classList.remove('seg-tour-target-active');
            activeHighlight = null;
        }
    };

    const finish = () => {
        localStorage.setItem(STORAGE_KEY, 'done');
        overlay.hidden = true;
        clearHighlight();
        document.body.classList.remove('seg-tour-active');
    };

    const showWelcome = () => {
        welcomePanel.hidden = false;
        stepPanel.hidden = true;
        overlay.hidden = false;
        document.body.classList.add('seg-tour-active');
        clearHighlight();
        startBtn.focus();
    };

    const positionStepCard = (target) => {
        const card = stepPanel;
        const gap = 14;
        const margin = 16;
        const rect = target.getBoundingClientRect();

        card.style.visibility = 'hidden';
        card.hidden = false;

        const cardRect = card.getBoundingClientRect();
        let top = rect.bottom + gap;
        let left = rect.left + (rect.width / 2) - (cardRect.width / 2);
        let placement = 'bottom';

        if (top + cardRect.height > window.innerHeight - margin) {
            top = rect.top - gap - cardRect.height;
            placement = 'top';
        }

        if (top < margin) {
            top = margin;
            left = Math.min(
                window.innerWidth - cardRect.width - margin,
                rect.right + gap
            );
            placement = 'right';
        }

        left = Math.max(margin, Math.min(left, window.innerWidth - cardRect.width - margin));
        top = Math.max(margin, Math.min(top, window.innerHeight - cardRect.height - margin));

        card.style.top = `${top}px`;
        card.style.left = `${left}px`;
        card.dataset.placement = placement;
        card.style.visibility = 'visible';
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
        welcomePanel.hidden = true;
        stepPanel.hidden = false;
        overlay.hidden = false;
        document.body.classList.add('seg-tour-active');

        clearHighlight();
        activeHighlight = target;
        target.classList.add('seg-tour-target-active');

        titleEl.textContent = step.title;
        bodyEl.textContent = step.body;
        stepEl.textContent = `Passo ${stepIndex + 1} di ${steps.length}`;
        progressEl.style.width = `${((stepIndex + 1) / steps.length) * 100}%`;
        nextBtn.textContent = stepIndex === steps.length - 1 ? 'Fine, ho capito' : 'Avanti';
        backBtn.hidden = stepIndex === 0;

        target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

        requestAnimationFrame(() => positionStepCard(target));
    };

    startBtn.addEventListener('click', () => showStep(0));

    nextBtn.addEventListener('click', () => {
        if (index >= steps.length - 1) {
            finish();

            return;
        }

        showStep(index + 1);
    });

    backBtn.addEventListener('click', () => {
        if (index > 0) {
            showStep(index - 1);
        } else {
            showWelcome();
        }
    });

    skipBtn.addEventListener('click', finish);
    closeBtn.addEventListener('click', finish);
    backdrop.addEventListener('click', finish);

    document.addEventListener('keydown', (event) => {
        if (overlay.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            finish();
        }
    });

    window.addEventListener('resize', () => {
        if (! overlay.hidden && ! stepPanel.hidden && activeHighlight) {
            positionStepCard(activeHighlight);
        }
    });

    showWelcome();
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
        <div class="seg-onboarding-tour-backdrop" data-tour-backdrop aria-hidden="true"></div>

        <div class="seg-onboarding-tour-welcome" data-tour-welcome role="dialog" aria-labelledby="seg-tour-welcome-title" aria-describedby="seg-tour-welcome-body">
            <button type="button" class="seg-onboarding-tour-close" data-tour-close aria-label="Chiudi guida">&times;</button>
            <p class="seg-onboarding-tour-eyebrow">Primo accesso</p>
            <h2 id="seg-tour-welcome-title">Benvenuto in RENTRI CRM</h2>
            <p id="seg-tour-welcome-body" class="seg-onboarding-tour-lead">
                Ti guidiamo in pochi passi alle funzioni principali. La dashboard resta visibile: niente è bloccato.
            </p>
            <ul class="seg-onboarding-tour-checklist">
                <li>Azioni rapide per VFU e magazzino</li>
                <li>Widget KPI personalizzabili</li>
                <li>Menu e trasmissione RENTRI</li>
            </ul>
            <div class="seg-onboarding-tour-actions seg-onboarding-tour-actions--welcome">
                <button type="button" class="seg-btn seg-btn-ghost" data-tour-skip>Salta, conosco già il sistema</button>
                <button type="button" class="seg-btn seg-btn-primary" data-tour-start>Inizia il tour (1 min)</button>
            </div>
        </div>

        <div class="seg-onboarding-tour-card" data-tour-step-panel hidden role="dialog" aria-labelledby="seg-tour-title" aria-describedby="seg-tour-body">
            <button type="button" class="seg-onboarding-tour-close" data-tour-close aria-label="Chiudi guida">&times;</button>
            <div class="seg-onboarding-tour-progress-track" aria-hidden="true">
                <div class="seg-onboarding-tour-progress-bar" data-tour-progress></div>
            </div>
            <p class="seg-onboarding-tour-step" data-tour-step></p>
            <h2 id="seg-tour-title" data-tour-title></h2>
            <p id="seg-tour-body" data-tour-body></p>
            <div class="seg-onboarding-tour-actions">
                <button type="button" class="seg-btn seg-btn-ghost" data-tour-back>Indietro</button>
                <button type="button" class="seg-btn seg-btn-ghost" data-tour-skip>Salta</button>
                <button type="button" class="seg-btn seg-btn-primary" data-tour-next>Avanti</button>
            </div>
        </div>
    `;

    return overlay;
}
