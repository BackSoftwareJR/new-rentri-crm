import './bootstrap';
import '../css/gestionale.css';
import { initModalFocusTraps } from './modal-focus-trap';
import { initDashboardWidgetOrder } from './dashboard-widget-order';
import { initThemeToggle } from './theme-toggle';
import { initOnboardingTour } from './onboarding-tour';
import { initContextualHelp } from './contextual-help';
import { initTabletSidebar } from './tablet-sidebar';

document.addEventListener('DOMContentLoaded', () => {
    initModalFocusTraps();
    initThemeToggle();
    initDashboardWidgetOrder();
    initContextualHelp();
    initOnboardingTour();
    initTabletSidebar();

    const toggle = document.getElementById('seg-toggle');
    const sidebar = document.getElementById('seg-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const collapsed = sidebar.classList.contains('collapsed');
            toggle.setAttribute('aria-label', collapsed ? 'Espandi menu' : 'Comprimi menu');
        });
    }
});

document.addEventListener('livewire:navigated', () => {
    initDashboardWidgetOrder();
    initContextualHelp();
    initOnboardingTour();
    initTabletSidebar();
});
