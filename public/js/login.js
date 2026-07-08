/* @author Romila Raluca */

    // Micro-interaction: Focus wiggle
    const inputs = document.querySelectorAll('.form-input');
    const card = document.querySelector('.index-card');

    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            card.style.transform = 'scale(1.01) rotate(0deg)';
        });
        input.addEventListener('blur', () => {
            card.style.transform = '';
        });
    });

    // Mesaj de bun-venit dupa inregistrare reusita
    if (new URLSearchParams(window.location.search).get('registered') === '1') {
        const notice = document.createElement('div');
        notice.style.cssText = 'padding:0.6rem 1rem; background:rgba(100,160,100,0.12); border-left:3px solid #5a9a5a; font-family:"Special Elite",cursive; font-size:0.9rem; color:#3d6e3d; margin-bottom:1.5rem;';
        notice.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:4px;">check_circle</span> Account created! Please log in.';
        document.getElementById('loginForm').prepend(notice);
    }

    // ── CSRF helper ─────────────────────────────────────────────────────────
    let _csrfToken = null;
    async function getCsrfToken() {
        if (_csrfToken) return _csrfToken;
        try {
            const r = await fetch('/api/auth/csrf', { credentials: 'include' });
            const d = await r.json();
            _csrfToken = d.token || '';
        } catch { _csrfToken = ''; }
        return _csrfToken;
    }

    // ── Autentificare cu fetch() ────────────────────────────────────────────
    const loginForm = document.getElementById('loginForm');
    const loginBtn  = document.getElementById('loginBtn');
    const errorBox  = document.getElementById('loginError');
    const errorText = document.getElementById('loginErrorText');

    /**
     * Afiseaza un mesaj de eroare in caseta vintage.
     * @param {string} msg
     */
    function showError(msg) {
        errorText.textContent = msg;
        errorBox.style.display = 'block';
        card.style.transform = 'rotate(-1deg)';
        setTimeout(() => { card.style.transform = ''; }, 400);
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorBox.style.display = 'none';

        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        // Stare de incarcare pe sigiliu
        loginBtn.querySelector('.text').textContent    = '...';
        loginBtn.querySelector('.subtext').textContent = 'WAIT';
        loginBtn.disabled = true;

        try {
            const res = await fetch('/api/auth/login', {
                method:      'POST',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
                credentials: 'include',
                body:        JSON.stringify({ email, password }),
            });

            const data = await res.json();

            if (!res.ok) {
                showError(data.error || 'Login failed. Please try again.');
                loginBtn.querySelector('.text').textContent    = 'LOGIN';
                loginBtn.querySelector('.subtext').textContent = 'VERIFIED';
                loginBtn.disabled = false;
                return;
            }

            // Succes — redirect catre dashboard
            loginBtn.querySelector('.text').textContent    = 'OPEN';
            loginBtn.querySelector('.subtext').textContent = 'WELCOME';
            window.location.href = '/dashboard';

        } catch (err) {
            showError('Connection error. Please try again.');
            loginBtn.querySelector('.text').textContent    = 'LOGIN';
            loginBtn.querySelector('.subtext').textContent = 'VERIFIED';
            loginBtn.disabled = false;
        }
    });
