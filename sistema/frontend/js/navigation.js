const dialog = document.querySelector('#site-navigation');
const trigger = document.querySelector('[data-navigation-open]');

if (dialog && trigger && typeof dialog.showModal === 'function') {
    trigger.hidden = false;
    let pointerStartedOutside = false;

    const isOutside = (event) => {
        const bounds = dialog.getBoundingClientRect();
        return event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom;
    };

    trigger.addEventListener('click', () => {
        if (!dialog.open) {
            dialog.showModal();
            trigger.setAttribute('aria-expanded', 'true');
            document.documentElement.classList.add('navigation-open');
        }
    });

    dialog.addEventListener('pointerdown', (event) => {
        pointerStartedOutside = event.target === dialog && isOutside(event);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog && pointerStartedOutside && isOutside(event)) {
            dialog.close();
        }
        pointerStartedOutside = false;
    });

    dialog.addEventListener('close', () => {
        trigger.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('navigation-open');
        trigger.focus({ preventScroll: true });
    });
}
