/* @author Romila Raluca */

let currentUser  = null;
let children     = [];
let activeChildId = null;

/**
 * Calculeaza varsta copilului din data nasterii.
 * @param {string} dob - YYYY-MM-DD
 * @returns {string} - ex: "8 months" sau "2 years 3 months"
 */
function calcAge(dob) {
    const birth = new Date(dob);
    const now   = new Date();
    let years  = now.getFullYear() - birth.getFullYear();
    let months = now.getMonth()    - birth.getMonth();
    if (months < 0) { years--; months += 12; }
    if (years > 0)  return `${years} yr${years > 1 ? 's' : ''} ${months > 0 ? months + ' mo' : ''}`.trim();
    return `${months} month${months !== 1 ? 's' : ''}`;
}

/**
 * Formateaza o data relativ (acum, azi, ieri, data).
 * @param {string} iso
 * @returns {string}
 */
function relativeTime(iso) {
    const d     = new Date(iso);
    const now   = new Date();
    const diff  = (now - d) / 1000;
    if (diff < 60)   return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

/**
 * Escapeaza un string pentru inserare sigura in innerHTML.
 * Previne XSS: &, <, >, ", ' sunt inlocuite cu entitati HTML.
 * @param {*} val
 * @returns {string}
 */
function esc(val) {
    const d = document.createElement('div');
    d.textContent = String(val ?? '');
    return d.innerHTML;
}

/**
 * CSRF helper — fetches token once, caches it in memory.
 * @returns {Promise<string>}
 */
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
 * Returneaza culoarea de fundal a avatar-ului copilului.
 */
function avatarBg(color) {
    const map = {
        c1: '#f0e6e6', c2: '#d8e2dc', c3: '#e9edc9',
        c4: '#b5c9de', c5: '#f4d35e', c6: '#f0c4a0',
    };
    return map[color] || '#f0e6e6';
}

document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('/api/auth/logout', {
        method: 'POST',
        headers: { 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
    });
    window.location.href = '/login';
});

document.getElementById('childSelector').addEventListener('change', (e) => {
    activeChildId = parseInt(e.target.value);
    localStorage.setItem('activeChildId', activeChildId);
    renderDashboard();
});

async function init() {
    // 1. Verifica autentificarea
    const meRes = await fetch('/api/auth/me', { credentials: 'include' });
    if (!meRes.ok) { window.location.href = '/login'; return; }
    currentUser = await meRes.json();

    // Afiseaza link-ul Admin (sus + meniul de jos) doar pentru super-admini
    if (currentUser.is_superadmin) {
        document.getElementById('adminNavLink').style.display = '';
        document.getElementById('adminBottomNav').style.display = '';
    }

    // 2. Fetch copii
    const childRes = await fetch('/api/children', { credentials: 'include' });
    children = childRes.ok ? await childRes.json() : [];

    if (children.length === 0) {
        document.getElementById('noChildrenState').style.display = 'block';
        return;
    }

    // 3. Doar owner si coparent pot accesa dashboard-ul.
    //    Caregiver si viewer → redirect la Gallery (read-only).
    const DASHBOARD_ROLES = ['owner', 'coparent'];
    const firstPermission  = children[0].permission || 'viewer';
    if (!DASHBOARD_ROLES.includes(firstPermission)) {
        window.location.href = '/gallery';
        return;
    }

    // 4. Populeaza selectorul de copii
    const sel = document.getElementById('childSelector');
    children.forEach(c => {
        const opt    = document.createElement('option');
        opt.value    = c.id;
        opt.textContent = `${c.first_name}${c.last_name ? ' ' + c.last_name : ''}`;
        sel.appendChild(opt);
    });
    if (children.length > 1) sel.style.display = 'block';

    const savedId = parseInt(localStorage.getItem('activeChildId'));
    const validSaved = children.find(c => c.id === savedId);
    activeChildId = validSaved ? savedId : children[0].id;
    sel.value = activeChildId;

    // Ascunde butonul de Invite pentru non-owner
    if (firstPermission !== 'owner') {
        document.getElementById('inviteBtn').style.display = 'none';
    }

    document.getElementById('dashboardMain').style.display = 'grid';
    renderDashboard();
}

async function renderDashboard() {
    const child = children.find(c => c.id === activeChildId);
    if (!child) return;

    renderHero(child);
    initAvatarUpload();   // ataseaza click dupa ce DOM e gata
    renderDashboardColumn(child);

    // Fetch date în paralel
    const [feedRes, sleepRes, growthRes] = await Promise.all([
        fetch(`/api/children/${child.id}/feedings?limit=3`, { credentials: 'include' }),
        fetch(`/api/children/${child.id}/sleep?limit=3`,    { credentials: 'include' }),
        fetch(`/api/children/${child.id}/growth?limit=1`,   { credentials: 'include' }),
    ]);

    const feedings = feedRes.ok  ? await feedRes.json()  : [];
    const sleeps   = sleepRes.ok ? await sleepRes.json() : [];
    const growths  = growthRes.ok ? await growthRes.json() : [];

    updateTodayCard(feedings, sleeps);
    updateGrowthCard(growths, child);
    updateScrapNote(feedings, sleeps);
}

function renderHero(child) {
    const bg      = avatarBg(child.avatar_color);
    const name    = child.first_name + (child.last_name ? ' ' + child.last_name : '');
    const age     = calcAge(child.date_of_birth);
    const today   = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

    const safeName    = esc(name);
    const safePhotoUrl = esc(child.photo_url || '');

    const avatarHtml = child.photo_url
        ? `<div class="avatar-photo-wrapper" id="avatarClick" title="Click to change photo">
               <img src="${safePhotoUrl}" alt="${safeName}">
           </div>`
        : `<div class="avatar-placeholder" id="avatarClick" style="background:${bg};" title="Click to add photo">
               ${child.gender === 'F' ? '👧' : child.gender === 'M' ? '👦' : '👶'}
           </div>`;

    document.getElementById('heroColumn').innerHTML = `
        <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp"
               style="display:none;" aria-label="Upload child photo">
        <div class="hero-polaroid-wrapper">
            <div class="washi-tape"></div>
            <div class="polaroid">
                ${avatarHtml}
                <p class="polaroid-caption font-handwritten">${safeName}</p>
            </div>
            <div class="random-scrap torn-edge" id="scrapNote">
                <p class="date font-typewriter">DATE: ${today}</p>
                <p class="note font-handwritten" id="scrapNoteText">Loading today's notes...</p>
            </div>
        </div>
    `;
}

function renderDashboardColumn(child) {
    const name = esc(child.first_name);
    const age  = esc(calcAge(child.date_of_birth));

    document.getElementById('dashboardColumn').innerHTML = `
        <div class="dashboard-header">
            <h1 class="font-modern">${name}'s Journal</h1>
            <p class="font-typewriter">AGE: ${age.toUpperCase()}
               <span style="margin-left:0.8rem; font-size:0.75rem;">EXPORT:
                   <a href="/api/children/${child.id}/export/csv" download style="color:inherit;">CSV</a> ·
                   <a href="/api/children/${child.id}/export/json" download style="color:inherit;">JSON</a>
               </span></p>
        </div>
        <div class="bento-grid">
            <!-- Today card -->
            <div class="scrap-card card-today torn-edge">
                <svg class="paper-clip-svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <h2 class="card-title font-typewriter">TODAY AT A GLANCE</h2>
                <ul class="today-list font-handwritten" id="todayList">
                    <li><div class="skeleton" style="width:80%"></div></li>
                    <li><div class="skeleton" style="width:60%"></div></li>
                    <li><div class="skeleton" style="width:70%"></div></li>
                </ul>
                <button class="btn-add-log font-typewriter">ADD LOG</button>
            </div>

            <!-- Growth card -->
            <div class="scrap-card card-growth torn-edge">
                <svg class="push-pin-svg" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="9" r="6"/>
                    <line x1="12" y1="15" x2="12" y2="24" stroke="currentColor" stroke-width="2"/>
                </svg>
                <h2 class="card-title font-typewriter" style="color: var(--secondary);">GROWTH &amp; STATS</h2>
                <button class="btn-add-log font-typewriter" onclick="event.stopPropagation(); openLogModal('growth')" style="font-size:0.7rem; padding:4px 10px; margin-bottom:0.5rem;">+ MEASURE</button>
                <div id="growthContent">
                    <div class="skeleton" style="width:90%"></div>
                    <div class="skeleton" style="width:70%; margin-top:1rem"></div>
                    <div class="skeleton" style="width:80%; margin-top:1rem"></div>
                </div>
            </div>

            <!-- Tags card -->
            <div class="scrap-card card-tags torn-edge">
                <span class="font-typewriter" style="font-size:0.8rem; opacity:0.6;">RECENT:</span>
                <span class="tag font-handwritten" id="tag1">#Journal</span>
                <span class="tag font-handwritten" id="tag2">#Growth</span>
                <span class="tag font-handwritten" id="tag3">#Feeding</span>
            </div>

            <!-- Small polaroid -->
            <div class="small-polaroid-wrapper">
                <div class="small-polaroid">
                    <div style="width:100%; aspect-ratio:1/1; background:var(--scrap-sage); display:flex; align-items:center; justify-content:center; font-size:3rem;">
                        ${child.gender === 'F' ? '🌸' : '⭐'}
                    </div>
                    <p class="font-handwritten" style="text-align:center; margin-top:10px; font-size:1.2rem;">
                        ${new Date().toLocaleDateString('en-GB', { month: 'short', year: 'numeric' })}
                    </p>
                </div>
            </div>
        </div>
    `;
}

function updateTodayCard(feedings, sleeps) {
    const list = document.getElementById('todayList');
    if (!list) return;

    const items = [];

    feedings.slice(0, 2).forEach(f => {
        const label = f.type === 'breast'  ? `Breastfed ${f.duration_min ? f.duration_min + ' min' : ''}` :
                      f.type === 'bottle'  ? `Bottle: ${f.amount_ml ? f.amount_ml + 'ml' : ''}` :
                      `Solid food${f.food_desc ? ': ' + f.food_desc : ''}`;
        items.push({ icon: 'restaurant', text: label, time: f.fed_at });
    });

    sleeps.slice(0, 1).forEach(s => {
        const dur = s.ended_at
            ? Math.round((new Date(s.ended_at) - new Date(s.started_at)) / 60000) + ' min'
            : 'in progress...';
        items.push({ icon: 'bedtime', text: `${s.type === 'nap' ? 'Nap' : 'Sleep'}: ${dur}`, time: s.started_at });
    });

    if (items.length === 0) {
        list.innerHTML = `<li style="color:var(--text-muted); font-size:1rem; font-style:italic;">No logs yet today. Use ADD LOG to start.</li>`;
        return;
    }

    list.innerHTML = items.map(item => `
        <li>
            <span class="material-symbols-outlined log-icon">${item.icon}</span>
            <div>
                <span>${item.text}</span>
                <span class="log-time font-typewriter">${relativeTime(item.time)}</span>
            </div>
        </li>
    `).join('');
}

function updateGrowthCard(growths, child) {
    const el = document.getElementById('growthContent');
    if (!el) return;

    const age = calcAge(child.date_of_birth);

    if (growths.length === 0) {
        el.innerHTML = `
            <p style="font-family:'Caveat',cursive; font-size:1.2rem; color:var(--text-muted);">
                No measurements yet.
            </p>
            <span class="age-badge font-typewriter">${age}</span>
        `;
        return;
    }

    const g = growths[0];
    const date = new Date(g.measured_at).toLocaleDateString('en-GB', { day:'numeric', month:'short' });

    el.innerHTML = `
        ${g.weight_kg ? `
        <div class="growth-item">
            <div class="growth-row">
                <span class="growth-label font-handwritten">Weight</span>
                <span class="growth-val">${g.weight_kg} kg</span>
            </div>
            <span class="growth-date font-typewriter">${date}</span>
        </div>` : ''}
        ${g.height_cm ? `
        <div class="growth-item">
            <div class="growth-row">
                <span class="growth-label font-handwritten">Height</span>
                <span class="growth-val">${g.height_cm} cm</span>
            </div>
        </div>` : ''}
        ${g.head_cm ? `
        <div class="growth-item">
            <div class="growth-row">
                <span class="growth-label font-handwritten">Head circ.</span>
                <span class="growth-val">${g.head_cm} cm</span>
            </div>
        </div>` : ''}
        <span class="age-badge font-typewriter">${age}</span>
    `;
}

// ── Actualizeaza scrap note ───────────────────────────────────────────────────
function updateScrapNote(feedings, sleeps) {
    const el = document.getElementById('scrapNoteText');
    if (!el) return;

    if (feedings.length > 0) {
        const f = feedings[0];
        if (f.type === 'solids' && f.food_desc) {
            el.textContent = `Tried ${f.food_desc} today!`;
        } else if (f.type === 'breast') {
            el.textContent = `Last breastfed ${relativeTime(f.fed_at)}.`;
        } else {
            el.textContent = `Last fed ${relativeTime(f.fed_at)}.`;
        }
    } else if (sleeps.length > 0) {
        el.textContent = `Last sleep started ${relativeTime(sleeps[0].started_at)}.`;
    } else {
        el.textContent = `Start logging to fill this journal!`;
    }
}

// ── Photo upload pentru avatar copil ─────────────────────────────────────────

/**
 * Ataseaza event listeners pe avatarul din hero column dupa ce e randat.
 * Apelat din renderHero() dupa ce DOM-ul e gata.
 */
function initAvatarUpload() {
    const avatarClick    = document.getElementById('avatarClick');
    const photoFileInput = document.getElementById('photoFileInput');
    if (!avatarClick || !photoFileInput) return;

    // Click pe avatar -> deschide file picker
    avatarClick.addEventListener('click', () => photoFileInput.click());

    // Selectie fisier -> upload imediat
    photoFileInput.addEventListener('change', async () => {
        const file = photoFileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('photo', file);

        // Preview optimist inainte de upload
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarClick.innerHTML = `<img src="${e.target.result}" alt="preview"
                style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);

        try {
            const res = await fetch(`/api/children/${activeChildId}/photo`, {
                method: 'POST',
                headers: { 'X-CSRF-Token': await getCsrfToken() },
                credentials: 'include',
                body: formData,
                // NU setam Content-Type — browser-ul adauga automat boundary pentru multipart
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                alert(err.error || 'Photo upload failed.');
                return;
            }

            const data = await res.json();

            // Actualizeaza photo_url pe obiectul local
            const child = children.find(c => c.id === activeChildId);
            if (child) child.photo_url = data.photo_url;

            // Inlocuieste cu poza finala de pe server
            avatarClick.outerHTML = `
                <div class="avatar-photo-wrapper" id="avatarClick" title="Click to change photo">
                    <img src="${data.photo_url}?t=${Date.now()}" alt="Profile photo">
                </div>`;

            // Re-ataseaza listener pe noul element
            initAvatarUpload();

        } catch (err) {
            console.error('Photo upload error:', err);
            alert('Connection error during upload.');
        }
    });
}

// ── Modal: Add Child ─────────────────────────────────────────────────────────
const addChildModal   = document.getElementById('addChildModal');
const addChildForm    = document.getElementById('addChildForm');
const childError      = document.getElementById('childError');
const childErrorText  = document.getElementById('childErrorText');
const childSubmitBtn  = document.getElementById('childSubmitBtn');

/** Deschide modalul si reseteaza formularul. */
function showAddChildModal() {
    addChildForm.reset();
    childError.style.display = 'none';
    childSubmitBtn.disabled  = false;
    childSubmitBtn.textContent = 'File in Archive';
    addChildModal.classList.add('open');
    document.getElementById('childFirstName').focus();
}

/** Inchide modalul. */
function closeAddChildModal() {
    addChildModal.classList.remove('open');
}

// Buton X
document.getElementById('modalCloseBtn').addEventListener('click', closeAddChildModal);

// Click pe overlay (in afara cardului)
addChildModal.addEventListener('click', (e) => {
    if (e.target === addChildModal) closeAddChildModal();
});

// Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAddChildModal();
});

/**
 * Trimite formularul de adaugare copil la POST /api/children.
 * Dupa succes, actualizeaza lista si re-rendereaza dashboard-ul.
 */
addChildForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    childError.style.display = 'none';

    const body = {
        first_name:    addChildForm.first_name.value.trim(),
        last_name:     addChildForm.last_name.value.trim() || '',
        date_of_birth: addChildForm.date_of_birth.value,
        gender:        addChildForm.gender.value    || null,
        blood_type:    addChildForm.blood_type.value || null,
        notes:         addChildForm.notes.value.trim() || '',
    };

    if (!body.first_name) {
        childErrorText.textContent = 'First name is required.';
        childError.style.display   = 'block';
        return;
    }

    childSubmitBtn.disabled    = true;
    childSubmitBtn.textContent = 'Stamping...';

    try {
        const res  = await fetch('/api/children', {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
            credentials: 'include',
            body:        JSON.stringify(body),
        });

        const data = await res.json();

        if (!res.ok) {
            childErrorText.textContent = data.error || 'Could not save profile.';
            childError.style.display   = 'block';
            childSubmitBtn.disabled    = false;
            childSubmitBtn.textContent = 'File in Archive';
            return;
        }

        // Succes: inchide modalul si reinitializeaza
        closeAddChildModal();
        document.getElementById('noChildrenState').style.display  = 'none';
        document.getElementById('dashboardMain').style.display    = 'grid';

        // Reincarca lista de copii si re-rendereaza
        const childRes = await fetch('/api/children', { credentials: 'include' });
        children = childRes.ok ? await childRes.json() : [];

        // Actualizeaza selectorul
        const sel = document.getElementById('childSelector');
        sel.innerHTML = '';
        children.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.first_name}${c.last_name ? ' ' + c.last_name : ''}`;
            sel.appendChild(opt);
        });
        if (children.length > 1) sel.style.display = 'block';

        // Seteaza copilul nou creat ca activ
        activeChildId = data.id;
        sel.value     = data.id;
        renderDashboard();

    } catch (err) {
        childErrorText.textContent = 'Connection error. Please try again.';
        childError.style.display   = 'block';
        childSubmitBtn.disabled    = false;
        childSubmitBtn.textContent = 'File in Archive';
    }
});

// ── Add Log Modal ─────────────────────────────────────────────────────────────

const addLogModal = document.getElementById('addLogModal');

/** Afiseaza un toast rapid de confirmare. */
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

/** Deschide modalul de log si seteaza datele implicite la momentul curent. */
function openLogModal(tab = 'feeding') {
    document.getElementById('logError').style.display = 'none';

    // Reseteaza toate formularele
    ['feedingForm', 'sleepForm', 'growthForm'].forEach(id => {
        document.getElementById(id).reset();
        document.getElementById(id).querySelector('[type=submit]').disabled = false;
    });

    // Seteaza data/ora implicita la "acum" si blocheaza viitorul
    const nowLocal = () => {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    };
    const nowStr   = nowLocal();
    const todayStr = new Date().toISOString().slice(0, 10);

    document.getElementById('feedAt').value      = nowStr;
    document.getElementById('feedAt').max        = nowStr;
    document.getElementById('sleepStart').value  = nowStr;
    document.getElementById('sleepStart').max    = nowStr;
    document.getElementById('sleepEnd').max      = nowStr;
    document.getElementById('growthDate').value  = todayStr;
    document.getElementById('growthDate').max    = todayStr;

    // Activeaza tab-ul cerut
    switchLogTab(tab);
    updateFeedingFields();

    addLogModal.classList.add('open');
}

/** Schimba tab-ul activ in modalul de log. */
function switchLogTab(tab) {
    document.querySelectorAll('.log-tab').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });
    document.querySelectorAll('.log-panel').forEach(panel => {
        panel.classList.toggle('active', panel.id === `panel-${tab}`);
    });
}

/** Arata/ascunde campurile de feeding in functie de tip. */
function updateFeedingFields() {
    const type = document.getElementById('feedType').value;
    document.getElementById('fieldDuration').style.display = type === 'breast'  ? '' : 'none';
    document.getElementById('fieldAmount').style.display   = type === 'bottle'  ? '' : 'none';
    document.getElementById('fieldFood').style.display     = type === 'solids'  ? '' : 'none';
}

// Tab clicks
document.querySelectorAll('.log-tab').forEach(btn => {
    btn.addEventListener('click', () => switchLogTab(btn.dataset.tab));
});

// Feeding type change
document.getElementById('feedType').addEventListener('change', updateFeedingFields);

// Close
document.getElementById('logModalClose').addEventListener('click', () => addLogModal.classList.remove('open'));
addLogModal.addEventListener('click', e => { if (e.target === addLogModal) addLogModal.classList.remove('open'); });

// Wire ADD LOG button (rendered dynamically, folosim delegare pe dashboardColumn)
document.getElementById('dashboardColumn').addEventListener('click', e => {
    if (e.target.closest('.btn-add-log')) openLogModal('feeding');
});

// Wire Growth card — click pe "No measurements yet" deschide tab Growth
document.getElementById('dashboardColumn').addEventListener('click', e => {
    if (e.target.closest('.age-badge') || e.target.closest('#growthContent')) {
        // nu facem nimic la click pe growth card in sine
    }
});

// ── Feeding submit ──────────────────────────────────────────────────────────
document.getElementById('feedingForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('feedSubmit');
    btn.disabled = true; btn.textContent = 'Logging...';
    document.getElementById('logError').style.display = 'none';

    const type = document.getElementById('feedType').value;
    const body = {
        type,
        fed_at:       document.getElementById('feedAt').value,
        duration_min: type === 'breast' ? (parseInt(document.getElementById('feedDuration').value) || null) : null,
        amount_ml:    type === 'bottle' ? (parseInt(document.getElementById('feedAmount').value)   || null) : null,
        food_desc:    type === 'solids' ? (document.getElementById('feedFood').value.trim() || null) : null,
        notes:        document.getElementById('feedNotes').value.trim() || null,
    };

    const res = await fetch(`/api/children/${activeChildId}/feedings`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include', body: JSON.stringify(body),
    });

    btn.disabled = false; btn.textContent = 'Log Feeding';

    if (!res.ok) {
        const d = await res.json();
        document.getElementById('logErrorText').textContent = d.error || 'Could not save.';
        document.getElementById('logError').style.display = 'block';
        return;
    }

    addLogModal.classList.remove('open');
    showToast('🍼 Feeding logged!');
    renderDashboard(); // reincarca cardul Today
});

// ── Sleep submit ────────────────────────────────────────────────────────────
document.getElementById('sleepForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('sleepSubmit');
    btn.disabled = true; btn.textContent = 'Logging...';
    document.getElementById('logError').style.display = 'none';

    const endVal = document.getElementById('sleepEnd').value;
    const body = {
        type:       document.getElementById('sleepType').value,
        started_at: document.getElementById('sleepStart').value,
        ended_at:   endVal || null,
        notes:      document.getElementById('sleepNotes').value.trim() || null,
    };

    if (!body.started_at) {
        document.getElementById('logErrorText').textContent = 'Start time is required.';
        document.getElementById('logError').style.display = 'block';
        btn.disabled = false; btn.textContent = 'Log Sleep';
        return;
    }

    const res = await fetch(`/api/children/${activeChildId}/sleep`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include', body: JSON.stringify(body),
    });

    btn.disabled = false; btn.textContent = 'Log Sleep';

    if (!res.ok) {
        const d = await res.json();
        document.getElementById('logErrorText').textContent = d.error || 'Could not save.';
        document.getElementById('logError').style.display = 'block';
        return;
    }

    addLogModal.classList.remove('open');
    showToast('😴 Sleep logged!');
    renderDashboard();
});

// ── Growth submit ───────────────────────────────────────────────────────────
document.getElementById('growthForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('growthSubmit');
    btn.disabled = true; btn.textContent = 'Saving...';
    document.getElementById('logError').style.display = 'none';

    const body = {
        measured_at: document.getElementById('growthDate').value,
        weight_kg:   parseFloat(document.getElementById('growthWeight').value) || null,
        height_cm:   parseFloat(document.getElementById('growthHeight').value) || null,
        head_cm:     parseFloat(document.getElementById('growthHead').value)   || null,
    };

    if (!body.measured_at) {
        document.getElementById('logErrorText').textContent = 'Date is required.';
        document.getElementById('logError').style.display = 'block';
        btn.disabled = false; btn.textContent = 'Log Measurement';
        return;
    }
    if (!body.weight_kg && !body.height_cm && !body.head_cm) {
        document.getElementById('logErrorText').textContent = 'Enter at least one measurement.';
        document.getElementById('logError').style.display = 'block';
        btn.disabled = false; btn.textContent = 'Log Measurement';
        return;
    }

    const res = await fetch(`/api/children/${activeChildId}/growth`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include', body: JSON.stringify(body),
    });

    btn.disabled = false; btn.textContent = 'Log Measurement';

    if (!res.ok) {
        const d = await res.json();
        document.getElementById('logErrorText').textContent = d.error || 'Could not save.';
        document.getElementById('logError').style.display = 'block';
        return;
    }

    addLogModal.classList.remove('open');
    showToast('📏 Measurement saved!');
    renderDashboard();
});

// ── Ink effect on click ───────────────────────────────────────────────────────
document.addEventListener('click', (e) => {
    if (e.target.closest('button, a, select')) return;
    const ink = document.createElement('div');
    ink.style.cssText = `position:absolute;left:${e.pageX-10}px;top:${e.pageY-10}px;
        width:20px;height:20px;background:rgba(50,23,22,0.05);border-radius:50%;
        pointer-events:none;z-index:100;transform:scale(${Math.random()*2+0.5})`;
    document.body.appendChild(ink);
    setTimeout(() => {
        ink.style.opacity = '0';
        ink.style.transition = 'opacity 1s ease';
        setTimeout(() => ink.remove(), 1000);
    }, 2000);
});

// FAB -> add log daca exista copii, add child daca nu
document.querySelector('.fab').addEventListener('click', () => {
    showAddChildModal();
});

// ── Invite Modal ──────────────────────────────────────────────────────────────
const inviteModal     = document.getElementById('inviteModal');
const inviteForm      = document.getElementById('inviteForm');
const inviteError     = document.getElementById('inviteError');
const inviteErrorText = document.getElementById('inviteErrorText');
const inviteSubmitBtn = document.getElementById('inviteSubmitBtn');
const inviteLinkBox   = document.getElementById('inviteLinkBox');
const inviteLinkValue = document.getElementById('inviteLinkValue');

/** Deschide modalul de invitatie si reseteaza starea. */
function showInviteModal() {
    if (!activeChildId) {
        alert('Please select a child first.');
        return;
    }
    inviteForm.reset();
    inviteError.style.display  = 'none';
    inviteLinkBox.style.display = 'none';
    inviteSubmitBtn.disabled   = false;
    inviteSubmitBtn.textContent = 'Generate Invite Link';
    inviteModal.classList.add('open');
}

/** Inchide modalul de invitatie. */
function closeInviteModal() {
    inviteModal.classList.remove('open');
}

document.getElementById('inviteBtn').addEventListener('click', showInviteModal);
document.getElementById('inviteModalCloseBtn').addEventListener('click', closeInviteModal);
inviteModal.addEventListener('click', (e) => { if (e.target === inviteModal) closeInviteModal(); });

/**
 * Trimite cererea de generare invite la POST /api/children/{id}/invites.
 * Afiseaza linkul generat pe care utilizatorul il poate copia.
 */
inviteForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    inviteError.style.display   = 'none';
    inviteLinkBox.style.display = 'none';

    const email      = document.getElementById('inviteEmail').value.trim();
    const permission = document.getElementById('inviteRole').value;

    inviteSubmitBtn.disabled    = true;
    inviteSubmitBtn.textContent = 'Stamping...';

    try {
        const res = await fetch(`/api/children/${activeChildId}/invites`, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
            credentials: 'include',
            body:        JSON.stringify({ email: email || null, permission }),
        });

        const data = await res.json();

        if (!res.ok) {
            inviteErrorText.textContent = data.error || 'Could not generate invite.';
            inviteError.style.display   = 'block';
            inviteSubmitBtn.disabled    = false;
            inviteSubmitBtn.textContent = 'Generate Invite Link';
            return;
        }

        // Afisam linkul generat
        const link = `${window.location.origin}/join?token=${data.token}`;
        inviteLinkValue.textContent  = link;
        inviteLinkBox.style.display  = 'block';
        inviteSubmitBtn.textContent  = 'Link Generated!';

    } catch (err) {
        inviteErrorText.textContent = 'Connection error. Please try again.';
        inviteError.style.display   = 'block';
        inviteSubmitBtn.disabled    = false;
        inviteSubmitBtn.textContent = 'Generate Invite Link';
    }
});

/** Copiaza linkul in clipboard. */
document.getElementById('inviteCopyBtn').addEventListener('click', async () => {
    const link = inviteLinkValue.textContent;
    try {
        await navigator.clipboard.writeText(link);
        const btn = document.getElementById('inviteCopyBtn');
        btn.textContent = '✓ Copied!';
        setTimeout(() => {
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:0.9rem;vertical-align:middle;">content_copy</span> Copy link';
        }, 2000);
    } catch {
        // Fallback pentru browsere fara clipboard API
        const ta = document.createElement('textarea');
        ta.value = link;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
    }
});

init();
