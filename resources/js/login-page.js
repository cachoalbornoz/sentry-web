import { bootWhenReady } from './shared/page-boot';

function init() {
    const passwordToggle = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const loginForm = document.querySelector('form[data-login-form]');

    if (!passwordToggle || !passwordInput || !loginForm) return;

    passwordToggle.addEventListener('click', () => {
        passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    });

    loginForm.addEventListener('submit', () => {
        const btn = document.getElementById('login-submit');
        const label = document.getElementById('login-submit-label');
        const loading = document.getElementById('login-submit-loading');
        if (!btn || !label || !loading) return;
        btn.disabled = true;
        label.classList.add('hidden');
        loading.classList.remove('hidden');
        loading.classList.add('inline-flex');
        label.style.display = 'none';
        loading.style.display = 'inline-flex';
    });
}

bootWhenReady('__sentryLoginPageInitialized', init);
