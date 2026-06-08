const TABLET_MIN = 768;
const TABLET_MAX = 1024;
const STORAGE_KEY = 'seg-tablet-sidebar-collapsed';

export function initTabletSidebar() {
    const sidebar = document.getElementById('seg-sidebar');
    const tabletToggle = document.getElementById('seg-tablet-nav-toggle');
    const desktopToggle = document.getElementById('seg-toggle');

    if (! sidebar) {
        return;
    }

    const isTablet = () => window.matchMedia(`(min-width: ${TABLET_MIN}px) and (max-width: ${TABLET_MAX}px)`).matches;

    const applyTabletState = () => {
        document.body.classList.toggle('seg-tablet-viewport', isTablet());

        if (! isTablet()) {
            sidebar.classList.remove('seg-tablet-overlay-open');
            document.body.classList.remove('seg-tablet-sidebar-open');

            return;
        }

        const collapsed = readCollapsed();
        sidebar.classList.toggle('collapsed', collapsed);
        sidebar.classList.toggle('seg-tablet-overlay-open', ! collapsed);
        document.body.classList.toggle('seg-tablet-sidebar-open', ! collapsed);

        if (tabletToggle) {
            tabletToggle.hidden = false;
            tabletToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            tabletToggle.setAttribute('aria-label', collapsed ? 'Apri menu laterale' : 'Chiudi menu laterale');
        }
    };

    tabletToggle?.addEventListener('click', () => {
        if (! isTablet()) {
            return;
        }

        const collapsed = ! sidebar.classList.contains('seg-tablet-overlay-open');
        sidebar.classList.toggle('collapsed', collapsed);
        sidebar.classList.toggle('seg-tablet-overlay-open', ! collapsed);
        document.body.classList.toggle('seg-tablet-sidebar-open', ! collapsed);
        persistCollapsed(collapsed);

        tabletToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        tabletToggle.setAttribute('aria-label', collapsed ? 'Apri menu laterale' : 'Chiudi menu laterale');
    });

    desktopToggle?.addEventListener('click', () => {
        if (isTablet()) {
            persistCollapsed(sidebar.classList.contains('collapsed'));
        }
    });

    window.addEventListener('resize', applyTabletState);
    applyTabletState();
}

function readCollapsed() {
    try {
        return localStorage.getItem(STORAGE_KEY) !== '0';
    } catch {
        return true;
    }
}

function persistCollapsed(collapsed) {
    try {
        localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch {
        // ignore
    }
}
