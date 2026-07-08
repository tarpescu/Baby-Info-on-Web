/* @author Tarpescu Sergiu */
let currentUser = null;

/** Escapeaza text pentru a-l insera in siguranta in HTML. */
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
}

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

/** Toast scurt (mesaj de succes/eroare). */
function toast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.toggle('err', isError);
    t.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => t.classList.remove('show'), 2600);
}

/** Converteste bytes -> MB sau GB (peste 1024 MB). */
function formatBytes(bytes) {
    const mb = (bytes || 0) / (1024 * 1024);
    if (mb >= 1024) return (mb / 1024).toFixed(2) + ' GB';
    return mb.toFixed(2) + ' MB';
}

/* ── 1. Statistici ── */
async function loadStats() {
    const grid = document.getElementById('statsGrid');
    try {
        const res = await fetch('/api/admin/stats', { credentials: 'include' });
        if (!res.ok) throw new Error('stats');
        const s = await res.json();
        const cards = [
            { icon: 'group',       value: s.users?.total ?? 0, label: 'Utilizatori' },
            { icon: 'diversity_3', value: s.families ?? 0,      label: 'Familii' },
            { icon: 'child_care',  value: s.children ?? 0,      label: 'Copii' },
            { icon: 'photo_library', value: s.media ?? 0,       label: 'Fisiere media' },
        ];
        grid.innerHTML = cards.map(c => `
            <div class="stat-card">
                <span class="material-symbols-outlined stat-icon">${c.icon}</span>
                <div class="stat-value">${c.value}</div>
                <div class="stat-label">${c.label}</div>
            </div>
        `).join('');
    } catch {
        grid.innerHTML = '<p class="empty">Nu am putut incarca statisticile.</p>';
    }
}

/* ── 2. Stocare ── */
async function loadStorage() {
    const card = document.getElementById('storageCard');
    try {
        const res = await fetch('/api/admin/storage', { credentials: 'include' });
        if (!res.ok) throw new Error('storage');
        const data = await res.json();
        const bytes = data.disk?.bytes ?? 0;
        const files = data.disk?.files ?? 0;
        card.innerHTML = `
            <div class="storage-value">${formatBytes(bytes)}</div>
            <div class="storage-sub">${files} fisier${files === 1 ? '' : 'e'} in /uploads (${bytes.toLocaleString('ro-RO')} bytes)</div>
        `;
    } catch {
        card.innerHTML = '<p class="empty">Nu am putut calcula stocarea.</p>';
    }
}

/* ── 3. Utilizatori ── */
function statusCell(user) {
    return user.banned_at
        ? '<span class="badge badge-banned">Banat</span>'
        : '<span class="badge badge-active">Activ</span>';
}

function actionCell(user) {
    if (user.id === currentUser.id) return '<span class="self-note">(contul tau)</span>';
    if (user.is_superadmin)         return '<span class="self-note">(admin)</span>';
    return user.banned_at
        ? `<button class="act-btn unban" data-id="${user.id}" data-action="unban">Unban</button>`
        : `<button class="act-btn ban"   data-id="${user.id}" data-action="ban">Ban</button>`;
}

function rowHtml(user) {
    const fullName = `${esc(user.first_name)} ${esc(user.last_name || '')}`.trim();
    const adminTag = user.is_superadmin ? '<span class="badge badge-admin">admin</span>' : '';
    return `
        <td class="uid">#${user.id}</td>
        <td>${fullName}${adminTag}</td>
        <td>${esc(user.email)}</td>
        <td><span class="role-badge">${esc(user.role)}</span></td>
        <td data-cell="status">${statusCell(user)}</td>
        <td data-cell="action">${actionCell(user)}</td>
    `;
}

async function loadUsers() {
    const body = document.getElementById('usersBody');
    try {
        const res = await fetch('/api/admin/users', { credentials: 'include' });
        if (!res.ok) throw new Error('users');
        const users = await res.json();
        if (!users.length) {
            body.innerHTML = '<tr><td colspan="6" class="empty">Niciun utilizator.</td></tr>';
            return;
        }
        body.innerHTML = '';
        users.forEach(u => {
            const tr = document.createElement('tr');
            tr.dataset.id = u.id;
            tr.innerHTML = rowHtml(u);
            body.appendChild(tr);
        });
    } catch {
        body.innerHTML = '<tr><td colspan="6" class="empty">Nu am putut incarca utilizatorii.</td></tr>';
    }
}

/** Ban/Unban: actualizeaza randul direct in DOM, fara reload. */
async function handleUserAction(btn) {
    const id = parseInt(btn.dataset.id, 10);  // id-ul utilizatorului
    const action = btn.dataset.action; // 'ban' | 'unban'
    btn.disabled = true;

    try {
        const res = await fetch(`/api/admin/users/${id}/${action}`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
            body: JSON.stringify(action === 'ban' ? { reason: 'Banned from admin panel' } : {}),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || 'Actiunea a esuat');
        }

        // Actualizeaza randul: status + buton
        const row = document.querySelector(`tr[data-id="${id}"]`);
        const banned = action === 'ban';
        row.querySelector('[data-cell="status"]').innerHTML = banned
            ? '<span class="badge badge-banned">Banat</span>'
            : '<span class="badge badge-active">Activ</span>';
        row.querySelector('[data-cell="action"]').innerHTML = banned
            ? `<button class="act-btn unban" data-id="${id}" data-action="unban">Unban</button>`
            : `<button class="act-btn ban"   data-id="${id}" data-action="ban">Ban</button>`;

        toast(banned ? 'Utilizator banat.' : 'Utilizator debanat.');
    } catch (e) {
        toast(e.message, true);
        btn.disabled = false;
    }
}

// Delegare de evenimente pentru butoanele de actiune
document.getElementById('usersBody').addEventListener('click', (e) => {
    const btn = e.target.closest('.act-btn');
    if (btn) handleUserAction(btn);
});

document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
    window.location.href = '/login';
});

/* ── Auth guard + init ── */
async function init() {
    const res = await fetch('/api/auth/me', { credentials: 'include' });
    if (!res.ok) { window.location.href = '/login'; return; }

    currentUser = await res.json();
    if (!currentUser.is_superadmin) { window.location.href = '/dashboard'; return; }

    document.getElementById('adminMain').style.display = 'block';

    loadStats();
    loadStorage();
    loadUsers();
}

init();
