<!--
  user_profile_modal.php  (FIXED VERSION)
  Place at:  views/user_profile_modal.php
  Include at the bottom of admin_user.php (before </body>)

  KEY FIXES:
  - Avatar is fully visible (moved inside hero, sits on the bottom edge with overflow visible)
  - Hero height reduced so avatar doesn't clip on small sheets
  - Identity row has correct top-padding to clear the avatar
  - Overall polish improvements
-->

<!-- ═══════════════════════════════════════════════════════════════
     MODAL OVERLAY
════════════════════════════════════════════════════════════════════ -->
<div id="userProfileModal" class="upm-overlay" onclick="closeProfileModal(event)" role="dialog" aria-modal="true" aria-labelledby="upm-username">
  <div class="upm-sheet">

    <!-- Close button -->
    <button class="upm-close" onclick="closeProfileModalBtn()" aria-label="Close">
      <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>

    <!-- Loading spinner -->
    <div class="upm-loading" id="upmLoading">
      <div class="upm-spinner"></div>
      <span>Loading profile…</span>
    </div>

    <!-- Content (filled by JS) -->
    <div class="upm-content" id="upmContent" style="display:none;"></div>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     STYLES
════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Overlay ── */
.upm-overlay {
  position: fixed; inset: 0;
  background: rgba(15,25,18,.6);
  backdrop-filter: blur(5px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  opacity: 0;
  visibility: hidden;
  transition: opacity .25s, visibility .25s;
}
.upm-overlay.open {
  opacity: 1;
  visibility: visible;
}

/* ── Sheet ── */
.upm-sheet {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 840px;
  max-height: 92vh;
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;
  box-shadow: 0 32px 80px rgba(0,0,0,.28), 0 2px 8px rgba(0,0,0,.08);
  transform: translateY(24px) scale(.97);
  transition: transform .3s cubic-bezier(.34,1.56,.64,1);
  scrollbar-width: thin;
  scrollbar-color: rgba(26,51,37,.18) transparent;
}
.upm-overlay.open .upm-sheet {
  transform: translateY(0) scale(1);
}
.upm-sheet::-webkit-scrollbar { width: 4px; }
.upm-sheet::-webkit-scrollbar-thumb { background: rgba(26,51,37,.18); border-radius:4px; }

/* ── Close button — floats above hero ── */
.upm-close {
  position: absolute;
  top: 14px; right: 14px;
  width: 36px; height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(0,0,0,.38);
  backdrop-filter: blur(6px);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #fff;
  z-index: 20;
  transition: all .2s;
}
.upm-close:hover { background: rgba(0,0,0,.58); transform: scale(1.08); }

/* ── Loading ── */
.upm-loading {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 5rem 2rem;
  gap: 1rem;
  color: #6b6355;
  font-size: .875rem;
}
.upm-spinner {
  width: 32px; height: 32px;
  border: 3px solid rgba(26,51,37,.12);
  border-top-color: #1a3325;
  border-radius: 50%;
  animation: upm-spin .7s linear infinite;
}
@keyframes upm-spin { to { transform: rotate(360deg); } }

/* ══════════════════════════════════════════════════════
   HERO + AVATAR  (THE FIXED PART)
   
   The trick: hero has overflow:visible so the avatar
   sitting at the bottom edge is fully shown. We use a
   wrapper (.upm-hero-inner) for the clipped bg image.
══════════════════════════════════════════════════════ */
.upm-hero {
  position: relative;
  /* overflow MUST be visible so the avatar protrudes */
  overflow: visible;
  /* margin-bottom creates room below hero for the protruding avatar */
  margin-bottom: 0;
  border-radius: 20px 20px 0 0;
}

/* The actual clipped background area */
.upm-hero-inner {
  height: 200px;
  background: linear-gradient(135deg, #1a3325 0%, #2d5a42 50%, #1a3325 100%);
  border-radius: 20px 20px 0 0;
  overflow: hidden;
  position: relative;
}

.upm-hero-bg {
  position: absolute; inset: 0;
  object-fit: cover; width: 100%; height: 100%;
  cursor: zoom-in;
  transition: transform .35s ease, opacity .2s;
  display: block;
}
.upm-hero-bg:hover { transform: scale(1.04); opacity: .9; }

.upm-hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to bottom, transparent 30%, rgba(0,0,0,.45));
  pointer-events: none;
}

/* Click hint on hover */
.upm-hero-hint {
  position: absolute;
  top: 12px; left: 12px;
  background: rgba(0,0,0,.48);
  backdrop-filter: blur(4px);
  color: #fff;
  font-size: .6rem; font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  pointer-events: none;
  letter-spacing: .06em;
  text-transform: uppercase;
  opacity: 0;
  transition: opacity .2s;
}
.upm-hero-inner:hover .upm-hero-hint { opacity: 1; }

/* ── Avatar wrapper — sits at bottom of hero, protrudes below ── */
.upm-avatar-wrap {
  position: absolute;
  /* bottom: -54px means centre of 108px avatar sits exactly on the hero bottom line */
  bottom: -54px;
  left: 28px;
  z-index: 5;
}

.upm-avatar,
.upm-avatar-initials {
  width: 108px; height: 108px;
  border-radius: 50%;
  border: 4px solid #fff;
  box-shadow: 0 4px 24px rgba(0,0,0,.22), 0 0 0 1px rgba(0,0,0,.06);
  display: block;
}
.upm-avatar {
  object-fit: cover;
  cursor: zoom-in;
  transition: transform .2s, box-shadow .2s;
}
.upm-avatar:hover {
  transform: scale(1.06);
  box-shadow: 0 8px 32px rgba(0,0,0,.3);
}
.upm-avatar-initials {
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; font-weight: 800;
  font-family: 'Manrope', sans-serif;
}

/* ── Lightbox ── */
.upm-lightbox {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.93);
  z-index: 2000;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
  cursor: zoom-out;
  animation: upm-lb-in .18s ease;
}
@keyframes upm-lb-in { from { opacity:0; } to { opacity:1; } }
.upm-lightbox img {
  max-width: 100%; max-height: 90vh;
  border-radius: 10px;
  box-shadow: 0 24px 80px rgba(0,0,0,.5);
  object-fit: contain;
  pointer-events: none;
}
.upm-lightbox-close {
  position: absolute; top: 18px; right: 18px;
  width: 38px; height: 38px;
  border-radius: 50%;
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.3);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .15s;
  font-size: 20px; line-height: 1;
}
.upm-lightbox-close:hover { background: rgba(255,255,255,.3); }
.upm-lightbox-label {
  position: absolute; bottom: 20px; left: 50%;
  transform: translateX(-50%);
  background: rgba(0,0,0,.5);
  color: rgba(255,255,255,.7);
  font-size: .72rem; padding: 5px 14px;
  border-radius: 20px; letter-spacing: .04em;
  pointer-events: none;
}

/* ── Identity row ── */
/* padding-top must be > half of avatar height (108/2 = 54) so avatar doesn't overlap content */
.upm-identity {
  padding: 66px 28px 4px 28px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .5rem;
}
.upm-name {
  font-family: 'Manrope', sans-serif;
  font-size: 1.4rem; font-weight: 800;
  color: #201b09;
  margin: 0 0 4px 0;
  line-height: 1.2;
}
.upm-sub {
  font-size: .8rem; color: #6b6355;
  display: flex; align-items: center; gap: 8px;
  flex-wrap: wrap;
  margin-top: 2px;
}
.upm-badge {
  padding: 2px 9px;
  border-radius: 20px;
  font-size: .65rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em;
  display: inline-block;
}
.upm-badge-admin    { background: rgba(157,124,57,.16); color: #7a5c14; }
.upm-badge-user     { background: rgba(108,117,125,.14); color: #495057; }
.upm-badge-allowed  { background: rgba(40,167,69,.13);  color: #155724; }
.upm-badge-restrict { background: rgba(108,117,125,.12); color: #6c757d; }
.upm-badge-provider { background: rgba(66,133,244,.13); color: #174ea6; }

/* ── Bio box ── */
.upm-bio {
  margin: 12px 28px 0;
  background: #faf7f2;
  border: 1px solid rgba(74,69,56,.1);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: .83rem; color: #4a4538; line-height: 1.6;
  font-style: italic;
}

/* ── Stats bar ── */
.upm-stats {
  display: flex;
  gap: 1px;
  margin: 16px 28px 0;
  border: 1px solid rgba(74,69,56,.1);
  border-radius: 12px;
  overflow: hidden;
}
.upm-stat {
  flex: 1; text-align: center;
  padding: 14px 8px;
  background: #fff;
  border-right: 1px solid rgba(74,69,56,.1);
}
.upm-stat:last-child { border-right: none; }
.upm-stat-num {
  font-family: 'Manrope', sans-serif;
  font-size: 1.4rem; font-weight: 800;
  color: #1a3325;
  line-height: 1;
}
.upm-stat-label {
  font-size: .63rem; color: #6b6355;
  text-transform: uppercase; letter-spacing: .05em;
  margin-top: 4px;
}

/* ── Info grid ── */
.upm-sections {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  margin: 16px 28px 0;
  border: 1px solid rgba(74,69,56,.1);
  border-radius: 12px;
  overflow: hidden;
  background: rgba(74,69,56,.08);
}
@media (max-width: 600px) { .upm-sections { grid-template-columns: 1fr; } }
.upm-section {
  padding: 14px 16px;
  background: #fff;
}
.upm-section-title {
  font-size: .63rem;
  text-transform: uppercase; letter-spacing: .07em;
  color: #9d7c39; font-weight: 700;
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 5px;
}
.upm-row {
  display: flex; justify-content: space-between;
  align-items: center; gap: 8px;
  margin-bottom: 7px;
}
.upm-row:last-child { margin-bottom: 0; }
.upm-label {
  font-size: .75rem; color: #6b6355;
  display: flex; align-items: center; gap: 5px;
  flex-shrink: 0;
}
.upm-value {
  font-size: .78rem; font-weight: 600;
  color: #201b09; text-align: right;
  word-break: break-word; max-width: 58%;
}
.upm-value.mono { font-family: monospace; font-size: .72rem; }

/* ── Sessions ── */
.upm-sessions-list {
  border: 1px solid rgba(74,69,56,.1);
  border-radius: 8px; overflow: hidden;
  margin-top: 4px;
}
.upm-session-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 12px;
  border-bottom: 1px solid rgba(74,69,56,.07);
  font-size: .75rem; gap: 8px;
}
.upm-session-item:last-child { border-bottom: none; }
.upm-session-dot {
  width: 8px; height: 8px;
  border-radius: 50%; flex-shrink: 0;
}
.dot-active   { background: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,.2); }
.dot-inactive { background: #adb5bd; }
.upm-session-info { flex: 1; color: #4a4538; }
.upm-session-time { color: #9d8c6e; font-size: .7rem; white-space: nowrap; }

/* ── Products section ── */
.upm-section-heading {
  margin: 20px 28px 12px;
  font-family: 'Manrope', sans-serif;
  font-size: .78rem; font-weight: 700;
  color: #1a3325;
  text-transform: uppercase; letter-spacing: .07em;
  display: flex; align-items: center; gap: 8px;
}
.upm-section-heading span {
  background: #1a3325;
  color: #fff;
  font-size: .65rem; padding: 2px 8px;
  border-radius: 20px;
}

/* ── Products grid ── */
.upm-products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
  gap: .75rem;
  margin: 0 28px 28px;
}
.upm-product-card {
  border: 1px solid rgba(74,69,56,.1);
  border-radius: 10px; overflow: hidden;
  background: #fff;
  transition: box-shadow .2s, transform .2s;
}
.upm-product-card:hover {
  box-shadow: 0 6px 20px rgba(26,51,37,.1);
  transform: translateY(-2px);
}
.upm-product-img {
  width: 100%; aspect-ratio: 4/3;
  object-fit: cover; display: block;
  background: #f0ede8;
  cursor: zoom-in;
  transition: opacity .2s;
}
.upm-product-img:hover { opacity: .9; }
.upm-product-img-placeholder {
  width: 100%; aspect-ratio: 4/3;
  background: linear-gradient(135deg, #e8e4dc, #d4cfc4);
  display: flex; align-items: center; justify-content: center;
}
.upm-product-info { padding: 10px; }
.upm-product-name {
  font-weight: 700; font-size: .8rem;
  color: #201b09;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 3px;
}
.upm-product-cat {
  font-size: .68rem; color: #9d7c39;
  text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px;
}
.upm-product-price {
  font-size: .82rem; font-weight: 700; color: #1a3325;
}
.upm-product-meta {
  display: flex; gap: 8px; margin-top: 5px;
  font-size: .68rem; color: #6b6355;
}
.upm-product-visibility {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 7px; border-radius: 20px; font-size: .63rem; font-weight: 700;
  margin-top: 5px;
}
.vis-shown  { background: rgba(40,167,69,.1);  color: #1a6631; }
.vis-hidden { background: rgba(220,53,69,.08); color: #a0202e; }

.upm-no-products {
  margin: 0 28px 28px;
  padding: 2.5rem;
  text-align: center;
  background: #faf7f2;
  border: 1px dashed rgba(74,69,56,.2);
  border-radius: 12px;
  color: #9d8c6e; font-size: .83rem;
}

/* ── Divider line between hero and identity ── */
.upm-hero-spacer {
  height: 56px; /* clears the protruding avatar */
  background: #fff;
}
</style>

<!-- ═══════════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════════════ -->
<script>
const IMG_BASE_USER    = '../uploads/profiles/';
const IMG_BASE_PRODUCT = '../uploads/products/';

const avColors = [
  {bg:'#d4f1e4',color:'#0f6e56'},{bg:'#e8e4ff',color:'#534ab7'},
  {bg:'#fde8d8',color:'#993c1d'},{bg:'#fdf0d4',color:'#854f0b'},
  {bg:'#fce4f0',color:'#993556'},{bg:'#dceeff',color:'#185fa5'},
];

function getInitialsJS(name) {
  const p = (name||'?').trim().split(' ');
  return (p[0][0] + (p[1] ? p[1][0] : '')).toUpperCase();
}

// ── Lightbox ──────────────────────────────────────────────────────────────────
function openLightbox(src, label) {
  const lb = document.createElement('div');
  lb.className = 'upm-lightbox';
  lb.innerHTML = `
    <img src="${src}" alt="${label}">
    <button class="upm-lightbox-close" onclick="this.parentElement.remove()">✕</button>
    <div class="upm-lightbox-label">${label}</div>
  `;
  lb.addEventListener('click', e => { if (e.target === lb) lb.remove(); });
  document.body.appendChild(lb);
  const onKey = e => {
    if (e.key === 'Escape') { lb.remove(); document.removeEventListener('keydown', onKey); }
  };
  document.addEventListener('keydown', onKey);
}

// ── Open modal ────────────────────────────────────────────────────────────────
function openDetail(uid) {
  document.querySelectorAll('.user-table-row').forEach(r => r.classList.remove('row-active'));
  const row = document.querySelector(`.user-table-row[data-uid="${uid}"]`);
  if (row) row.classList.add('row-active');

  const modal = document.getElementById('userProfileModal');
  document.getElementById('upmLoading').style.display = 'flex';
  document.getElementById('upmContent').style.display = 'none';
  document.getElementById('upmContent').innerHTML = '';
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  // Reset scroll position
  modal.querySelector('.upm-sheet').scrollTop = 0;

  fetch(`ajax/get_user_detail.php?uid=${uid}`)
    .then(r => {
      if (!r.ok) throw new Error('Server error ' + r.status);
      return r.json();
    })
    .then(data => {
      if (data.error) throw new Error(data.error);
      renderModal(data);
    })
    .catch(err => {
      document.getElementById('upmLoading').innerHTML =
        `<div style="text-align:center;padding:3rem;color:#a0202e;">
           <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:.5rem;opacity:.5;">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
               d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
           </svg>
           <p style="font-size:.82rem;">${upmEsc(err.message)}</p>
         </div>`;
    });
}

// ── Render ────────────────────────────────────────────────────────────────────
function renderModal(data) {
  const { user, sessions, products, stats } = data;
  const col      = avColors[user.id % avColors.length];
  const initials = getInitialsJS(user.name);

  /* Avatar */
  const avatarHTML = user.user_image
    ? `<img src="${IMG_BASE_USER}${upmEsc(user.user_image)}" class="upm-avatar" alt="${upmEsc(user.name)}"
           onclick="openLightbox('${IMG_BASE_USER}${upmEsc(user.user_image)}','Profile Photo — ${upmEsc(user.name)}')">`
    : `<div class="upm-avatar-initials" style="background:${col.bg};color:${col.color};">${initials}</div>`;

  /* Background */
  const heroContent = user.background_image
    ? `<img src="${IMG_BASE_USER}${upmEsc(user.background_image)}" class="upm-hero-bg" alt="background"
           onclick="openLightbox('${IMG_BASE_USER}${upmEsc(user.background_image)}','Cover Photo — ${upmEsc(user.name)}')">
       <div class="upm-hero-hint">🔍 Click to view cover</div>`
    : '';

  /* Badges */
  const roleBadge = user.is_admin == 1
    ? `<span class="upm-badge upm-badge-admin">Admin</span>`
    : `<span class="upm-badge upm-badge-user">User</span>`;
  const permBadge = user.can_post == 1
    ? `<span class="upm-badge upm-badge-allowed">✓ Can post</span>`
    : `<span class="upm-badge upm-badge-restrict">Restricted</span>`;
  const providerBadge = user.provider
    ? `<span class="upm-badge upm-badge-provider">${upmEsc(user.provider)}</span>`
    : '';

  /* Sessions */
  const activeSess = sessions.filter(s => s.is_active == 1).length;
  const sessHTML = sessions.length === 0
    ? `<div class="upm-session-item" style="color:#9d8c6e;">No sessions recorded</div>`
    : sessions.map(s => `
        <div class="upm-session-item">
          <div class="upm-session-dot ${s.is_active == 1 ? 'dot-active' : 'dot-inactive'}"></div>
          <div class="upm-session-info">
            ${s.is_active == 1 ? '<strong>Active</strong>' : 'Expired'} — ${upmFmtDate(s.created_at)}
          </div>
          <div class="upm-session-time">Exp: ${upmFmtDate(s.expires_at)}</div>
        </div>`).join('');

  /* Products */
  const prodHTML = products.length === 0
    ? `<div class="upm-no-products">
         <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:.5rem;opacity:.3;">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
             d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
         </svg>
         <p>This user has not posted any products yet.</p>
       </div>`
    : `<div class="upm-products-grid">${products.map(p => upmProductCard(p, user.id)).join('')}</div>`;

  const html = `
    <!-- ── HERO (overflow:visible parent; inner clip) ── -->
    <div class="upm-hero">
      <div class="upm-hero-inner">
        ${heroContent}
        <div class="upm-hero-overlay"></div>
      </div>
      <!-- Avatar protrudes below hero-inner -->
      <div class="upm-avatar-wrap">${avatarHTML}</div>
    </div>

    <!-- Identity (top padding clears avatar) -->
    <div class="upm-identity">
      <div>
        <div class="upm-name" id="upm-username">${upmEsc(user.name)}</div>
        <div class="upm-sub">
          <span>${upmEsc(user.first_name || '')} ${upmEsc(user.last_name || '')}</span>
          ${roleBadge} ${permBadge} ${providerBadge}
        </div>
      </div>
      <div style="text-align:right;font-size:.72rem;color:#9d8c6e;padding-top:4px;flex-shrink:0;">
        ID #${user.id}<br>
        Joined ${upmFmtDate(user.created_at)}
      </div>
    </div>

    ${user.bio ? `<div class="upm-bio">"${upmEsc(user.bio)}"</div>` : ''}

    <!-- Stats -->
    <div class="upm-stats">
      <div class="upm-stat">
        <div class="upm-stat-num">${stats.total_products}</div>
        <div class="upm-stat-label">Products</div>
      </div>
      <div class="upm-stat">
        <div class="upm-stat-num">${stats.total_likes_received}</div>
        <div class="upm-stat-label">Likes Received</div>
      </div>
      <div class="upm-stat">
        <div class="upm-stat-num">${stats.total_comments_received}</div>
        <div class="upm-stat-label">Comments</div>
      </div>
      <div class="upm-stat">
        <div class="upm-stat-num">${activeSess}</div>
        <div class="upm-stat-label">Active Sessions</div>
      </div>
    </div>

    <!-- Info grid -->
    <div class="upm-sections">
      <div class="upm-section">
        <div class="upm-section-title">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Account
        </div>
        <div class="upm-row"><span class="upm-label">Email</span><span class="upm-value">${upmEsc(user.email||'—')}</span></div>
        <div class="upm-row"><span class="upm-label">First name</span><span class="upm-value">${upmEsc(user.first_name||'—')}</span></div>
        <div class="upm-row"><span class="upm-label">Last name</span><span class="upm-value">${upmEsc(user.last_name||'—')}</span></div>
        <div class="upm-row"><span class="upm-label">Gender</span><span class="upm-value">${upmEsc(user.gender||'—')}</span></div>
      </div>

      <div class="upm-section">
        <div class="upm-section-title">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          Contact
        </div>
        <div class="upm-row"><span class="upm-label">Phone 1</span><span class="upm-value">${upmEsc(user.phone1||'—')}</span></div>
        <div class="upm-row"><span class="upm-label">Phone 2</span><span class="upm-value">${upmEsc(user.phone2||'—')}</span></div>
        <div class="upm-row"><span class="upm-label">Provider</span><span class="upm-value">${upmEsc(user.provider||'Email/Password')}</span></div>
        <div class="upm-row"><span class="upm-label">Provider ID</span><span class="upm-value mono">${user.provider_id ? upmEsc(String(user.provider_id).substring(0,16))+'…' : '—'}</span></div>
      </div>

      <div class="upm-section">
        <div class="upm-section-title">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          Permissions
        </div>
        <div class="upm-row"><span class="upm-label">Role</span><span class="upm-value">${user.is_admin==1?'Administrator':'Regular User'}</span></div>
        <div class="upm-row"><span class="upm-label">Can post</span><span class="upm-value">${user.can_post==1?'✓ Yes':'✗ No'}</span></div>
        <div class="upm-row"><span class="upm-label">Requesting</span><span class="upm-value">${user.request_post_permission==1?'⏳ Yes':'No'}</span></div>
        <div class="upm-row"><span class="upm-label">Last updated</span><span class="upm-value">${upmFmtDate(user.updated_at)}</span></div>
      </div>

      <div class="upm-section">
        <div class="upm-section-title">
          <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
          Sessions (${sessions.length})
        </div>
        <div class="upm-sessions-list">${sessHTML}</div>
      </div>
    </div>

    <!-- Products -->
    <div class="upm-section-heading">
      Products Posted <span>${products.length}</span>
    </div>
    ${prodHTML}
  `;

  const content = document.getElementById('upmContent');
  content.innerHTML = html;
  document.getElementById('upmLoading').style.display = 'none';
  content.style.display = 'block';
}

// ── Product card — clickable, opens product detail modal ─────────────────────
function upmProductCard(p, uid) {
  const imgSrc = p.main_image ? `${IMG_BASE_PRODUCT}${upmEsc(p.main_image)}` : null;
  const imgHTML = imgSrc
    ? `<img src="${imgSrc}" class="upm-product-img" alt="${upmEsc(p.name)}" loading="lazy">`
    : `<div class="upm-product-img-placeholder">
         <svg width="28" height="28" fill="none" stroke="#9d8c6e" viewBox="0 0 24 24">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
             d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
         </svg>
       </div>`;
  const price    = p.prices    ? `$${parseFloat(p.prices).toLocaleString()}` : 'Free';
  const discount = p.discounts ? ` <span style="font-size:.68rem;color:#9d7c39;">-${p.discounts}%</span>` : '';
  return `
    <div class="upm-product-card" onclick="upmOpenProduct(${p.id}, ${uid||0})" style="cursor:pointer;" title="View product detail">
      ${imgHTML}
      <div class="upm-product-info">
        <div class="upm-product-name" title="${upmEsc(p.name)}">${upmEsc(p.name)}</div>
        <div class="upm-product-cat">${upmEsc(p.category||'Uncategorized')}</div>
        <div class="upm-product-price">${price}${discount}</div>
        <div class="upm-product-meta">
          <span>♥ ${p.like_count}</span>
          <span>💬 ${p.comment_count}</span>
          ${p.location ? `<span>📍 ${upmEsc(p.location)}</span>` : ''}
        </div>
        <div class="upm-product-visibility ${p.showed==1?'vis-shown':'vis-hidden'}">
          ${p.showed==1?'● Visible':'● Hidden'}
        </div>
        <div style="margin-top:6px;font-size:.65rem;color:#1a3325;font-weight:700;opacity:.75;">View Details →</div>
      </div>
    </div>`;
}

// ── Opens product detail modal (works on any admin page) ─────────────────────
function upmOpenProduct(pid, fromUid) {
  // If admin_product.php's own modal is present, use it
  if (typeof openProductDetail === 'function') {
    closeProfileModalBtn();
    setTimeout(() => openProductDetail(pid), 160);
    return;
  }
  // Otherwise use our own inline product modal (closes user modal, opens product modal)
  closeProfileModalBtn();
  setTimeout(() => upmPdOpen(pid, fromUid), 160);
}

// ── Close ─────────────────────────────────────────────────────────────────────
function closeProfileModal(e) {
  if (e.target === document.getElementById('userProfileModal')) closeProfileModalBtn();
}
function closeProfileModalBtn() {
  document.getElementById('userProfileModal').classList.remove('open');
  document.body.style.overflow = '';
  document.querySelectorAll('.user-table-row').forEach(r => r.classList.remove('row-active'));
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && !document.querySelector('.upm-lightbox')) closeProfileModalBtn();
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function upmEsc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
}
function upmFmtDate(str) {
  if (!str || str === '0000-00-00 00:00:00') return '—';
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
}
</script>

<!-- ═══════════════════════════════════════════════════════════════
     INLINE PRODUCT DETAIL MODAL
     Self-contained — works on admin_user.php without admin_product.php
════════════════════════════════════════════════════════════════════ -->
<div id="upmPdModal" class="upm-pd-overlay" onclick="upmPdOverlayClick(event)" role="dialog" aria-modal="true">
  <div class="upm-pd-sheet">
    <button class="upm-pd-close" onclick="upmPdClose()" aria-label="Close">
      <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
    <!-- Back to user profile -->
    <button class="upm-pd-back" onclick="upmPdGoBack()" title="Back to user profile">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
      </svg>
      Back to Profile
    </button>
    <div class="upm-pd-loading" id="upmPdLoading">
      <div class="upm-pd-spinner"></div>
      <span>Loading product…</span>
    </div>
    <div id="upmPdContent" style="display:none;"></div>
  </div>
</div>

<style>
/* ── Product modal overlay ── */
.upm-pd-overlay {
  position: fixed; inset: 0;
  background: rgba(10,20,14,.65);
  backdrop-filter: blur(6px);
  z-index: 1100; /* above user modal (1000) */
  display: flex; align-items: center; justify-content: center;
  padding: 1rem;
  opacity: 0; visibility: hidden;
  transition: opacity .22s, visibility .22s;
}
.upm-pd-overlay.open { opacity: 1; visibility: visible; }

.upm-pd-sheet {
  background: #fff;
  border-radius: 20px;
  width: 100%; max-width: 820px;
  max-height: 92vh;
  overflow-y: auto; overflow-x: hidden;
  position: relative;
  box-shadow: 0 32px 80px rgba(0,0,0,.32);
  transform: translateY(28px) scale(.97);
  transition: transform .3s cubic-bezier(.34,1.56,.64,1);
  scrollbar-width: thin;
  scrollbar-color: rgba(26,51,37,.18) transparent;
}
.upm-pd-overlay.open .upm-pd-sheet { transform: translateY(0) scale(1); }
.upm-pd-sheet::-webkit-scrollbar { width: 4px; }
.upm-pd-sheet::-webkit-scrollbar-thumb { background: rgba(26,51,37,.18); border-radius: 4px; }

/* Close & back buttons */
.upm-pd-close {
  position: absolute; top: 14px; right: 14px;
  width: 34px; height: 34px; border-radius: 50%;
  border: 1px solid rgba(74,69,56,.18);
  background: rgba(255,255,255,.92);
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  color: #6b6355; z-index: 20; transition: all .18s;
}
.upm-pd-close:hover { background: #fff; color: #a0202e; border-color: rgba(160,32,46,.3); }

.upm-pd-back {
  position: absolute; top: 14px; left: 14px;
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 12px;
  background: rgba(26,51,37,.08);
  border: 1px solid rgba(26,51,37,.15);
  border-radius: 20px;
  color: #1a3325; font-size: .72rem; font-weight: 700;
  cursor: pointer; z-index: 20; font-family: inherit;
  transition: all .18s;
}
.upm-pd-back:hover { background: rgba(26,51,37,.15); }

/* Loading */
.upm-pd-loading {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 6rem 2rem; gap: 1rem;
  color: #6b6355; font-size: .875rem;
}
.upm-pd-spinner {
  width: 30px; height: 30px;
  border: 3px solid rgba(26,51,37,.12);
  border-top-color: #1a3325;
  border-radius: 50%;
  animation: upm-pd-spin .7s linear infinite;
}
@keyframes upm-pd-spin { to { transform: rotate(360deg); } }

/* Gallery */
.upm-pd-gallery { padding: 56px 24px 0; }
.upm-pd-main-wrap {
  position: relative; width: 100%;
  aspect-ratio: 16/7;
  border-radius: 14px; overflow: hidden;
  background: #f0ede8; cursor: zoom-in;
}
.upm-pd-main-img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: transform .3s;
}
.upm-pd-main-wrap:hover .upm-pd-main-img { transform: scale(1.03); }
.upm-pd-zoom-hint {
  position: absolute; bottom: 10px; right: 10px;
  background: rgba(0,0,0,.42); backdrop-filter: blur(4px);
  color: #fff; font-size: .6rem; font-weight: 700;
  padding: 4px 10px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: .05em;
  pointer-events: none;
}
.upm-pd-no-img {
  width: 100%; aspect-ratio: 16/7;
  background: linear-gradient(135deg,#e8e4dc,#d4cfc4);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: #9d8c6e; font-size: .82rem;
}
.upm-pd-thumbs { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.upm-pd-thumb {
  width: 58px; height: 58px; border-radius: 8px;
  object-fit: cover; cursor: pointer;
  border: 2px solid transparent;
  transition: border-color .15s, transform .15s;
}
.upm-pd-thumb:hover { border-color: #1a3325; transform: scale(1.06); }
.upm-pd-thumb.active { border-color: #1a3325; }

/* Body */
.upm-pd-body { padding: 20px 24px 28px; }

.upm-pd-title-row {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 1rem;
  flex-wrap: wrap; margin-bottom: 10px;
}
.upm-pd-title {
  font-family: 'Manrope', sans-serif;
  font-size: 1.4rem; font-weight: 800;
  color: #201b09; margin: 0; line-height: 1.2;
}
.upm-pd-vis {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 11px; border-radius: 20px;
  font-size: .7rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em; flex-shrink: 0;
}
.upm-pd-vis-shown  { background: rgba(40,167,69,.12);  color: #1a6631; }
.upm-pd-vis-hidden { background: rgba(220,53,69,.08);  color: #a0202e; }

.upm-pd-price-row {
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 16px; flex-wrap: wrap;
}
.upm-pd-price {
  font-family: 'Manrope', sans-serif;
  font-size: 1.55rem; font-weight: 800; color: #1a3325;
}
.upm-pd-discount { background: rgba(157,124,57,.14); color: #7a5c14; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.upm-pd-original  { font-size: .83rem; color: #9d8c6e; text-decoration: line-through; }

/* Meta grid */
.upm-pd-meta {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; border: 1px solid rgba(74,69,56,.1);
  border-radius: 12px; overflow: hidden;
  background: rgba(74,69,56,.07); margin-bottom: 14px;
}
@media (max-width:540px) { .upm-pd-meta { grid-template-columns: 1fr; } }
.upm-pd-meta-cell { padding: 11px 14px; background: #fff; }
.upm-pd-meta-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .07em; color: #9d7c39; font-weight: 700; margin-bottom: 3px; }
.upm-pd-meta-value { font-size: .82rem; font-weight: 600; color: #201b09; }

/* Stats */
.upm-pd-stats {
  display: flex; gap: 1px;
  border: 1px solid rgba(74,69,56,.1); border-radius: 12px;
  overflow: hidden; margin-bottom: 14px;
}
.upm-pd-stat { flex: 1; text-align: center; padding: 12px 8px; background: #fff; border-right: 1px solid rgba(74,69,56,.1); }
.upm-pd-stat:last-child { border-right: none; }
.upm-pd-stat-num { font-family: 'Manrope', sans-serif; font-size: 1.3rem; font-weight: 800; color: #1a3325; line-height: 1; }
.upm-pd-stat-lbl { font-size: .62rem; color: #6b6355; text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Description */
.upm-pd-section-lbl { font-size: .6rem; text-transform: uppercase; letter-spacing: .07em; color: #9d7c39; font-weight: 700; margin-bottom: 7px; }
.upm-pd-desc {
  font-size: .83rem; color: #4a4538; line-height: 1.7;
  background: #faf7f2; border: 1px solid rgba(74,69,56,.1);
  border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;
}

/* Seller card */
.upm-pd-seller {
  display: flex; align-items: center; gap: 12px;
  background: #faf7f2; border: 1px solid rgba(74,69,56,.1);
  border-radius: 12px; padding: 12px 14px; margin-bottom: 14px;
}
.upm-pd-seller-av {
  width: 42px; height: 42px; border-radius: 50%;
  object-fit: cover; border: 2px solid #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.1); flex-shrink: 0;
}
.upm-pd-seller-ini {
  width: 42px; height: 42px; border-radius: 50%;
  border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 800; flex-shrink: 0;
  font-family: 'Manrope', sans-serif;
}
.upm-pd-seller-name { font-weight: 700; font-size: .86rem; color: #201b09; }
.upm-pd-seller-sub  { font-size: .72rem; color: #6b6355; margin-top: 2px; }
.upm-pd-seller-btn {
  margin-left: auto; padding: 6px 13px;
  background: #1a3325; color: #fff;
  border: none; border-radius: 8px;
  font-size: .7rem; font-weight: 700; cursor: pointer;
  text-transform: uppercase; letter-spacing: .04em;
  font-family: inherit; flex-shrink: 0; transition: background .18s;
}
.upm-pd-seller-btn:hover { background: #2a5038; }

/* Comments */
.upm-pd-comment { display: flex; gap: 8px; margin-bottom: 8px; }
.upm-pd-comment-av {
  width: 26px; height: 26px; border-radius: 50%;
  background: rgba(74,69,56,.1); display: flex;
  align-items: center; justify-content: center;
  font-size: 9px; font-weight: 700; flex-shrink: 0;
  color: #6b6355;
}
.upm-pd-comment-bubble { flex: 1; background: #faf7f2; border: 1px solid rgba(74,69,56,.1); border-radius: 10px; padding: 8px 12px; }
.upm-pd-comment-user { font-size: .7rem; font-weight: 700; color: #1a3325; }
.upm-pd-comment-text { font-size: .77rem; color: #4a4538; margin-top: 2px; line-height: 1.5; }
.upm-pd-comment-time { font-size: .63rem; color: #9d8c6e; margin-top: 3px; }

/* Actions */
.upm-pd-actions { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
.upm-pd-act-btn {
  flex: 1; min-width: 120px; padding: 10px 14px;
  border-radius: 8px; font-family: inherit; font-size: .78rem; font-weight: 700;
  cursor: pointer; border: 1px solid transparent;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: all .18s; text-decoration: none; text-align: center;
}
.upm-pd-act-show   { background: rgba(40,167,69,.1);   border-color: rgba(40,167,69,.25); color: #1a6631; }
.upm-pd-act-show:hover { background: rgba(40,167,69,.18); }
.upm-pd-act-hide   { background: rgba(220,53,69,.08);  border-color: rgba(220,53,69,.22); color: #a0202e; }
.upm-pd-act-hide:hover { background: rgba(220,53,69,.15); }
.upm-pd-act-delete { background: rgba(126,0,10,.06);   border-color: rgba(126,0,10,.18);  color: #7e000a; }
.upm-pd-act-delete:hover { background: rgba(126,0,10,.12); }
.upm-pd-act-view   { background: rgba(26,51,37,.06);   border-color: rgba(74,69,56,.2);   color: #1a3325; }
.upm-pd-act-view:hover { background: rgba(26,51,37,.12); }
</style>

<script>
const UPM_PD_IMG    = '../uploads/products/';
const UPM_PD_AVATAR = '../uploads/profiles/';

// remember which user we came from so the back button works
let _upmPdFromUid = null;

function upmPdOpen(pid, fromUid) {
  _upmPdFromUid = fromUid || null;

  const modal = document.getElementById('upmPdModal');
  document.getElementById('upmPdLoading').style.display = 'flex';
  document.getElementById('upmPdContent').style.display = 'none';
  document.getElementById('upmPdContent').innerHTML = '';
  modal.classList.add('open');
  modal.querySelector('.upm-pd-sheet').scrollTop = 0;
  document.body.style.overflow = 'hidden';

  fetch(`ajax/get_product_detail.php?pid=${pid}`)
    .then(r => { if (!r.ok) throw new Error('Server error ' + r.status); return r.json(); })
    .then(data => { if (data.error) throw new Error(data.error); upmPdRender(data); })
    .catch(err => {
      document.getElementById('upmPdLoading').innerHTML =
        `<div style="text-align:center;padding:3rem;color:#a0202e;font-size:.82rem;">
          <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:.5rem;opacity:.5;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p>${upmPdEsc(err.message)}</p>
        </div>`;
    });
}

function upmPdRender(data) {
  const { product: p, stats, comments } = data;

  // Images
  const imgs = [p.main_image, p.image1, p.image2, p.image3, p.image4, p.image5]
    .filter(Boolean).map(i => UPM_PD_IMG + upmPdEsc(i));

  let galleryHTML;
  if (imgs.length) {
    const thumbsHTML = imgs.length > 1
      ? `<div class="upm-pd-thumbs">${imgs.map((src,i) =>
          `<img src="${src}" class="upm-pd-thumb${i===0?' active':''}"
               onclick="upmPdSetMain('${src}',this)" alt="img ${i+1}">`
        ).join('')}</div>` : '';
    galleryHTML = `
      <div class="upm-pd-main-wrap" onclick="openLightbox('${imgs[0]}','${upmPdEsc(p.name)}')">
        <img src="${imgs[0]}" class="upm-pd-main-img" id="upmPdMainImg" alt="${upmPdEsc(p.name)}">
        <div class="upm-pd-zoom-hint">🔍 Click to enlarge</div>
      </div>${thumbsHTML}`;
  } else {
    galleryHTML = `<div class="upm-pd-no-img">No images uploaded</div>`;
  }

  // Price
  const price = p.prices ? `$${parseFloat(p.prices).toLocaleString('en-US',{minimumFractionDigits:2})}` : 'Free';
  const discountHTML = p.discounts > 0
    ? `<span class="upm-pd-discount">-$${parseFloat(p.discounts).toLocaleString()} off</span>
       <span class="upm-pd-original">$${(parseFloat(p.prices)+parseFloat(p.discounts)).toLocaleString('en-US',{minimumFractionDigits:2})}</span>`
    : '';

  // Visibility
  const visHTML = p.showed == 1
    ? `<span class="upm-pd-vis upm-pd-vis-shown">● Visible</span>`
    : `<span class="upm-pd-vis upm-pd-vis-hidden">● Hidden</span>`;

  // Seller
  const avColors = [
    {bg:'#d4f1e4',color:'#0f6e56'},{bg:'#e8e4ff',color:'#534ab7'},
    {bg:'#fde8d8',color:'#993c1d'},{bg:'#fdf0d4',color:'#854f0b'},
    {bg:'#fce4f0',color:'#993556'},{bg:'#dceeff',color:'#185fa5'},
  ];
  const col = avColors[(p.owner_id||0) % avColors.length];
  const ini = (p.owner_name||'?').trim().split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
  const sellerAvHTML = p.owner_avatar
    ? `<img src="${UPM_PD_AVATAR}${upmPdEsc(p.owner_avatar)}" class="upm-pd-seller-av" alt="${upmPdEsc(p.owner_name)}">`
    : `<div class="upm-pd-seller-ini" style="background:${col.bg};color:${col.color};">${ini}</div>`;

  // Comments
  const commentsHTML = comments.length === 0
    ? `<p style="font-size:.77rem;color:#9d8c6e;margin:0;">No comments yet.</p>`
    : comments.map(c => {
        const ci = (c.user_name||'?').trim().split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
        return `<div class="upm-pd-comment">
          <div class="upm-pd-comment-av">${ci}</div>
          <div class="upm-pd-comment-bubble">
            <div class="upm-pd-comment-user">${upmPdEsc(c.user_name)}</div>
            <div class="upm-pd-comment-text">${upmPdEsc(c.content)}</div>
            <div class="upm-pd-comment-time">${upmPdFmtDate(c.created_at)}</div>
          </div>
        </div>`;
      }).join('');

  // Toggle + delete actions
  const toggleBtn = p.showed == 1
    ? `<a href="../controllers/product.php?action=toggle_visibility&id=${p.id}&status=0" class="upm-pd-act-btn upm-pd-act-hide">🚫 Hide</a>`
    : `<a href="../controllers/product.php?action=toggle_visibility&id=${p.id}&status=1" class="upm-pd-act-btn upm-pd-act-show">✓ Make Visible</a>`;

  document.getElementById('upmPdContent').innerHTML = `
    <div class="upm-pd-gallery">${galleryHTML}</div>
    <div class="upm-pd-body">
      <div class="upm-pd-title-row">
        <h2 class="upm-pd-title">${upmPdEsc(p.name)}</h2>
        ${visHTML}
      </div>
      <div class="upm-pd-price-row">
        <div class="upm-pd-price">${price}</div>
        ${discountHTML}
      </div>
      <div class="upm-pd-meta">
        <div class="upm-pd-meta-cell"><div class="upm-pd-meta-label">Category</div><div class="upm-pd-meta-value">${upmPdEsc(p.category_name||'—')}</div></div>
        <div class="upm-pd-meta-cell"><div class="upm-pd-meta-label">Location</div><div class="upm-pd-meta-value">${upmPdEsc(p.location||'—')}</div></div>
        <div class="upm-pd-meta-cell"><div class="upm-pd-meta-label">Posted</div><div class="upm-pd-meta-value">${upmPdFmtDate(p.created_at)}</div></div>
        <div class="upm-pd-meta-cell"><div class="upm-pd-meta-label">Product ID</div><div class="upm-pd-meta-value">#${p.id}</div></div>
      </div>
      <div class="upm-pd-stats">
        <div class="upm-pd-stat"><div class="upm-pd-stat-num">${stats.like_count}</div><div class="upm-pd-stat-lbl">Likes</div></div>
        <div class="upm-pd-stat"><div class="upm-pd-stat-num">${stats.comment_count}</div><div class="upm-pd-stat-lbl">Comments</div></div>
        <div class="upm-pd-stat"><div class="upm-pd-stat-num">${imgs.length}</div><div class="upm-pd-stat-lbl">Images</div></div>
      </div>
      ${p.description ? `<div class="upm-pd-section-lbl">Description</div><div class="upm-pd-desc">${upmPdEsc(p.description)}</div>` : ''}
      <div class="upm-pd-section-lbl">Seller</div>
      <div class="upm-pd-seller">
        ${sellerAvHTML}
        <div>
          <div class="upm-pd-seller-name">${upmPdEsc(p.owner_name||'Unknown')}</div>
          <div class="upm-pd-seller-sub">${upmPdEsc(p.owner_email||'')}${p.owner_phone1?' · '+upmPdEsc(p.owner_phone1):''}</div>
        </div>
        <button class="upm-pd-seller-btn" onclick="upmPdViewSeller(${p.owner_id})">View Profile</button>
      </div>
      ${comments.length > 0 ? `<div class="upm-pd-section-lbl">Recent Comments</div>${commentsHTML}` : ''}
      <div class="upm-pd-actions">
        ${toggleBtn}
        <a href="../controllers/product.php?action=delete&id=${p.id}" class="upm-pd-act-btn upm-pd-act-delete"
           onclick="return confirm('Delete this product permanently?')">🗑 Delete</a>
        <a href="product_detail.php?id=${p.id}" class="upm-pd-act-btn upm-pd-act-view" target="_blank">↗ View on Site</a>
      </div>
    </div>`;

  document.getElementById('upmPdLoading').style.display = 'none';
  document.getElementById('upmPdContent').style.display = 'block';
}

function upmPdSetMain(src, thumb) {
  document.getElementById('upmPdMainImg').src = src;
  document.querySelector('.upm-pd-main-wrap').onclick = () => openLightbox(src,'');
  document.querySelectorAll('.upm-pd-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

function upmPdViewSeller(uid) {
  upmPdClose();
  setTimeout(() => openDetail(uid), 160);
}

function upmPdGoBack() {
  upmPdClose();
  if (_upmPdFromUid) {
    setTimeout(() => openDetail(_upmPdFromUid), 160);
  }
}

function upmPdClose() {
  document.getElementById('upmPdModal').classList.remove('open');
  document.body.style.overflow = '';
}

function upmPdOverlayClick(e) {
  if (e.target === document.getElementById('upmPdModal')) upmPdClose();
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && !document.querySelector('.upm-lightbox')) {
    if (document.getElementById('upmPdModal').classList.contains('open')) {
      upmPdGoBack();
    }
  }
});

function upmPdEsc(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
}
function upmPdFmtDate(str) {
  if (!str || str === '0000-00-00 00:00:00') return '—';
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
}
</script>