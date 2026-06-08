const FOCUSABLE = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

export function initModalFocusTraps(root = document) {
    root.querySelectorAll('[data-seg-modal]').forEach((modal) => {
        if (modal.dataset.segModalInit === '1') {
            return;
        }

        modal.dataset.segModalInit = '1';

        modal.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') {
                return;
            }

            const focusable = modal.querySelectorAll(FOCUSABLE);
            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
    });
}

export function focusModal(modal) {
    if (! modal) {
        return;
    }

    const focusable = modal.querySelectorAll(FOCUSABLE);
    (focusable[0] ?? modal).focus();
}

document.addEventListener('DOMContentLoaded', () => initModalFocusTraps());

document.addEventListener('livewire:navigated', () => initModalFocusTraps());

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', ({ el }) => {
        initModalFocusTraps(el);
        const modal = el.querySelector?.('[data-seg-modal][data-seg-modal-open="1"]');
        if (modal) {
            focusModal(modal);
        }
    });
});
