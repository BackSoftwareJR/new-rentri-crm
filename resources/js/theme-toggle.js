const STORAGE_KEY = 'seg-theme';
const THEMES = ['light', 'dark', 'high-contrast'];

export function initThemeToggle() {
    applyStoredTheme();

    document.querySelectorAll('[data-seg-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const current = document.documentElement.dataset.theme || 'light';
            const index = THEMES.indexOf(current);
            const next = THEMES[(index + 1) % THEMES.length];
            setTheme(next);
        });
    });

    document.querySelectorAll('[data-seg-contrast-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const current = document.documentElement.dataset.theme || 'light';
            const next = current === 'high-contrast' ? 'light' : 'high-contrast';
            setTheme(next);
        });
    });
}

export function applyStoredTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (THEMES.includes(stored)) {
            setTheme(stored, false);
        }
    } catch {
        // ignore private browsing
    }
}

function setTheme(theme, persist = true) {
    document.documentElement.dataset.theme = theme;

    document.querySelectorAll('[data-seg-theme-toggle]').forEach((button) => {
        const labels = {
            light: { pressed: 'false', label: 'Attiva tema scuro', title: 'Tema scuro' },
            dark: { pressed: 'true', label: 'Attiva alto contrasto', title: 'Alto contrasto' },
            'high-contrast': { pressed: 'true', label: 'Attiva tema chiaro', title: 'Tema chiaro' },
        };
        const meta = labels[theme] ?? labels.light;
        button.setAttribute('aria-pressed', meta.pressed);
        button.setAttribute('aria-label', meta.label);
        button.setAttribute('title', meta.title);
    });

    document.querySelectorAll('[data-seg-contrast-toggle]').forEach((button) => {
        const isHigh = theme === 'high-contrast';
        button.setAttribute('aria-pressed', isHigh ? 'true' : 'false');
        button.setAttribute('aria-label', isHigh ? 'Disattiva alto contrasto' : 'Attiva alto contrasto');
        button.setAttribute('title', isHigh ? 'Contrasto normale' : 'Alto contrasto');
    });

    if (persist) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            // ignore
        }
    }
}
