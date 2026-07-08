/* @author Romila Raluca */

    // Mapare permission -> eticheta prietenoasă
    const ROLE_LABELS = {
        owner:    'Owner',
        coparent: 'Co-Parent',
        caregiver:'Caregiver',
        viewer:   'Viewer',
    };

    const loadingState = document.getElementById('loadingState');
    const invalidState = document.getElementById('invalidState');
    const joinState    = document.getElementById('joinState');
    const invalidMsg   = document.getElementById('invalidMsg');
    const errorBox     = document.getElementById('errorBox');
    const errorText    = document.getElementById('errorText');
    const joinBtn      = document.getElementById('joinBtn');

    let inviteToken = '';
    let inviteData  = null;

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

    /**
     * Afiseaza mesaj de eroare in caseta vintage.
     * @param {string} msg
     */
    function showError(msg) {
        errorText.textContent = msg;
        errorBox.style.display = 'block';
    }

    /**
     * Valideaza tokenul de invitatie si populeaza UI-ul.
     */
    async function validateToken() {
        const params = new URLSearchParams(window.location.search);
        inviteToken = params.get('token') || '';

        if (!inviteToken) {
            loadingState.style.display = 'none';
            invalidState.style.display = 'block';
            invalidMsg.textContent = 'No invite token found in URL.';
            return;
        }

        try {
            const res = await fetch(`/api/invite?token=${encodeURIComponent(inviteToken)}`, {
                credentials: 'include',
            });

            loadingState.style.display = 'none';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                invalidState.style.display = 'block';
                invalidMsg.textContent = data.error || 'This invite link is invalid or has expired.';
                return;
            }

            inviteData = await res.json();

            // Populam bannerul
            document.getElementById('childName').textContent = inviteData.child_name || '—';
            document.getElementById('roleBadge').textContent =
                ROLE_LABELS[inviteData.permission] ?? inviteData.permission;

            joinState.style.display = 'block';

        } catch (err) {
            loadingState.style.display = 'none';
            invalidState.style.display = 'block';
            invalidMsg.textContent = 'Connection error. Please try again.';
        }
    }

    /**
     * Trimite formularul de inregistrare cu tokenul de invitatie.
     */
    document.getElementById('joinForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        errorBox.style.display = 'none';

        const payload = {
            first_name:       document.getElementById('firstName').value.trim(),
            last_name:        document.getElementById('lastName').value.trim(),
            email:            document.getElementById('email').value.trim(),
            password:         document.getElementById('password').value,
            invite_token:     inviteToken,
            security_answer_1: document.getElementById('secAnswer1').value.trim(),
            security_answer_2: document.getElementById('secAnswer2').value.trim(),
            security_answer_3: document.getElementById('secAnswer3').value.trim(),
        };

        joinBtn.querySelector('.text').textContent    = '...';
        joinBtn.querySelector('.subtext').textContent = 'WAIT';
        joinBtn.disabled = true;

        try {
            const res = await fetch('/api/auth/register', {
                method:      'POST',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
                credentials: 'include',
                body:        JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok) {
                showError(data.error || 'Registration failed. Please try again.');
                joinBtn.querySelector('.text').textContent    = 'JOIN';
                joinBtn.querySelector('.subtext').textContent = 'ACCEPTED';
                joinBtn.disabled = false;
                return;
            }

            // Succes — logam automat si redirectam
            joinBtn.querySelector('.text').textContent    = 'DONE!';
            joinBtn.querySelector('.subtext').textContent = 'WELCOME';

            // Auto-login
            const loginRes = await fetch('/api/auth/login', {
                method:      'POST',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
                credentials: 'include',
                body:        JSON.stringify({ email: payload.email, password: payload.password }),
            });

            if (loginRes.ok) {
                window.location.href = '/dashboard';
            } else {
                // Login esuat dar contul exista — trimitem la login cu banner
                window.location.href = '/login?registered=1';
            }

        } catch (err) {
            showError('Connection error. Please try again.');
            joinBtn.querySelector('.text').textContent    = 'JOIN';
            joinBtn.querySelector('.subtext').textContent = 'ACCEPTED';
            joinBtn.disabled = false;
        }
    });

    // Pornire
    validateToken();
