document.querySelectorAll('[data-verification-resend]').forEach((form) => {
    const button = form.querySelector('button[type="submit"]');
    const message = form.querySelector('[data-verification-wait]');
    const seconds = form.querySelector('[data-verification-seconds]');
    const deadline = Date.now() + Number(form.dataset.retryAfter || 0) * 1000;

    const update = () => {
        const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        button.disabled = remaining > 0;
        message.hidden = remaining === 0;
        seconds.textContent = String(remaining);
        return remaining;
    };

    if (update() > 0) {
        const timer = window.setInterval(() => {
            if (update() === 0) window.clearInterval(timer);
        }, 1000);
    }

    form.addEventListener('submit', () => { button.disabled = true; });
    window.addEventListener('pageshow', update);
});
