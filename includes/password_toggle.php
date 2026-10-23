<style>
    .password-toggle-field { position: relative; }
    .password-toggle-field input[type="password"],
    .password-toggle-field input[type="text"] { padding-right: 72px !important; }
    .password-toggle-button {
        position: absolute;
        top: 50%;
        right: 9px;
        transform: translateY(-50%);
        display: inline-flex;
        width: auto;
        min-width: auto;
        margin: 0;
        padding: 5px 7px;
        border: 0;
        border-radius: 6px;
        color: #5364d8;
        background: transparent;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
    }
    .password-toggle-button:hover { color: #3947ad; background: #edf0ff; }
    .password-toggle-button:focus-visible { outline: 2px solid #5364d8; outline-offset: 2px; }
</style>
<script>
(() => {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.dataset.visibilityToggleReady === 'true') return;

        const wrapper = document.createElement('div');
        wrapper.className = 'password-toggle-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle-button';
        button.textContent = 'Show';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.textContent = reveal ? 'Hide' : 'Show';
            button.setAttribute('aria-label', `${reveal ? 'Hide' : 'Show'} password`);
            button.setAttribute('aria-pressed', String(reveal));
        });
        wrapper.appendChild(button);
        input.dataset.visibilityToggleReady = 'true';
    });
})();
</script>
