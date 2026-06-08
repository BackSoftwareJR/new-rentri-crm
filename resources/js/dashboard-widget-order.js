export function initDashboardWidgetOrder() {
    const container = document.getElementById('seg-dashboard-widgets');
    if (! container) {
        return;
    }

    const storageKey = 'seg-dashboard-widget-order';
    const widgets = [...container.querySelectorAll('[data-widget-id]')];

    if (widgets.length === 0) {
        return;
    }

    const saved = readOrder(storageKey);
    if (saved.length > 0) {
        saved.forEach((id) => {
            const widget = container.querySelector(`[data-widget-id="${id}"]`);
            if (widget) {
                container.appendChild(widget);
            }
        });
    }

    let dragged = null;

    widgets.forEach((widget) => {
        widget.addEventListener('dragstart', (event) => {
            dragged = widget;
            widget.classList.add('is-dragging');
            event.dataTransfer?.setData('text/plain', widget.dataset.widgetId ?? '');
        });

        widget.addEventListener('dragend', () => {
            widget.classList.remove('is-dragging');
            dragged = null;
            persistOrder(container, storageKey);
        });

        widget.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (! dragged || dragged === widget) {
                return;
            }

            const rect = widget.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;
            container.insertBefore(dragged, after ? widget.nextSibling : widget);
        });
    });
}

function readOrder(storageKey) {
    try {
        const raw = localStorage.getItem(storageKey);
        const parsed = raw ? JSON.parse(raw) : [];

        return Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string') : [];
    } catch {
        return [];
    }
}

function persistOrder(container, storageKey) {
    const order = [...container.querySelectorAll('[data-widget-id]')]
        .map((widget) => widget.dataset.widgetId)
        .filter(Boolean);

    localStorage.setItem(storageKey, JSON.stringify(order));
}
