/* @author Romila Raluca */

// ── State ──────────────────────────────────────────────────────────────────
let currentUser    = null;
let children       = [];
let activeChildId  = null;
let familyMembers  = [];
let relationships  = [];
let userPermission = 'viewer';

// Scatter rotations for polaroids
const ROTATIONS = [-4, 3, -2, 5, -3, 2, -5, 4, -1, 6];

const ROLE_LABELS = {
    owner:     'Owner',
    coparent:  'Co-parent',
    caregiver: 'Caregiver',
    viewer:    'Viewer',
};

const GROUP_ICONS = {
    family:  '👨‍👩‍👧',
    friends: '🌟',
    daycare: '🏫',
    other:   '💛',
};

// ── Utilities ──────────────────────────────────────────────────────────────

/**
 * Escapes a value for safe HTML output (XSS prevention).
 */
function esc(val) {
    const d = document.createElement('div');
    d.textContent = String(val ?? '');
    return d.innerHTML;
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

/**
 * Returns initials and background color for a person based on name and avatarColor.
 */
function avatarInfo(name, avatarColor) {
    const palettes = {
        c1: '#d8e2dc', c2: '#f0e6e6', c3: '#e9edc9',
        c4: '#d4e4f7', c5: '#f7e4d4', c6: '#e4d4f7',
    };
    const bg       = palettes[avatarColor] || palettes.c1;
    const initials = (name || '?').split(' ')
        .map(p => p[0] || '').join('').substring(0, 2).toUpperCase();
    return { bg, initials };
}

/**
 * Calculates age string from ISO date of birth.
 */
function calcAge(dob) {
    if (!dob) return '';
    const d = new Date(dob), now = new Date();
    const m = (now.getFullYear() - d.getFullYear()) * 12 + (now.getMonth() - d.getMonth());
    return m < 24 ? `${m}m` : `${Math.floor(m / 12)}y`;
}

// ── Render helpers ─────────────────────────────────────────────────────────

/**
 * Renders the child's center polaroid.
 */
function renderChildPolaroid(child) {
    const age = calcAge(child.date_of_birth);
    const av  = avatarInfo(`${child.first_name} ${child.last_name}`, child.avatar_color);
    return `
        <div class="pol-child" id="child-center">
            <div class="polaroid" style="transform:rotate(-2deg); cursor:default;">
                <div class="push-pin"></div>
                <div class="polaroid-photo" style="background:${esc(av.bg)};">
                    ${child.photo_url
                        ? `<img src="${esc(child.photo_url)}" alt="${esc(child.first_name)}"/>`
                        : `<span style="font-family:'Caveat',cursive; font-size:3.5rem; color:var(--primary);">${esc(av.initials)}</span>`
                    }
                </div>
                <div class="polaroid-caption">
                    <span class="polaroid-name" style="font-size:1.3rem; font-weight:700;">
                        ${esc(child.first_name)}
                    </span>
                    ${age ? `<span style="font-family:'Special Elite',cursive; font-size:0.7rem; color:var(--text-muted); display:block;">${esc(age)} old</span>` : ''}
                </div>
            </div>
        </div>`;
}

/**
 * Renders a single family member as a polaroid.
 */
function renderMemberCard(member, index) {
    const rot = ROTATIONS[index % ROTATIONS.length];
    const av  = avatarInfo(`${member.first_name} ${member.last_name}`, member.avatar_color || 'c1');
    const rl  = ROLE_LABELS[member.permission] || member.permission;
    const pin = index % 2 === 0
        ? `<div class="push-pin left"></div>`
        : `<div class="washi ${index % 3 === 1 ? 'olive' : 'blush'}"></div>`;

    return `
        <div class="pol-member" onclick="openMemberDetail(${member.user_id})" title="${esc(member.first_name)} ${esc(member.last_name)}">
            <div class="polaroid" style="transform:rotate(${rot}deg);">
                ${pin}
                <div class="polaroid-photo" style="background:${esc(av.bg)};">
                    <span style="font-family:'Caveat',cursive; font-size:2.8rem; color:var(--primary);">${esc(av.initials)}</span>
                </div>
                <div class="polaroid-caption">
                    <span class="polaroid-name">${esc(member.first_name)} ${esc(member.last_name)}</span>
                    <span class="role-badge role-${esc(member.permission)}">${esc(rl)}</span>
                </div>
            </div>
        </div>`;
}

/**
 * Renders a sibling (another child in the same family) as a polaroid.
 * Click navigates to that child's board.
 */
function renderSiblingCard(sibling, index) {
    const rot = ROTATIONS[(index + 6) % ROTATIONS.length];
    const av  = avatarInfo(`${sibling.first_name} ${sibling.last_name || ''}`, sibling.avatar_color);
    const age = calcAge(sibling.date_of_birth);
    const genderIcon = sibling.gender === 'F' ? '👧' : sibling.gender === 'M' ? '👦' : '👶';

    return `
        <div class="pol-member" title="${esc(sibling.first_name)}"
             onclick="switchToSibling(${sibling.id})" style="cursor:pointer;">
            <div class="polaroid" style="transform:rotate(${rot}deg);">
                <div class="washi blush"></div>
                <div class="polaroid-photo" style="background:${esc(av.bg)}; font-size:${sibling.photo_url ? '0' : '2.8rem'};">
                    ${sibling.photo_url
                        ? `<img src="${esc(sibling.photo_url)}" alt="${esc(sibling.first_name)}" style="width:100%;height:100%;object-fit:cover;"/>`
                        : genderIcon
                    }
                </div>
                <div class="polaroid-caption">
                    <span class="polaroid-name">${esc(sibling.first_name)}</span>
                    <span class="group-tag" style="background:var(--scrap-blush);">sibling${age ? ' · ' + age : ''}</span>
                </div>
            </div>
        </div>`;
}

/**
 * Switches the active child to a sibling and reloads the board.
 */
function switchToSibling(childId) {
    activeChildId = childId;
    localStorage.setItem('activeChildId', childId);
    document.getElementById('childSelector').value = childId;
    loadBoard();
}

/**
 * Renders a social relationship as a smaller polaroid.
 */
function renderRelCard(rel, index) {
    const rot  = ROTATIONS[(index + 4) % ROTATIONS.length];
    const icon = GROUP_ICONS[rel.group_type] || '💛';
    const gCls = `group-${rel.group_type}`;

    return `
        <div class="pol-rel" onclick="openRelDetail(${rel.id})" title="${esc(rel.name)}">
            <div class="polaroid" style="transform:rotate(${rot}deg);">
                <div class="push-pin"></div>
                <div class="polaroid-photo" style="background:var(--scrap-blush); font-size:2.8rem;">
                    ${icon}
                </div>
                <div class="polaroid-caption">
                    <span class="polaroid-name" style="font-size:0.95rem;">${esc(rel.name)}</span>
                    <span class="group-tag ${gCls}">${esc(rel.group_type)}</span>
                </div>
            </div>
        </div>`;
}

/**
 * Draws SVG dashed red strings from the center child polaroid
 * to every visible family member and relationship card.
 */
function drawStrings() {
    const svg   = document.getElementById('stringLayer');
    const board = document.getElementById('corkboard');
    const center = document.getElementById('child-center');
    svg.innerHTML = '';
    if (!center) return;

    const bRect = board.getBoundingClientRect();
    const cRect = center.getBoundingClientRect();
    const cx = cRect.left - bRect.left + cRect.width  / 2 + board.scrollLeft;
    const cy = cRect.top  - bRect.top  + cRect.height / 2 + board.scrollTop;

    document.querySelectorAll('.pol-member .polaroid, .pol-rel .polaroid, .pol-sibling .polaroid').forEach(el => {
        const r  = el.getBoundingClientRect();
        const tx = r.left - bRect.left + r.width  / 2 + board.scrollLeft;
        const ty = r.top  - bRect.top  + r.height / 2 + board.scrollTop;

        // Slight random curve so strings don't all overlap
        const seed = tx * 0.01 + ty * 0.01;
        const mx = (cx + tx) / 2 + Math.sin(seed) * 50;
        const my = (cy + ty) / 2 + Math.cos(seed) * 30;

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M${cx},${cy} Q${mx},${my} ${tx},${ty}`);
        path.setAttribute('class', 'red-string');
        svg.appendChild(path);
    });
}

// ── Board render ───────────────────────────────────────────────────────────

function renderBoard(child) {
    // Subtitle
    document.getElementById('boardSubtitle').textContent =
        `${child.first_name}'s favourite people, all pinned up!`;

    // Child center
    document.getElementById('childPolaroidWrap').innerHTML = renderChildPolaroid(child);

    // Family members (exclude self)
    const famEl     = document.getElementById('familyContainer');
    const otherMems = familyMembers.filter(m => m.user_id !== currentUser.id);

    // Siblings = other children in the same family
    const siblings  = children.filter(c => c.id !== activeChildId);

    let famHtml = '';

    if (otherMems.length > 0) {
        famHtml += `<div style="display:flex; flex-direction:column; align-items:center; gap:2rem;">
            ${otherMems.map((m, i) => renderMemberCard(m, i)).join('')}
        </div>`;
    } else {
        famHtml += `
            <div class="add-note" onclick="document.getElementById('inviteModal').classList.add('open')">
                <p>Invite a co-parent or caregiver →</p>
            </div>`;
    }

    if (siblings.length > 0) {
        famHtml += `
            <div style="width:100%; text-align:center; margin-top:1rem;">
                <span style="font-family:'Caveat',cursive; font-size:1.1rem; color:rgba(255,255,255,0.75);
                             text-shadow:1px 1px 2px rgba(0,0,0,0.3); display:block; margin-bottom:1rem;">
                    👶 Siblings
                </span>
                <div style="display:flex; flex-direction:column; align-items:center; gap:2rem;">
                    ${siblings.map((s, i) => renderSiblingCard(s, i)).join('')}
                </div>
            </div>`;
    }

    famEl.innerHTML = famHtml;

    // Social relationships — show first 4 in the right column, rest below
    const socEl = document.getElementById('socialContainer');
    if (relationships.length > 0) {
        const inCol  = relationships.slice(0, 4);
        const inGrid = relationships.slice(4);

        socEl.innerHTML = `<div style="display:flex; flex-direction:column; align-items:center; gap:2rem;">
            ${inCol.map((r, i) => renderRelCard(r, i)).join('')}
        </div>`;

        if (inGrid.length > 0) {
            document.getElementById('relSection').style.display = 'block';
            document.getElementById('relGrid').innerHTML =
                inGrid.map((r, i) => renderRelCard(r, i + 4)).join('');
        } else {
            document.getElementById('relSection').style.display = 'none';
        }
    } else {
        socEl.innerHTML = `
            <div class="add-note" onclick="document.getElementById('addRelModal').classList.add('open')">
                <p>Pin a cousin, friend or classmate →</p>
            </div>`;
        document.getElementById('relSection').style.display = 'none';
    }

    document.getElementById('loadingState').style.display = 'none';
    document.getElementById('boardContent').style.display  = 'block';

    // Draw strings after DOM settles
    setTimeout(drawStrings, 200);
}

// ── Data loading ───────────────────────────────────────────────────────────

/**
 * Fetches family members and social relationships for the active child,
 * then re-renders the board.
 */
async function loadBoard() {
    if (!activeChildId) return;

    document.getElementById('boardContent').style.display = 'none';
    document.getElementById('loadingState').style.display = 'block';

    const [famRes, relRes] = await Promise.all([
        fetch(`/api/children/${activeChildId}/family`,        { credentials: 'include' }),
        fetch(`/api/children/${activeChildId}/relationships`, { credentials: 'include' }),
    ]);

    familyMembers = famRes.ok ? await famRes.json() : [];
    relationships = relRes.ok ? await relRes.json() : [];

    // Determine current user's permission on this child
    const myEntry  = familyMembers.find(m => m.user_id === currentUser.id);
    userPermission = myEntry ? myEntry.permission : 'viewer';

    // Invite FAB: only owners can invite
    const invFab = document.getElementById('inviteFab');
    if (userPermission === 'owner') {
        invFab.style.display = 'flex';
    } else {
        invFab.style.display = 'none';
    }

    const child = children.find(c => c.id === activeChildId);
    renderBoard(child);
}

// ── Initialisation ─────────────────────────────────────────────────────────

/**
 * Entry point: checks auth, loads children, renders the board.
 */
async function init() {
    // 1. Auth guard
    const meRes = await fetch('/api/auth/me', { credentials: 'include' });
    if (!meRes.ok) { window.location.href = '/login'; return; }
    currentUser = await meRes.json();

    // Show admin links for superadmins
    if (currentUser.is_superadmin) {
        document.getElementById('adminNavLink').style.display    = '';
        document.getElementById('adminBottomNav').style.display  = '';
    }

    // 2. Load children
    const childRes = await fetch('/api/children', { credentials: 'include' });
    children = childRes.ok ? await childRes.json() : [];

    if (children.length === 0) {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display   = 'block';
        return;
    }

    // 3. Populate child selector
    const sel = document.getElementById('childSelector');
    sel.innerHTML = children.map(c =>
        `<option value="${c.id}">${esc(c.first_name)}</option>`
    ).join('');
    sel.style.display = '';

    const savedId = parseInt(localStorage.getItem('activeChildId'));
    const validSaved = children.find(c => c.id === savedId);
    activeChildId = validSaved ? savedId : children[0].id;
    sel.value = activeChildId;
    await loadBoard();
}

// ── Detail modals ──────────────────────────────────────────────────────────

/**
 * Opens the detail panel for a family member.
 * Owners can change permissions or remove members.
 */
function openMemberDetail(userId) {
    const m = familyMembers.find(x => x.user_id === userId);
    if (!m) return;

    const av       = avatarInfo(`${m.first_name} ${m.last_name}`, m.avatar_color || 'c1');
    const rl       = ROLE_LABELS[m.permission] || m.permission;
    const isSelf   = m.user_id === currentUser.id;
    const canManage = userPermission === 'owner' && !isSelf;

    document.getElementById('detailContent').innerHTML = `
        <div class="detail-header">
            <div class="detail-avatar" style="background:${esc(av.bg)};">
                <span style="color:var(--primary);">${esc(av.initials)}</span>
            </div>
            <div>
                <div class="detail-name" id="detailTitle">${esc(m.first_name)} ${esc(m.last_name)}</div>
                ${m.email ? `<div class="detail-sub">${esc(m.email)}</div>` : ''}
                <span class="role-badge role-${esc(m.permission)}" style="margin-top:6px; display:inline-block;">${esc(rl)}</span>
                ${isSelf ? `<span style="font-family:'Caveat',cursive; font-size:0.9rem; color:var(--text-muted); margin-left:8px;">(you)</span>` : ''}
            </div>
        </div>
        <div class="detail-info">
            Joined: ${m.joined_at ? new Date(m.joined_at).toLocaleDateString('ro-RO') : '—'}
        </div>
        ${canManage ? `
        <div class="detail-actions">
            <div>
                <label style="font-family:'Special Elite',cursive; font-size:0.7rem; letter-spacing:1px; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:4px;">Change role:</label>
                <select class="perm-select" id="permSelect" onchange="changePermission(${m.user_id}, this.value)">
                    <option value="coparent"  ${m.permission==='coparent'  ? 'selected':''}>Co-parent</option>
                    <option value="caregiver" ${m.permission==='caregiver' ? 'selected':''}>Caregiver</option>
                    <option value="viewer"    ${m.permission==='viewer'    ? 'selected':''}>Viewer</option>
                </select>
            </div>
            <button class="btn-danger" onclick="removeMember(${m.user_id})">
                <span class="material-symbols-outlined" style="font-size:0.9rem;">person_remove</span>
                Remove
            </button>
        </div>` : ''}
    `;
    document.getElementById('detailModal').classList.add('open');
}

/**
 * Opens the detail panel for a social relationship.
 * Owners and co-parents can delete it.
 */
function openRelDetail(relId) {
    const r = relationships.find(x => x.id === relId);
    if (!r) return;

    const icon     = GROUP_ICONS[r.group_type] || '💛';
    const canEdit  = userPermission === 'owner' || userPermission === 'coparent';

    document.getElementById('detailContent').innerHTML = `
        <div class="detail-header">
            <div class="detail-avatar" style="background:var(--scrap-blush); font-size:2.5rem;">
                ${icon}
            </div>
            <div>
                <div class="detail-name" id="detailTitle">${esc(r.name)}</div>
                <div class="detail-sub">${esc(r.relationship)} · ${esc(r.group_type)}${r.age_years ? ` · ${r.age_years}y` : ''}</div>
            </div>
        </div>
        ${r.notes ? `<div class="detail-info">${esc(r.notes)}</div>` : ''}
        ${canEdit ? `
        <div class="detail-actions">
            <button class="btn-danger" onclick="deleteRelationship(${r.id})">
                <span class="material-symbols-outlined" style="font-size:0.9rem;">push_pin</span>
                Unpin from board
            </button>
        </div>` : ''}
    `;
    document.getElementById('detailModal').classList.add('open');
}

// ── Actions ────────────────────────────────────────────────────────────────

/**
 * Updates a family member's permission. Only owners can call this.
 */
async function changePermission(userId, newPerm) {
    const res = await fetch(`/api/children/${activeChildId}/family/permission`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
        body: JSON.stringify({ user_id: userId, permission: newPerm }),
    });
    if (res.ok) {
        document.getElementById('detailModal').classList.remove('open');
        await loadBoard();
    }
}

/**
 * Removes a family member from the child's family. Only owners can call this.
 */
async function removeMember(userId) {
    if (!confirm('Remove this person from the family?')) return;
    const res = await fetch(`/api/children/${activeChildId}/family/member`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
        body: JSON.stringify({ user_id: userId }),
    });
    if (res.ok) {
        document.getElementById('detailModal').classList.remove('open');
        await loadBoard();
    }
}

/**
 * Deletes a social relationship from the board.
 */
async function deleteRelationship(relId) {
    if (!confirm('Remove this person from the board?')) return;
    const res = await fetch(`/api/relationships/${relId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
    });
    if (res.ok) {
        document.getElementById('detailModal').classList.remove('open');
        await loadBoard();
    }
}

// ── Form: add relationship ─────────────────────────────────────────────────
document.getElementById('addRelForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('addRelError');
    errEl.style.display = 'none';

    const btn = document.getElementById('addRelSubmit');
    btn.disabled = true;
    btn.textContent = 'Pinning...';

    const res = await fetch(`/api/children/${activeChildId}/relationships`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
        body: JSON.stringify({
            name:         document.getElementById('relName').value.trim(),
            relationship: document.getElementById('relRelationship').value.trim(),
            group_type:   document.getElementById('relGroup').value,
            age_years:    document.getElementById('relAge').value || null,
            notes:        document.getElementById('relNotes').value.trim() || null,
        }),
    });

    btn.disabled = false;
    btn.textContent = 'Pin to Board';

    const data = await res.json();
    if (!res.ok) {
        errEl.textContent = data.error || 'Could not add person.';
        errEl.style.display = 'block';
        return;
    }

    document.getElementById('addRelForm').reset();
    document.getElementById('addRelModal').classList.remove('open');
    await loadBoard();
});

// ── Form: invite ───────────────────────────────────────────────────────────
document.getElementById('inviteForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('inviteError');
    errEl.style.display = 'none';

    const btn = document.getElementById('inviteSubmit');
    btn.disabled = true;
    btn.textContent = 'Generating...';

    const res = await fetch(`/api/children/${activeChildId}/invites`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrfToken() },
        credentials: 'include',
        body: JSON.stringify({
            email:      document.getElementById('inviteEmail').value.trim() || null,
            permission: document.getElementById('invitePermission').value,
        }),
    });

    btn.disabled = false;
    btn.textContent = 'Generate Invite Link';

    const data = await res.json();
    if (!res.ok) {
        errEl.textContent = data.error || 'Could not generate link.';
        errEl.style.display = 'block';
        return;
    }

    const fullLink = `${window.location.origin}/join?token=${data.token}`;
    document.getElementById('inviteLinkText').textContent = fullLink;
    document.getElementById('inviteLinkBox').style.display = 'block';
});

document.getElementById('copyLinkBtn').addEventListener('click', () => {
    const link = document.getElementById('inviteLinkText').textContent;
    navigator.clipboard.writeText(link).catch(() => {
        window.prompt('Copy this link:', link);
    });
});

// ── Close modals ───────────────────────────────────────────────────────────
['addRelModal', 'inviteModal', 'detailModal'].forEach(id => {
    const el = document.getElementById(id);
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
document.getElementById('addRelClose').addEventListener('click',  () => document.getElementById('addRelModal').classList.remove('open'));
document.getElementById('inviteClose').addEventListener('click',  () => {
    document.getElementById('inviteModal').classList.remove('open');
    document.getElementById('inviteLinkBox').style.display = 'none';
    document.getElementById('inviteForm').reset();
});
document.getElementById('detailClose').addEventListener('click',  () => document.getElementById('detailModal').classList.remove('open'));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('addRelModal').classList.remove('open');
        document.getElementById('inviteModal').classList.remove('open');
        document.getElementById('detailModal').classList.remove('open');
    }
});

// ── FABs ───────────────────────────────────────────────────────────────────
document.getElementById('fabBtn').addEventListener('click', () =>
    document.getElementById('addRelModal').classList.add('open')
);
document.getElementById('inviteFab').addEventListener('click', () => {
    document.getElementById('inviteLinkBox').style.display = 'none';
    document.getElementById('inviteForm').reset();
    document.getElementById('inviteModal').classList.add('open');
});

// ── Child selector ─────────────────────────────────────────────────────────
document.getElementById('childSelector').addEventListener('change', (e) => {
    activeChildId = parseInt(e.target.value);
    localStorage.setItem('activeChildId', activeChildId);
    loadBoard();
});

// ── Logout ─────────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
    window.location.href = '/login';
});

// ── Redraw strings on resize ───────────────────────────────────────────────
window.addEventListener('resize', () => {
    clearTimeout(window._resizeTimer);
    window._resizeTimer = setTimeout(drawStrings, 150);
});

// ── Boot ───────────────────────────────────────────────────────────────────
init();
