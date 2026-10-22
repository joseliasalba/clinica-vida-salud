document.querySelectorAll('[data-reveal]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.reveal);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'Ocultar' : 'Mostrar';
        button.setAttribute('aria-pressed', String(visible));
    });
});

const password = document.querySelector('[data-password-policy]');
if (password) {
    password.addEventListener('input', () => {
        const length = Array.from(password.value).length;
        document.querySelector('[data-password-feedback]').textContent = length === 0 ? '' : length < 15
            ? `Te faltan ${15 - length} caracteres para el mínimo.` : 'La longitud es adecuada. Evita frases comunes.';
    });
}

document.getElementById('form-errors')?.focus();
