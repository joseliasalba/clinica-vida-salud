document.querySelectorAll('[data-print-receipt]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => window.print());
});