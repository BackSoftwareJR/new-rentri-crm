export function initContextualHelp() {
    document.querySelectorAll('[data-seg-help-open]').forEach((button) => {
        if (button.dataset.segHelpBound === '1') {
            return;
        }

        button.dataset.segHelpBound = '1';
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.segHelpOpen ?? '');

            if (dialog instanceof HTMLDialogElement) {
                dialog.showModal();
            }
        });
    });
}
