/* @author Tarpescu Sergiu */
let currentUser = null;
let children = [];
let activeChildId = null;

const WRITE_ROLES = ['owner', 'coparent'];

/** Etichete lizibile + titlu derivat (coloana title e NOT NULL in DB). */
const TYPE_LABEL = { visit: 'Vizita', vaccine: 'Vaccin', medication: 'Medicatie', allergy: 'Alergie' };
const TYPE_TITLE = { visit: 'Doctor Visit', vaccine: 'Vaccine', medication: 'Medication', allergy: 'Allergy' };

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

function toast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.toggle('err', isError);
    t.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => t.classList.remove('show'), 2600);
}

function formError(msg) {
    const el = document.getElementById('formError');
    el.textContent = msg;
    el.classList.add('show');
}
function clearFormError() {
    document.getElementById('formError').classList.remove('show');
}

/** Formateaza o data DATE (YYYY-MM-DD) intr-un format lizibil. */
function formatDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    if (isNaN(dt)) return esc(d);
    return dt.toLocaleDateString('ro-RO', { day: 'numeric', month: 'long', year: 'numeric' });
}

function recordHtml(r) {
    const type = r.type || 'visit';
    const label = TYPE_LABEL[type] || type;
    const title = esc(r.title || TYPE_TITLE[type] || label);
    const doctor = r.doctor_name
        ? `<div class="record-doctor"><span class="material-symbols-outlined" style="font-size:1rem;">stethoscope</span> ${esc(r.doctor_name)}</div>`
        : '';
    // description = coloana reala pt "notite"
    const notes = r.description
        ? `<div class="record-notes">${esc(r.description)}</div>`
        : '';
    // Link catre documentul PDF atasat (servit din storage/ prin /uploads/)
    const doc = r.document_url
        ? `<a class="record-doc" href="${esc(r.document_url)}" target="_blank" rel="noopener"
              style="display:inline-flex; align-items:center; gap:4px; margin-top:6px; font-size:0.85rem; color:var(--secondary); text-decoration:underline;">
              <span class="material-symbols-outlined" style="font-size:1rem;">picture_as_pdf</span> Vezi documentul</a>`
        : '';
    return `
        <div class="record">
            <div class="record-head">
                <span class="record-title">${title}<span class="type-badge type-${esc(type)}">${esc(label)}</span></span>
                <span class="record-date">${formatDate(r.date_at)}</span>
            </div>
            ${doctor}
            ${notes}
            ${doc}
        </div>
    `;
}

/** Incarca si afiseaza istoricul medical pentru copilul activ. */
async function loadRecords() {
    const list = document.getElementById('recordsList');
    list.innerHTML = '<p class="loading">Se incarca…</p>';
    try {
        const res = await fetch(`/api/children/${activeChildId}/medical?limit=50`, { credentials: 'include' });
        if (!res.ok) throw new Error('load');
        const records = await res.json();
        if (!records.length) {
            list.innerHTML = '<p class="empty">Nicio inregistrare medicala inca.</p>';
            return;
        }
        list.innerHTML = records.map(recordHtml).join('');
    } catch {
        list.innerHTML = '<p class="empty">Nu am putut incarca istoricul medical.</p>';
    }
}

/** Afiseaza/ascunde formularul in functie de permisiunea pe copilul activ. */
function updateFormAccess() {
    const child = children.find(c => c.id === activeChildId);
    const canWrite = child && WRITE_ROLES.includes(child.permission);
    document.getElementById('medicalForm').style.display = canWrite ? '' : 'none';
    document.getElementById('readonlyNote').style.display = canWrite ? 'none' : 'block';
}

/** Trimite formularul -> POST -> adauga in lista fara reload. */
async function submitForm(e) {
    e.preventDefault();
    clearFormError();

    const type = document.getElementById('type').value;
    const dateAt = document.getElementById('date_at').value;
    const doctorName = document.getElementById('doctor_name').value.trim();
    const notes = document.getElementById('notes').value.trim();

    if (!type || !dateAt) {
        formError('Tipul si data sunt obligatorii.');
        return;
    }

    const payload = {
        type,
        title: TYPE_TITLE[type] || type,   // titlu derivat (NOT NULL in DB)
        date_at: dateAt,
        doctor_name: doctorName || null,
        description: notes || null,        // notite -> coloana description
    };

    // Atasament PDF optional — validare client-side (serverul re-valideaza cu finfo)
    const docInput = document.getElementById('document');
    const docFile = docInput.files[0] || null;
    if (docFile && docFile.type !== 'application/pdf') {
        formError('Documentul atasat trebuie sa fie un PDF.');
        return;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    try {
        let res;
        if (docFile) {
            // multipart/form-data — browserul seteaza singur Content-Type cu boundary
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => { if (v !== null) fd.append(k, v); });
            fd.append('document', docFile);
            res = await fetch(`/api/children/${activeChildId}/medical`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'X-CSRF-Token': await getCsrfToken() },
                body: fd,
            });
        } else {
            res = await fetch(`/api/children/${activeChildId}/medical`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
                body: JSON.stringify(payload),
            });
        }
        const data = await res.json().catch(() => ({}));

        if (res.status !== 201) {
            throw new Error(data.error || 'Nu am putut salva inregistrarea.');
        }

        // Adauga in lista fara reload (in capul listei, sortare DESC dupa data)
        const newRecord = { ...payload, id: data.id, document_url: data.document_url || null };
        const list = document.getElementById('recordsList');
        const emptyEl = list.querySelector('.empty');
        if (emptyEl) list.innerHTML = '';
        list.insertAdjacentHTML('afterbegin', recordHtml(newRecord));

        document.getElementById('medicalForm').reset();
        toast('Inregistrare adaugata.');
    } catch (err) {
        formError(err.message);
    } finally {
        btn.disabled = false;
    }
}

/* ── Event listeners ── */
document.getElementById('medicalForm').addEventListener('submit', submitForm);

document.getElementById('childSelector').addEventListener('change', (e) => {
    activeChildId = parseInt(e.target.value, 10);
    localStorage.setItem('activeChildId', activeChildId);
    updateFormAccess();
    loadRecords();
});

document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
    window.location.href = '/login';
});

/* ── Auth guard + init ── */
async function init() {
    // 1. Verifica sesiunea
    const meRes = await fetch('/api/auth/me', { credentials: 'include' });
    if (!meRes.ok) { window.location.href = '/login'; return; }
    currentUser = await meRes.json();

    if (currentUser.is_superadmin) {
        document.getElementById('adminNavLink').style.display = '';
        document.getElementById('adminBottomNav').style.display = '';
    }
    document.getElementById('medicalMain').style.display = 'block';

    // 2. Incarca copiii
    const childRes = await fetch('/api/children', { credentials: 'include' });
    children = childRes.ok ? await childRes.json() : [];

    if (!children.length) {
        document.getElementById('noChildrenState').style.display = 'block';
        return;
    }

    // 3. Selector copil (afisat pentru familiile cu mai multi copii)
    const sel = document.getElementById('childSelector');
    children.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.first_name}${c.last_name ? ' ' + c.last_name : ''}`;
        sel.appendChild(opt);
    });
    if (children.length > 1) sel.style.display = '';

    const savedId = parseInt(localStorage.getItem('activeChildId'));
    const validSaved = children.find(c => c.id === savedId);
    activeChildId = validSaved ? savedId : children[0].id;
    sel.value = activeChildId;

    // 4. Render
    document.getElementById('medicalGrid').style.display = 'grid';
    updateFormAccess();
    loadRecords();
}

init();
