'use strict';

const LOCATIONS = [
  {
    id: 1,
    name: 'River Mall',
    branch: 'North Branch',
    category: 'Mall',
    address: 'Al Yasmin District, Riyadh',
    current: 'Medium',
    schedule: {
      weekday_morning: 'Low',
      weekday_afternoon: 'High',
      weekday_evening: 'Medium',
      weekend_morning: 'Medium',
      weekend_afternoon: 'High',
      weekend_evening: 'High',
    },
    alternatives: ['Palm Mall', 'Sky Plaza'],
    lat: 24.8103, lng: 46.6753,
  },
  {
    id: 2,
    name: 'River Mall',
    branch: 'East Branch',
    category: 'Mall',
    address: 'Al Rawdah District, Riyadh',
    current: 'Low',
    schedule: {
      weekday_morning: 'Low',
      weekday_afternoon: 'Medium',
      weekday_evening: 'Medium',
      weekend_morning: 'Low',
      weekend_afternoon: 'High',
      weekend_evening: 'High',
    },
    alternatives: ['Palm Mall', 'City Walk'],
    lat: 24.7342, lng: 46.7891,
  },
  {
    id: 3,
    name: 'Bean House',
    branch: 'King Fahd Road',
    category: 'Cafe',
    address: 'King Fahd Road, Riyadh',
    current: 'High',
    schedule: {
      weekday_morning: 'Medium',
      weekday_afternoon: 'High',
      weekday_evening: 'High',
      weekend_morning: 'Low',
      weekend_afternoon: 'High',
      weekend_evening: 'High',
    },
    alternatives: ['Roast Lab', 'Daily Cup'],
    lat: 24.7136, lng: 46.6753,
  },
  {
    id: 4,
    name: 'Bean House',
    branch: 'Olaya Branch',
    category: 'Cafe',
    address: 'Al Olaya, Riyadh',
    current: 'Low',
    schedule: {
      weekday_morning: 'Low',
      weekday_afternoon: 'Low',
      weekday_evening: 'Medium',
      weekend_morning: 'Low',
      weekend_afternoon: 'Medium',
      weekend_evening: 'High',
    },
    alternatives: ['Roast Lab', 'Moon Cafe'],
    lat: 24.7115, lng: 46.6749,
  },
  {
    id: 5,
    name: 'Grocery Hub',
    branch: 'Malaz Branch',
    category: 'Supermarket',
    address: 'Al Malaz, Riyadh',
    current: 'Low',
    schedule: {
      weekday_morning: 'Low',
      weekday_afternoon: 'Medium',
      weekday_evening: 'Medium',
      weekend_morning: 'Medium',
      weekend_afternoon: 'High',
      weekend_evening: 'Medium',
    },
    alternatives: ['Fresh Market', 'QuickShop'],
    lat: 24.6877, lng: 46.7219,
  },
];

const MOCK_NOTIFICATIONS = [
  { id: 1, read: false, title: 'River Mall is getting busy',            time: '5 minutes ago' },
  { id: 2, read: false, title: 'Best time to visit Bean House: now!',  time: '22 minutes ago' },
  { id: 3, read: true,  title: 'Your favorite spot is quieter today',  time: '2 hours ago' },
  { id: 4, read: true,  title: 'Grocery Hub congestion dropped to Low', time: 'Yesterday' },
];

// ─── Session state ────────────────────────────────────
// Both currentUser and favoriteIds live in sessionStorage
// so they survive page navigation but reset when the tab closes

let currentUser = sessionStorage.getItem('t2g_user')
                  ? JSON.parse(sessionStorage.getItem('t2g_user'))
                  : null;

let favoriteIds = sessionStorage.getItem('t2g_favs')
                  ? JSON.parse(sessionStorage.getItem('t2g_favs'))
                  : [];

let notificationsEnabled = true;

function saveFavs() {
  sessionStorage.setItem('t2g_favs', JSON.stringify(favoriteIds));
}

// ─── Utilities ───────────────────────────────────────

function badgeClass(level) {
  const map = { low: 'badge-low', medium: 'badge-medium', high: 'badge-high' };
  return map[level.toLowerCase()] || 'badge-low';
}

function badge(level) {
  return `<span class="badge ${badgeClass(level)}">${level}</span>`;
}

function setEl(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function setHTML(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

function showAlert(elementId, message, type = 'info') {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
  setTimeout(() => { el.innerHTML = ''; }, 3500);
}

function isWeekend(date) {
  const day = date.getDay();
  return day === 5 || day === 6;
}

function getTimeSlot(date) {
  const hour = date.getHours();
  if (hour >= 6  && hour < 12) return 'morning';
  if (hour >= 12 && hour < 18) return 'afternoon';
  return 'evening';
}

function getPredictedCongestion(location, date) {
  const dayType  = isWeekend(date) ? 'weekend' : 'weekday';
  const timeSlot = getTimeSlot(date);
  return location.schedule[`${dayType}_${timeSlot}`] || 'Medium';
}

function getBestTimeLabel(location, date) {
  const dayType = isWeekend(date) ? 'weekend' : 'weekday';
  const slots = [
    { key: `${dayType}_morning`,   label: '6:00 AM – 12:00 PM' },
    { key: `${dayType}_afternoon`, label: '12:00 PM – 6:00 PM' },
    { key: `${dayType}_evening`,   label: '6:00 PM – 11:00 PM' },
  ];
  const low = slots.find(s => location.schedule[s.key] === 'Low');
  if (low) return low.label;
  const med = slots.find(s => location.schedule[s.key] === 'Medium');
  if (med) return med.label;
  return 'No low-congestion window today';
}

function avatarLetter(name) {
  return name ? name.charAt(0).toUpperCase() : 'G';
}

// ─── Sidebar ─────────────────────────────────────────

function buildSidebar() {
  const nav = document.querySelector('nav#mainNav');
  if (!nav) return;

  const currentFile = window.location.pathname.split('/').pop() || 'index.html';

  function navItem(href, icon, label, page) {
    const active = (currentFile === page || (currentFile === '' && page === 'index.html')) ? 'active' : '';
    return `<a class="nav-link ${active}" href="${href}">
              <span class="nav-icon">${icon}</span>${label}
            </a>`;
  }

  if (currentUser) {
    nav.innerHTML = `
      ${navItem('index.html', '🏠', 'Home', 'index.html')}
      <a class="sidebar-profile ${currentFile === 'profile.html' ? 'active' : ''}" href="profile.html">
        <div class="profile-avatar">${avatarLetter(currentUser.name)}</div>
        <div class="profile-info">
          <span class="profile-name">${currentUser.name}</span>
          <span class="profile-role">View profile</span>
        </div>
      </a>
      ${navItem('favorites.html', '❤️', 'Favorites', 'favorites.html')}
    `;
  } else {
    nav.innerHTML = `
      ${navItem('index.html',  '🏠', 'Home',    'index.html')}
      ${navItem('signin.html', '🔑', 'Sign in', 'signin.html')}
    `;
  }
}

// ─── Home Page ───────────────────────────────────────

function initHomePage() {
  buildSidebar();
  const input = document.getElementById('searchInput');
  if (!input) return;
  renderResults('');
  input.addEventListener('input', () => renderResults(input.value));
  input.addEventListener('keydown', e => { if (e.key === 'Enter') renderResults(input.value); });
  document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      renderResults(document.getElementById('searchInput').value, chip.dataset.cat);
    });
  });
}

function renderResults(query = '', cat = 'all') {
  const q = query.toLowerCase().trim();
  const filtered = LOCATIONS.filter(loc => {
    const matchQ = !q || loc.name.toLowerCase().includes(q) || loc.branch.toLowerCase().includes(q) || loc.address.toLowerCase().includes(q);
    const matchC = cat === 'all' || loc.category.toLowerCase() === cat.toLowerCase();
    return matchQ && matchC;
  });

  const countEl = document.getElementById('resultsCount');
  if (countEl) countEl.textContent = `${filtered.length} place${filtered.length !== 1 ? 's' : ''} found`;

  const container = document.getElementById('resultsContainer');
  if (!container) return;

  if (!filtered.length) {
    container.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
      <div class="empty-icon">🔍</div>
      <p>Nothing matched — try a different name or area.</p>
    </div>`;
    return;
  }

  container.innerHTML = filtered.map(item => {
    const isFav = favoriteIds.includes(item.id);
    return `
      <div class="card location-card">
        <div class="card-top">
          <div>
            <h3>${item.name}</h3>
            <div class="branch-label">${item.branch}</div>
          </div>
          ${badge(item.current)}
        </div>
        <div class="address">📍 ${item.address}</div>
        <div style="margin-bottom:14px;"><span class="chip">${item.category}</span></div>
        <div class="card-actions">
          <button class="btn btn-primary btn-sm" onclick="goToDetails(${item.id})">View details</button>
          ${currentUser
            ? `<button class="btn btn-ghost btn-sm" onclick="toggleFavorite(${item.id}, this)">${isFav ? '❤️ Saved' : '🤍 Save'}</button>`
            : ''}
        </div>
      </div>`;
  }).join('');
}

// ─── Details Page ─────────────────────────────────────

function initDetailsPage() {
  buildSidebar();
  const params = new URLSearchParams(window.location.search);
  const id = parseInt(params.get('id'));
  if (!id) { window.location.href = 'index.html'; return; }
  const item = LOCATIONS.find(l => l.id === id);
  if (!item) { window.location.href = 'index.html'; return; }
  renderDetails(item);
  initDatetimePicker(item);
}

function renderDetails(item) {
  const isFav = favoriteIds.includes(item.id);

  setEl('detailName',    item.name);
  setEl('detailBranch',  `${item.branch} · ${item.category}`);
  setEl('detailAddress', `📍 ${item.address}`);
  setHTML('statCurrent', badge(item.current));

  const favBtn = document.getElementById('favBtn');
  if (favBtn) {
    if (currentUser) {
      favBtn.style.display = 'inline-flex';
      favBtn.textContent = isFav ? '❤️ Saved' : '🤍 Save';
      favBtn.onclick = () => {
        toggleFavorite(item.id, favBtn);
      };
    } else {
      favBtn.style.display = 'none';
    }
  }

  const mapsBtn = document.getElementById('mapsBtn');
  if (mapsBtn) {
    mapsBtn.onclick = () => window.open(`https://www.google.com/maps?q=${item.lat},${item.lng}`, '_blank');
  }

  const altBox = document.getElementById('alternativesBox');
  if (altBox) {
    altBox.innerHTML = item.alternatives.map(alt => `
      <div class="item-row">
        <div><strong>${alt}</strong><div class="sub">Nearby — tends to be less busy</div></div>
        <span class="badge badge-low">Less busy</span>
      </div>`).join('');
  }

  const sameBranches = LOCATIONS.filter(l => l.name === item.name);
  const compareBox = document.getElementById('compareBox');
  if (compareBox) {
    compareBox.innerHTML = sameBranches.map(b => `
      <div class="item-row">
        <div><strong>${b.branch}</strong><div class="sub">${b.address}</div></div>
        <div class="item-actions">
          ${badge(b.current)}
          <button class="btn btn-primary btn-sm" onclick="goToDetails(${b.id})">Go</button>
        </div>
      </div>`).join('');
  }
}

function initDatetimePicker(item) {
  const dtInput  = document.getElementById('dtInput');
  const dtBtn    = document.getElementById('dtBtn');
  const dtResult = document.getElementById('dtResult');
  if (!dtInput || !dtBtn || !dtResult) return;

  const now = new Date();
  const pad = n => String(n).padStart(2, '0');
  dtInput.min = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;

  dtBtn.addEventListener('click', () => {
    if (!dtInput.value) {
      dtResult.className = 'datetime-result visible';
      dtResult.innerHTML = `<div class="datetime-answer">Pick a date and time first</div>`;
      return;
    }
    const chosen    = new Date(dtInput.value);
    const predicted = getPredictedCongestion(item, chosen);
    const bestTime  = getBestTimeLabel(item, chosen);
    const dayLabel  = isWeekend(chosen) ? 'weekend' : 'weekday';
    const timeLabel = getTimeSlot(chosen);

    dtResult.className = 'datetime-result visible';
    dtResult.innerHTML = `
      <div class="datetime-answer">${badge(predicted)}</div>
      <div class="datetime-note" style="margin-top:8px;">
        Expected congestion on that ${dayLabel} ${timeLabel}.
      </div>
      <div class="datetime-note" style="margin-top:6px;">
        💡 Best time to go that day: <strong>${bestTime}</strong>
      </div>`;
  });
}

// ─── Favorites Page ───────────────────────────────────

function initFavoritesPage() {
  buildSidebar();
  if (!currentUser) { window.location.href = 'index.html'; return; }
  renderFavorites();
}

function renderFavorites() {
  const box = document.getElementById('favoritesBox');
  if (!box) return;
  const items = LOCATIONS.filter(l => favoriteIds.includes(l.id));

  if (!items.length) {
    box.innerHTML = `<div class="empty-state">
      <div class="empty-icon">🔖</div>
      <p>Nothing saved yet. Search for a place and tap "Save".</p>
    </div>`;
    return;
  }

  box.innerHTML = items.map(item => `
    <div class="item-row">
      <div>
        <strong>${item.name} – ${item.branch}</strong>
        <div class="sub">📍 ${item.address}</div>
      </div>
      <div class="item-actions">
        ${badge(item.current)}
        <button class="btn btn-primary btn-sm" onclick="goToDetails(${item.id})">View</button>
        <button class="btn btn-ghost btn-sm" onclick="removeFavorite(${item.id})">Remove</button>
      </div>
    </div>`).join('');
}

function removeFavorite(id) {
  favoriteIds = favoriteIds.filter(f => f !== id);
  saveFavs();
  renderFavorites();
}

// ─── Profile Page ─────────────────────────────────────

function initProfilePage() {
  buildSidebar();
  if (!currentUser) { window.location.href = 'index.html'; return; }

  setEl('profileName',    currentUser.name);
  setEl('profileEmail',   currentUser.email);
  setEl('profileInitial', avatarLetter(currentUser.name));

  const toggle = document.getElementById('notifsToggle');
  const status = document.getElementById('notifsStatus');
  if (toggle) {
    toggle.classList.toggle('on', notificationsEnabled);
    toggle.addEventListener('click', () => {
      notificationsEnabled = !notificationsEnabled;
      toggle.classList.toggle('on', notificationsEnabled);
      if (status) status.textContent = notificationsEnabled
        ? "You'll get updates when your saved places get busy."
        : "Notifications are off.";
    });
    if (status) status.textContent = notificationsEnabled
      ? "You'll get updates when your saved places get busy."
      : "Notifications are off.";
  }

  const notifsList = document.getElementById('notifsList');
  if (notifsList) {
    notifsList.innerHTML = MOCK_NOTIFICATIONS.map(n => `
      <div class="notif-item">
        <div class="notif-dot ${n.read ? 'read' : ''}"></div>
        <div class="notif-text">
          <strong>${n.title}</strong>
          <span>${n.time}</span>
        </div>
      </div>`).join('');
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      currentUser = null;
      favoriteIds = [];
      sessionStorage.removeItem('t2g_user');
      sessionStorage.removeItem('t2g_favs');
      window.location.href = 'index.html';
    });
  }
}

// ─── Sign In Page ─────────────────────────────────────

function initSignInPage() {
  const loginTab     = document.getElementById('tabLogin');
  const registerTab  = document.getElementById('tabRegister');
  const loginForm    = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');

  function showTab(tab) {
    if (tab === 'login') {
      loginForm.style.display    = 'block';
      registerForm.style.display = 'none';
      loginTab.classList.add('active');
      registerTab.classList.remove('active');
    } else {
      loginForm.style.display    = 'none';
      registerForm.style.display = 'block';
      registerTab.classList.add('active');
      loginTab.classList.remove('active');
    }
  }

  loginTab.addEventListener('click',    () => showTab('login'));
  registerTab.addEventListener('click', () => showTab('register'));

  const params = new URLSearchParams(window.location.search);
  showTab(params.get('tab') === 'register' ? 'register' : 'login');

  document.getElementById('loginFormEl').addEventListener('submit', e => {
    e.preventDefault();
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    if (!email || !password) { showAlert('loginMsg', 'Please fill in both fields.', 'error'); return; }
    const name = email.split('@')[0];
    currentUser = { name: name.charAt(0).toUpperCase() + name.slice(1), email };
    sessionStorage.setItem('t2g_user', JSON.stringify(currentUser));
    window.location.href = 'index.html';
  });

  document.getElementById('registerFormEl').addEventListener('submit', e => {
    e.preventDefault();
    const name     = document.getElementById('regName').value.trim();
    const email    = document.getElementById('regEmail').value.trim();
    const password = document.getElementById('regPassword').value;
    const confirm  = document.getElementById('regConfirm').value;
    if (!name || !email || !password) { showAlert('registerMsg', 'Please fill in all fields.', 'error'); return; }
    if (password !== confirm) { showAlert('registerMsg', "Passwords don't match.", 'error'); return; }
    currentUser = { name, email };
    sessionStorage.setItem('t2g_user', JSON.stringify(currentUser));
    window.location.href = 'index.html';
  });
}

// ─── Shared Helpers ───────────────────────────────────

function goToDetails(id) {
  window.location.href = `details.html?id=${id}`;
}

function toggleFavorite(id, btn) {
  if (favoriteIds.includes(id)) {
    favoriteIds = favoriteIds.filter(f => f !== id);
  } else {
    favoriteIds.push(id);
  }
  saveFavs();
  if (btn) btn.textContent = favoriteIds.includes(id) ? '❤️ Saved' : '🤍 Save';
}

// ─── Boot ─────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const page = window.location.pathname.split('/').pop() || 'index.html';
  if (page === 'index.html'     || page === '') initHomePage();
  if (page === 'details.html')                  initDetailsPage();
  if (page === 'favorites.html')                initFavoritesPage();
  if (page === 'profile.html')                  initProfilePage();
  if (page === 'signin.html')                   initSignInPage();
});