/* @author Romila Raluca */

    // Form input focus styling
    const inputs = document.querySelectorAll('.typewriter-input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            if (input.previousElementSibling) {
                input.previousElementSibling.style.color = 'var(--secondary)';
            }
        });
        input.addEventListener('blur', () => {
            if (!input.value && input.previousElementSibling) {
                input.previousElementSibling.style.color = '';
            }
        });
    });

    // Subtle parallax effect on the paper form
    document.addEventListener('mousemove', (e) => {
        const form = document.getElementById('paper-form');
        const x = (window.innerWidth / 2 - e.pageX) / 80;
        const y = (window.innerHeight / 2 - e.pageY) / 80;
        form.style.transform = `rotate(-1deg) translate(${x}px, ${y}px)`;
    });

    // ── Inregistrare cu fetch() ─────────────────────────────────────────────
    const regForm      = document.getElementById('registerForm');
    const regBtn       = document.getElementById('regBtn');
    const regError     = document.getElementById('regError');
    const regErrorText = document.getElementById('regErrorText');

    /**
     * Afiseaza un mesaj de eroare in caseta vintage.
     * @param {string} msg
     */
    function showRegError(msg) {
        regErrorText.textContent = msg;
        regError.style.display = 'block';
        regError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── CSRF helper ──────────────────────────────────────────────────────────
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

    regForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        regError.style.display = 'none';

        // Imparte "Full Name" in first_name + last_name
        const fullName  = regForm.parent_name.value.trim();
        const parts     = fullName.split(/\s+/);
        const firstName = parts[0] || '';
        const lastName  = parts.slice(1).join(' ') || '.';

        const email    = regForm.email.value.trim();
        const password = regForm.password.value;

        if (password.length < 8) {
            showRegError('Password must be at least 8 characters.');
            return;
        }

        regBtn.disabled    = true;
        regBtn.textContent = 'Filing...';

        try {
            const res = await fetch('/api/auth/register', {
                method:      'POST',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
                credentials: 'include',
                body: JSON.stringify({
                    first_name: firstName,
                    last_name:  lastName,
                    email,
                    password,
                    security_answer_1: regForm.security_answer_1.value.trim(),
                    security_answer_2: regForm.security_answer_2.value.trim(),
                    security_answer_3: regForm.security_answer_3.value.trim(),
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                showRegError(data.error || 'Registration failed. Please try again.');
                regBtn.disabled    = false;
                regBtn.textContent = 'Submit For Archiving';
                return;
            }

            // Succes — redirect la login cu flag de confirmare
            window.location.href = '/login?registered=1';

        } catch (err) {
            showRegError('Connection error. Please try again.');
            regBtn.disabled    = false;
            regBtn.textContent = 'Submit For Archiving';
        }
    });
