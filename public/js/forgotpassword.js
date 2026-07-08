/* @author Romila Raluca */

    // Micro-interaction: Subtle card shift on mouse move
    const card = document.querySelector('.index-card');
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        const rotateX = (y - centerY) / 50;
        const rotateY = (centerX - x) / 50;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) rotate(-1deg)`;
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) rotate(-1deg)`;
    });

    // ── CSRF helper (double-submit cookie + header X-CSRF-Token) ─────────────
    let _csrfToken = null;
    /**
     * Obtine token-ul CSRF de la server (o singura data per pagina).
     * @returns {Promise<string>}
     */
    async function getCsrfToken() {
        if (_csrfToken) return _csrfToken;
        try {
            const r = await fetch('/api/auth/csrf', { credentials: 'include' });
            const d = await r.json();
            _csrfToken = d.token || '';
        } catch { _csrfToken = ''; }
        return _csrfToken;
    }

    // ── Forgot password — reset pe baza de intrebari de securitate ───────────
    const resetForm = document.getElementById('resetForm');
    const resetError = document.getElementById('resetError');

    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        resetError.style.display = 'none';
        const btn = e.target.querySelector('button[type="submit"]');

        const payload = {
            email: document.getElementById('email').value.trim(),
            security_answer_1: document.getElementById('a1').value.trim(),
            security_answer_2: document.getElementById('a2').value.trim(),
            security_answer_3: document.getElementById('a3').value.trim(),
            new_password: document.getElementById('newPassword').value,
        };

        if (payload.new_password.length < 8) {
            resetError.textContent = 'Password must be at least 8 characters.';
            resetError.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Resetting...';

        try {
            const csrf = await getCsrfToken();
            const res = await fetch('/api/auth/reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                credentials: 'include',
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.error || 'Reset failed');
            }

            // Succes -> confirmare + redirect la login
            resetForm.style.display = 'none';
            const confirmDiv = document.createElement('div');
            confirmDiv.style.cssText = 'text-align:center; padding:2rem 0; flex-grow:1; display:flex; flex-direction:column; align-items:center; gap:1.5rem;';
            confirmDiv.innerHTML = `
                <span class="material-symbols-outlined" style="font-size:3rem; color:var(--secondary);">task_alt</span>
                <p class="font-typewriter" style="font-size:1rem; color:var(--text-body);">Password reset! Redirecting to login…</p>
            `;
            resetForm.parentElement.insertBefore(confirmDiv, resetForm);
            setTimeout(() => { window.location.href = '/login'; }, 1500);
        } catch (err) {
            resetError.textContent = err.message;
            resetError.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">lock_reset</span> Reset Password';
        }
    });
