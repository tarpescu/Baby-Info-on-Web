/* @author Romila Raluca */

let currentUser      = null;
let children         = [];
let activeChildId    = null;
let allMoments       = [];
let activeFilter     = 'all';
let activeTagFilter  = 'all';
let activeMomentId   = null;
let userCanWrite     = false;   // false pentru viewer/caregiver

// Reaction emoji map
const REACTIONS = [
    { type: 'heart', emoji: '❤️', label: 'Love' },
    { type: 'star',  emoji: '⭐', label: 'Star' },
    { type: 'laugh', emoji: '😂', label: 'Haha' },
];

// Placeholder colors per type
const TYPE_COLORS = {
    photo:     '#f0e6e6',
    video:     '#d8e2dc',
    audio:     '#e9edc9',
    text:      '#e6e0ef',
    note:      '#eee7db',
};

const TYPE_ICONS = {
    photo:     'photo_camera',
    video:     'videocam',
    audio:     'mic',
    text:      'description',
    note:      'edit_note',
};

/**
 * Escapeaza un string pentru inserare sigura in innerHTML.
 * Previne XSS prin inlocuirea caracterelor speciale HTML cu entitati.
 * @param {*} val
 * @returns {string}
 */
function esc(val) {
    const d = document.createElement('div');
    d.textContent = String(val ?? '');
    return d.innerHTML;
}

/**
 * Formateaza data in stil vintage.
 * @param {string} iso
 * @returns {string}
 */
function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
}

/**
 * Grupeaza momentele pe luni.
 * @param {Array} moments
 * @returns {Object} { "June 2026": [...] }
 */
function groupByMonth(moments) {
    const groups = {};
    moments.forEach(m => {
        const d = new Date(m.happened_at);
        const key = d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        if (!groups[key]) groups[key] = [];
        groups[key].push(m);
    });
    return groups;
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
 * Construieste raftul decorativ cu initialele copilului.
 * @param {Object} child
 */
function buildShelf(child) {
    const board = document.getElementById('shelfBoard');
    const name  = child.first_name || 'B';
    const initials = (child.first_name?.[0] || '') + (child.last_name?.[0] || '');
    const blockColors = ['#9e422c', '#504443', '#d8c4b6', '#8a6a5a', '#c4a882'];

    // Golim raftul dar pastram butonul de upload
    const uploadBtn = document.getElementById('shelfUploadBtn');
    board.innerHTML = '';

    // Blocuri cu literele copilului
    [...initials].forEach((letter, i) => {
        const block = document.createElement('div');
        block.className = 'shelf-item';
        block.innerHTML = `
            <div class="shelf-block" style="background:${blockColors[i % blockColors.length]};">
                ${letter.toUpperCase()}
            </div>
        `;
        board.appendChild(block);
    });

    // Carte decorativa
    const book = document.createElement('div');
    book.className = 'shelf-item';
    book.innerHTML = `<div class="shelf-book" style="background:#6b4f3a; height:64px;">MEMORIES</div>`;
    board.appendChild(book);

    // Ursulet
    const bear = document.createElement('div');
    bear.className = 'shelf-item';
    bear.innerHTML = `<div class="shelf-bear">🧸</div>`;
    board.appendChild(bear);

    // Foto frame
    const frame = document.createElement('div');
    frame.className = 'shelf-item';
    frame.innerHTML = `<div class="shelf-photo-frame">📷</div>`;
    board.appendChild(frame);

    // Inca o carte
    const book2 = document.createElement('div');
    book2.className = 'shelf-item';
    book2.innerHTML = `<div class="shelf-book" style="background:#3d6e3d; height:76px; width:24px;">JOURNAL</div>`;
    board.appendChild(book2);

    // Butonul de upload la final
    board.appendChild(uploadBtn);
}

/**
 * Parseaza sirul de tag-uri "a,b,c" intr-un array curat.
 * @param {string} raw
 * @returns {string[]}
 */
function parseTags(raw) {
    return String(raw || '').split(',').map(t => t.trim()).filter(Boolean);
}

/**
 * Construieste chip-urile de filtrare pe tag-uri din momentele incarcate.
 * @param {Array} moments
 */
function renderTagChips(moments) {
    const wrap  = document.getElementById('tagChips');
    const label = document.getElementById('tagFilterLabel');
    const allTags = [...new Set(moments.flatMap(m => parseTags(m.tags)))].sort();

    if (allTags.length === 0) {
        wrap.innerHTML = '';
        label.style.display = 'none';
        activeTagFilter = 'all';
        return;
    }
    label.style.display = '';

    // Daca tagul activ a disparut (ex. dupa stergere), revenim la "all"
    if (activeTagFilter !== 'all' && !allTags.includes(activeTagFilter)) {
        activeTagFilter = 'all';
    }

    wrap.innerHTML = ['all', ...allTags].map(tag => `
        <button class="filter-chip tag-chip ${activeTagFilter === tag ? 'active' : ''}" data-tag="${esc(tag)}">
            ${tag === 'all' ? 'All' : '# ' + esc(tag)}
        </button>
    `).join('');

    wrap.querySelectorAll('.tag-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            activeTagFilter = chip.dataset.tag;
            renderTagChips(allMoments);
            renderGallery(allMoments);
        });
    });
}

/**
 * Randeaza galeria grupata pe luni.
 * @param {Array} moments
 */
function renderGallery(moments) {
    const content = document.getElementById('galleryContent');
    content.innerHTML = '';

    // Viewer vede doar momentele marcate ca shared de parinti
    const viewable = userCanWrite
        ? moments
        : moments.filter(m => m.is_shared);

    const byType = activeFilter === 'all'
        ? viewable
        : viewable.filter(m => m.type === activeFilter);

    const byTag = activeTagFilter === 'all'
        ? byType
        : byType.filter(m => parseTags(m.tags).includes(activeTagFilter));

    // Filtru pe interval de date (cerinta: "filterable by category and date range")
    const fromVal = document.getElementById('dateFrom').value;
    const toVal   = document.getElementById('dateTo').value;
    const filtered = byTag.filter(m => {
        const d = String(m.happened_at || '').slice(0, 10);
        if (fromVal && d < fromVal) return false;
        if (toVal && d > toVal) return false;
        return true;
    });

    if (filtered.length === 0) {
        const emptyMsg = userCanWrite
            ? 'Click the + button to add your first moment.'
            : 'No shared moments yet. The family will share memories with you here.';
        content.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-outlined big-icon">photo_library</span>
                <h2 class="font-headline">No memories here yet</h2>
                <p>${emptyMsg}</p>
            </div>
        `;
        return;
    }

    const groups = groupByMonth(filtered);

    Object.entries(groups).forEach(([month, items]) => {
        const section = document.createElement('section');
        section.className = 'month-group';
        section.innerHTML = `
            <div class="month-header">
                <h2 class="month-title font-headline">${month}</h2>
                <div class="month-line"></div>
                <span class="month-count">${items.length} ${items.length === 1 ? 'memory' : 'memories'}</span>
            </div>
            <div class="polaroid-grid" id="grid-${month.replace(/\s/g,'-')}"></div>
        `;
        content.appendChild(section);

        const grid = section.querySelector('.polaroid-grid');
        items.forEach(m => {
            grid.appendChild(buildPolaroid(m));
        });
    });
}

/**
 * Construieste un card de tip polaroid pentru un moment.
 * @param {Object} moment
 * @returns {HTMLElement}
 */
function buildPolaroid(moment) {
    const wrap = document.createElement('div');
    wrap.className = 'polaroid-wrap';
    wrap.dataset.id = moment.id;

    // Reactii (ink stamps)
    const reactionEmojis = (moment.reactions || []).map(r => {
        const re = REACTIONS.find(x => x.type === r.emoji_type);
        if (!re || !r.count) return '';
        return `<div class="ink-stamp">${re.emoji}<span class="stamp-count">${r.count > 1 ? r.count : ''}</span></div>`;
    }).join('');

    // Placeholder color
    const placeholderColor = TYPE_COLORS[moment.type] || '#f4ede1';
    const placeholderIcon  = TYPE_ICONS[moment.type]  || 'image';

    const dateStr = formatDate(moment.happened_at);

    // Zona de media: poza/video reala daca exista, altfel placeholder
    const hasVisualMedia = moment.media_url && (moment.type === 'photo' || moment.type === 'video');
    let mediaZone;
    if (moment.media_url && moment.type === 'photo') {
        mediaZone = `<img src="${esc(moment.media_url)}" alt="${esc(moment.title)}"
                style="width:100%; height:100%; object-fit:cover; display:block;"
                onerror="this.parentElement.innerHTML='<span class=\\'material-symbols-outlined big-icon\\'>${placeholderIcon}</span><span class=\\'type-label\\'>${esc(moment.type)}</span>'"/>`;
    } else if (moment.media_url && moment.type === 'video') {
        mediaZone = `<video src="${esc(moment.media_url)}" muted preload="metadata"
                style="width:100%; height:100%; object-fit:cover; display:block;"></video>`;
    } else {
        mediaZone = `<span class="material-symbols-outlined big-icon">${placeholderIcon}</span>
           <span class="type-label">${esc(moment.type)}</span>`;
    }

    wrap.innerHTML = `
        <div class="polaroid">
            <div class="polaroid-tape"></div>
            ${moment.is_pinned ? '<span class="material-symbols-outlined pin-star">star</span>' : ''}

            <div class="polaroid-placeholder" style="background:${hasVisualMedia ? '#fff' : placeholderColor}; padding:${hasVisualMedia ? '0' : ''};">
                ${mediaZone}
            </div>

            <p class="polaroid-caption font-handwritten" title="${esc(moment.title)}">${esc(moment.title)}</p>

            ${parseTags(moment.tags).length ? `
                <div style="display:flex; flex-wrap:wrap; gap:3px; margin-top:2px;">
                    ${parseTags(moment.tags).slice(0, 3).map(t => `
                        <span style="font-family:'Special Elite',cursive; font-size:0.6rem; color:var(--text-muted);
                              border:1px dotted var(--outline); border-radius:8px; padding:0 6px;"># ${esc(t)}</span>
                    `).join('')}
                    ${parseTags(moment.tags).length > 3 ? `<span style="font-size:0.6rem; color:var(--text-muted);">+${parseTags(moment.tags).length - 3}</span>` : ''}
                </div>
            ` : ''}

            ${(moment.reactions && moment.reactions.length > 0) ? `
                <div class="polaroid-reactions">${reactionEmojis}</div>
            ` : ''}

            ${moment.comments > 0 ? `
                <div class="comment-badge">
                    <span class="material-symbols-outlined" style="font-size:0.7rem;">chat_bubble</span>
                    ${moment.comments}
                </div>
            ` : ''}
        </div>
        <p class="polaroid-date font-typewriter">${dateStr}</p>
    `;

    wrap.addEventListener('click', () => openDetail(moment));
    return wrap;
}

/**
 * Deschide modalul de detaliu pentru un moment.
 * @param {Object} moment
 */
async function openDetail(moment) {
    activeMomentId = moment.id;
    const modal   = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    // Randare media in functie de tip (poza / video / audio / fisier text)
    let detailMedia = '';
    if (moment.media_url) {
        if (moment.type === 'video') {
            detailMedia = `<div style="margin:0.8rem 0;"><video src="${moment.media_url}" controls preload="metadata"
                style="width:100%; max-height:360px; display:block; border:1px solid var(--outline); background:#000;"></video></div>`;
        } else if (moment.type === 'audio') {
            detailMedia = `<div style="margin:0.8rem 0;"><audio src="${moment.media_url}" controls style="width:100%;"></audio></div>`;
        } else if (moment.type === 'text') {
            detailMedia = `<div style="margin:0.8rem 0;"><a href="${moment.media_url}" target="_blank" rel="noopener"
                class="font-typewriter" style="display:inline-flex; align-items:center; gap:6px; color:var(--secondary);
                border:1px dashed var(--outline); padding:8px 14px; font-size:0.85rem;">
                <span class="material-symbols-outlined">description</span> Open text file</a></div>`;
        } else {
            detailMedia = `<div style="margin:0.8rem 0; border:1px solid var(--outline);"><img src="${moment.media_url}"
                alt="${moment.title}" style="width:100%; max-height:320px; object-fit:cover; display:block;"/></div>`;
        }
    }

    // Afisam skeleton in timp ce incarcam comentariile
    content.innerHTML = `
        <h2 class="modal-title font-typewriter">${esc(moment.title)}</h2>
        <div class="detail-meta">
            <span class="detail-type-badge">${esc(moment.type)}</span>
            <span class="detail-date">${formatDate(moment.happened_at)}</span>
            <span class="detail-author font-handwritten">by ${esc(moment.first_name)} ${esc(moment.last_name)}</span>
        </div>
        ${parseTags(moment.tags).length ? `
        <div style="display:flex; flex-wrap:wrap; gap:5px; margin:0.3rem 0 0.4rem;">
            ${parseTags(moment.tags).map(t => `
                <span class="font-typewriter" style="font-size:0.7rem; color:var(--secondary);
                      border:1px dotted var(--outline); border-radius:10px; padding:1px 8px;"># ${esc(t)}</span>
            `).join('')}
        </div>` : ''}
        ${(moment.is_shared && moment.share_token) ? `
        <button id="shareLinkBtn" class="font-typewriter" style="margin:0.2rem 0 0.4rem; font-size:0.8rem; color:var(--secondary); border:1px dashed var(--outline); padding:4px 12px; cursor:pointer; background:none; display:inline-flex; align-items:center; gap:5px;">
            <span class="material-symbols-outlined" style="font-size:1rem;">link</span> Copiază link public
        </button>` : ''}
        ${detailMedia}
        ${moment.body ? `<p class="detail-body">${esc(moment.body)}</p>` : ''}

        <div class="reactions-row" id="detailReactions">
            ${REACTIONS.map(r => {
                const existing = (moment.reactions || []).find(rx => rx.emoji_type === r.type);
                return `
                    <button class="reaction-btn" data-type="${r.type}">
                        <span class="emoji">${r.emoji}</span>
                        ${r.label}
                        ${existing && existing.count > 0 ? `<strong>${existing.count}</strong>` : ''}
                    </button>
                `;
            }).join('')}
        </div>

        <div class="comments-section">
            <p class="comments-title">Comments</p>
            <div id="commentsContainer">
                <p style="color:var(--text-muted); font-size:0.85rem;">Loading...</p>
            </div>
            <div class="comment-input-wrap">
                <input type="text" class="comment-input font-handwritten"
                       id="commentInput" placeholder="Write a note..." maxlength="500"/>
                <button class="comment-send-btn font-typewriter" id="commentSendBtn">Send</button>
            </div>
        </div>
    `;

    modal.classList.add('open');

    // Buton: copiaza link-ul public /share/{token} (doar pe momentele partajate)
    const shareBtn = document.getElementById('shareLinkBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
            const url = `${location.origin}/share/${moment.share_token}`;
            try {
                await navigator.clipboard.writeText(url);
                shareBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">check</span> Link copiat!';
            } catch {
                window.prompt('Copiaza link-ul public:', url);
            }
        });
    }

    // Incarca comentariile
    loadComments(moment.id);

    // Reaction buttons
    document.getElementById('detailReactions').querySelectorAll('.reaction-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleReaction(btn.dataset.type, moment));
    });

    // Send comment
    document.getElementById('commentSendBtn').addEventListener('click', () => sendComment(moment.id));
    document.getElementById('commentInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') sendComment(moment.id);
    });
}

/**
 * Incarca comentariile unui moment.
 * @param {number} momentId
 */
async function loadComments(momentId) {
    const container = document.getElementById('commentsContainer');
    if (!container) return;

    try {
        const res = await fetch(`/api/moments/${momentId}/comments`, { credentials: 'include' });
        if (!res.ok) { container.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;">Could not load comments.</p>'; return; }
        const comments = await res.json();

        if (comments.length === 0) {
            container.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;font-family:\'Special Elite\',cursive;">No comments yet — be the first!</p>';
            return;
        }

        container.innerHTML = comments.map(c => `
            <div class="comment-item">
                <p class="comment-author">${esc(c.first_name)} ${esc(c.last_name)}</p>
                <p class="comment-text">${esc(c.body)}</p>
            </div>
        `).join('');

    } catch {
        container.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;">Error loading comments.</p>';
    }
}

/**
 * Trimite un comentariu.
 * @param {number} momentId
 */
async function sendComment(momentId) {
    const input = document.getElementById('commentInput');
    const body  = input.value.trim();
    if (!body) return;

    const btn = document.getElementById('commentSendBtn');
    btn.disabled = true;

    try {
        const res = await fetch(`/api/moments/${momentId}/comments`, {
            method:      'POST',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
            credentials: 'include',
            body:        JSON.stringify({ body }),
        });
        if (res.ok) {
            input.value = '';
            loadComments(momentId);
        }
    } finally {
        btn.disabled = false;
    }
}

/**
 * Toggle reactie pe un moment.
 * @param {string} emojiType
 * @param {Object} moment
 */
async function toggleReaction(emojiType, moment) {
    const btn = document.querySelector(`#detailReactions .reaction-btn[data-type="${emojiType}"]`);
    if (!btn) return;

    const isActive = btn.classList.contains('active');
    const method   = isActive ? 'DELETE' : 'POST';

    try {
        const res = await fetch(`/api/moments/${moment.id}/reactions`, {
            method,
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
            credentials: 'include',
            body:        JSON.stringify({ emoji_type: emojiType }),
        });

        if (res.ok) {
            btn.classList.toggle('active');
        }
    } catch { /* silently fail */ }
}

/**
 * Deschide modalul de adaugare moment (doar pentru useri cu write access).
 */
function openAddMoment() {
    if (!userCanWrite) return;   // guard — viewer nu poate adauga
    document.getElementById('addMomentForm').reset();
    document.getElementById('momentError').style.display = 'none';
    document.getElementById('momentSubmitBtn').disabled  = false;
    document.getElementById('momentSubmitBtn').textContent = 'Stamp in Archive';

    // Set default date to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('momentDate').value = now.toISOString().slice(0,16);

    // Arata/ascunde campul de fisier in functie de tipul selectat implicit
    const mediaTypes = ['photo', 'video', 'audio', 'text'];
    const defaultType = document.getElementById('momentType').value;
    document.getElementById('momentFileField').style.display =
        mediaTypes.includes(defaultType) ? 'block' : 'none';

    document.getElementById('addMomentModal').classList.add('open');
}

// ── Event Listeners ──────────────────────────────────────────────────────────
document.getElementById('fabBtn').addEventListener('click', openAddMoment);
document.getElementById('shelfUploadBtn').addEventListener('click', openAddMoment);

// Arata/ascunde campul de fisier in functie de tipul momentului
document.getElementById('momentType').addEventListener('change', (e) => {
    const mediaTypes = ['photo', 'video', 'audio', 'text'];
    const fileField  = document.getElementById('momentFileField');
    fileField.style.display = mediaTypes.includes(e.target.value) ? 'block' : 'none';
    if (!mediaTypes.includes(e.target.value)) {
        document.getElementById('momentFile').value = '';
    }
});

document.getElementById('addMomentClose').addEventListener('click', () => {
    document.getElementById('addMomentModal').classList.remove('open');
});
document.getElementById('addMomentModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('addMomentModal'))
        document.getElementById('addMomentModal').classList.remove('open');
});

document.getElementById('detailClose').addEventListener('click', () => {
    document.getElementById('detailModal').classList.remove('open');
});
document.getElementById('detailModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('detailModal'))
        document.getElementById('detailModal').classList.remove('open');
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('addMomentModal').classList.remove('open');
        document.getElementById('detailModal').classList.remove('open');
    }
});

// Date range filter
['dateFrom', 'dateTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        const hasRange = document.getElementById('dateFrom').value || document.getElementById('dateTo').value;
        document.getElementById('dateClear').style.display = hasRange ? '' : 'none';
        renderGallery(allMoments);
    });
});
document.getElementById('dateClear').addEventListener('click', () => {
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    document.getElementById('dateClear').style.display = 'none';
    renderGallery(allMoments);
});

// Filter chips (doar cele de tip — [data-type]; tag chips si dateClear au handlerele lor)
document.querySelectorAll('.filter-chip[data-type]').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip[data-type]').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        activeFilter = chip.dataset.type;
        renderGallery(allMoments);
    });
});

// Submit form add moment
document.getElementById('addMomentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    document.getElementById('momentError').style.display = 'none';

    const title = document.getElementById('momentTitle').value.trim();
    if (!title) {
        document.getElementById('momentErrorText').textContent = 'Title is required.';
        document.getElementById('momentError').style.display = 'block';
        return;
    }

    const btn = document.getElementById('momentSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Stamping...';

    try {
        // Folosim FormData ca sa putem trimite si fisierul atasat (multipart/form-data).
        // NU setam Content-Type manual — browser-ul adauga automat boundary-ul corect.
        const fd = new FormData();
        fd.append('type',        document.getElementById('momentType').value);
        fd.append('title',       title);
        fd.append('body',        document.getElementById('momentBody').value.trim());
        fd.append('is_pinned',   document.getElementById('momentPinned').checked  ? '1' : '0');
        fd.append('is_shared',   document.getElementById('momentShared').checked  ? '1' : '0');

        const dateVal = document.getElementById('momentDate').value;
        if (dateVal) fd.append('happened_at', dateVal);

        const tagsVal = document.getElementById('momentTags').value.trim();
        if (tagsVal) fd.append('tags', tagsVal);

        const fileInput = document.getElementById('momentFile');
        if (fileInput.files.length > 0) {
            fd.append('photo', fileInput.files[0]);
        }

        const res = await fetch(`/api/children/${activeChildId}/moments`, {
            method:      'POST',
            credentials: 'include',
            // Content-Type intentionat omis — browser seteaza multipart/form-data cu boundary corect
            headers:     { 'X-CSRF-Token': await getCsrfToken() },
            body:        fd,
        });
        const data = await res.json();

        if (!res.ok) {
            document.getElementById('momentErrorText').textContent = data.error || 'Could not save moment.';
            document.getElementById('momentError').style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Stamp in Archive';
            return;
        }

        document.getElementById('addMomentModal').classList.remove('open');
        await loadTimeline();

    } catch {
        document.getElementById('momentErrorText').textContent = 'Connection error. Try again.';
        document.getElementById('momentError').style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Stamp in Archive';
    }
});

// Logout
document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('/api/auth/logout', {
        method: 'POST',
        headers: { 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
    });
    window.location.href = '/login';
});

// Child selector
document.getElementById('childSelector').addEventListener('change', (e) => {
    activeChildId = parseInt(e.target.value);
    localStorage.setItem('activeChildId', activeChildId);
    buildShelf(children.find(c => String(c.id) === e.target.value) || children[0]);
    updateWriteAccess();
    loadTimeline();
});

// Doar owner si coparent pot adauga momente.
// Caregiver si viewer sunt read-only (pot comenta si reactiona).
const WRITE_ROLES = ['owner', 'coparent'];

/**
 * Determina write access din permisiunea deja returnata de /api/children
 * (getByUser include fm.permission). Nu face niciun extra fetch.
 * Actualizeaza `userCanWrite` si ajusteaza UI-ul.
 */
function updateWriteAccess() {
    const activeChild = children.find(c => String(c.id) === String(activeChildId));
    const permission  = activeChild ? activeChild.permission : 'viewer';
    userCanWrite = WRITE_ROLES.includes(permission);

    // FAB si butonul de upload de pe raft — doar pentru editors
    document.getElementById('fabBtn').style.display        = userCanWrite ? 'flex' : 'none';
    document.getElementById('shelfUploadBtn').style.display = userCanWrite ? 'flex' : 'none';

    // Viewer: ascunde link-urile din nav la sectiuni private
    // Raftul RAMANE vizibil pentru toti — doar controlele de scriere dispar
    document.querySelectorAll('.nav-links a, .mobile-bottom-nav .nav-item').forEach(link => {
        // Link-urile Admin (sus + meniul de jos) sunt gestionate exclusiv de logica is_superadmin.
        if (link.id === 'adminNavLink' || link.id === 'adminBottomNav') return;
        const href = link.getAttribute('href') || '';
        if (!userCanWrite && !href.includes('gallery')) {
            link.style.display = 'none';
        } else {
            link.style.display = '';
        }
    });
}

/**
 * Incarca timeline-ul copilului activ si randeaza galeria.
 */
async function loadTimeline() {
    if (!activeChildId) return;

    document.getElementById('galleryContent').innerHTML = `
        <div style="text-align:center; padding:4rem 2rem; color:var(--text-muted);">
            <span class="material-symbols-outlined" style="font-size:2rem; display:block; margin-bottom:0.5rem; animation: spin 1s linear infinite;">autorenew</span>
            <span class="font-typewriter" style="font-size:0.85rem; letter-spacing:1px;">DEVELOPING FILM...</span>
        </div>
    `;

    try {
        const res = await fetch(`/api/children/${activeChildId}/timeline`, { credentials: 'include' });
        if (!res.ok) { allMoments = []; }
        else { allMoments = await res.json(); }
    } catch {
        allMoments = [];
    }

    renderTagChips(allMoments);
    renderGallery(allMoments);
}

/**
 * Init: verifica autentificarea, incarca copiii, randeaza.
 */
async function init() {
    const meRes = await fetch('/api/auth/me', { credentials: 'include' });
    if (!meRes.ok) { window.location.href = '/login'; return; }
    currentUser = await meRes.json();

    // Afiseaza link-ul Admin (sus + meniul de jos) doar pentru super-admini
    if (currentUser.is_superadmin) {
        document.getElementById('adminNavLink').style.display = '';
        document.getElementById('adminBottomNav').style.display = '';
    }

    const childRes = await fetch('/api/children', { credentials: 'include' });
    children = childRes.ok ? await childRes.json() : [];

    if (children.length === 0) {
        document.getElementById('galleryContent').innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-outlined big-icon">child_care</span>
                <h2 class="font-headline">No children yet</h2>
                <p>Go to the <a href="/dashboard" style="color:var(--secondary);">Journal</a> to add a child first.</p>
            </div>
        `;
        return;
    }

    // Populeaza selectorul
    const sel = document.getElementById('childSelector');
    children.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.first_name}${c.last_name ? ' ' + c.last_name : ''}`;
        sel.appendChild(opt);
    });
    if (children.length > 1) sel.style.display = 'block';

    const savedId = parseInt(localStorage.getItem('activeChildId'));
    const validSaved = children.find(c => c.id === savedId);
    activeChildId = validSaved ? savedId : children[0].id;
    sel.value = activeChildId;

    // Arata UI-ul
    document.getElementById('shelfSection').style.display = 'block';
    document.getElementById('filterBar').style.display    = 'flex';
    document.getElementById('fabBtn').style.display       = 'flex';

    buildShelf(children[0]);
    updateWriteAccess();        // sincron acum — nu mai face fetch
    await loadTimeline();
}

init();
