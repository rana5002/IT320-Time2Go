
// ─── Data ────────────────────────────────────

const LOCATIONS = [
  {
    locationId: 1,
    name: 'River Mall',
    category: 'Mall',
    branches: [
      {
        branchId: 1,
        name: 'North Branch',
        address: 'Al Yasmin District, Riyadh',
        coordinates: '24.8103,46.6753',
        current: 'Medium',
        schedule: {
          weekday_morning:   'Low',
          weekday_afternoon: 'High',
          weekday_evening:   'Medium',
          weekend_morning:   'Medium',
          weekend_afternoon: 'High',
          weekend_evening:   'High',
        },
        alternatives: ['Palm Mall', 'Sky Plaza'],
      },
      {
        branchId: 2,
        name: 'East Branch',
        address: 'Al Rawdah District, Riyadh',
        coordinates: '24.7342,46.7891',
        current: 'Low',
        schedule: {
          weekday_morning:   'Low',
          weekday_afternoon: 'Medium',
          weekday_evening:   'Medium',
          weekend_morning:   'Low',
          weekend_afternoon: 'High',
          weekend_evening:   'High',
        },
        alternatives: ['Palm Mall', 'City Walk'],
      },
    ],
  },
  {
    locationId: 2,
    name: 'Bean House',
    category: 'Cafe',
    branches: [
      {
        branchId: 1,
        name: 'King Fahd Road',
        address: 'King Fahd Road, Riyadh',
        coordinates: '24.7136,46.6753',
        current: 'High',
        schedule: {
          weekday_morning:   'Medium',
          weekday_afternoon: 'High',
          weekday_evening:   'High',
          weekend_morning:   'Low',
          weekend_afternoon: 'High',
          weekend_evening:   'High',
        },
        alternatives: ['Roast Lab', 'Daily Cup'],
      },
      {
        branchId: 2,
        name: 'Olaya Branch',
        address: 'Al Olaya, Riyadh',
        coordinates: '24.7115,46.6749',
        current: 'Low',
        schedule: {
          weekday_morning:   'Low',
          weekday_afternoon: 'Low',
          weekday_evening:   'Medium',
          weekend_morning:   'Low',
          weekend_afternoon: 'Medium',
          weekend_evening:   'High',
        },
        alternatives: ['Roast Lab', 'Moon Cafe'],
      },
    ],
  },
  {
    locationId: 3,
    name: 'Grocery Hub',
    category: 'Supermarket',
    branches: [
      {
        branchId: 1,
        name: 'Malaz Branch',
        address: 'Al Malaz, Riyadh',
        coordinates: '24.6877,46.7219',
        current: 'Low',
        schedule: {
          weekday_morning:   'Low',
          weekday_afternoon: 'Medium',
          weekday_evening:   'Medium',
          weekend_morning:   'Medium',
          weekend_afternoon: 'High',
          weekend_evening:   'Medium',
        },
        alternatives: ['Fresh Market', 'QuickShop'],
      },
    ],
  },
];

const MOCK_NOTIFICATIONS = [
  { id: 1, read: false, title: 'River Mall is getting busy',             time: '5 minutes ago' },
  { id: 2, read: false, title: 'Best time to visit Bean House: now!',   time: '22 minutes ago' },
  { id: 3, read: true,  title: 'Your favorite spot is quieter today',   time: '2 hours ago' },
  { id: 4, read: true,  title: 'Grocery Hub congestion dropped to Low', time: 'Yesterday' },
];


// ─── Session ─────────────────────────────────
// Stays alive while the tab is open, clears when tab closes

let currentUser = sessionStorage.getItem('t2g_user')
                  ? JSON.parse(sessionStorage.getItem('t2g_user'))
                  : null;

// favoritesList stores full branch objects as per the class diagram
let favoritesList = sessionStorage.getItem('t2g_favs')
                    ? JSON.parse(sessionStorage.getItem('t2g_favs'))
                    : [];

let notificationsEnabled = true;

function saveFavs() {
  sessionStorage.setItem('t2g_favs', JSON.stringify(favoritesList));
}


// ─── Helpers ─────────────────────────────────

function badgeClass(level) {
  if (level.toLowerCase() === 'low')    return 'badge-low';
  if (level.toLowerCase() === 'medium') return 'badge-medium';
  if (level.toLowerCase() === 'high')   return 'badge-high';
  return 'badge-low';
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

// Friday and Saturday are the weekend in Saudi Arabia
function isWeekend(date) {
  return date.getDay() === 5 || date.getDay() === 6;
}

function getTimeSlot(date) {
  const hour = date.getHours();
  if (hour >= 6  && hour < 12) return 'morning';
  if (hour >= 12 && hour < 18) return 'afternoon';
  return 'evening';
}

function getPredictedCongestion(branch, date) {
  const dayType = isWeekend(date) ? 'weekend' : 'weekday';
  const slot    = getTimeSlot(date);
  return branch.schedule[`${dayType}_${slot}`] || 'Medium';
}

function getBestTimeLabel(branch, date) {
  const dayType = isWeekend(date) ? 'weekend' : 'weekday';
  const slots = [
    { key: `${dayType}_morning`,   label: '6:00 AM – 12:00 PM' },
    { key: `${dayType}_afternoon`, label: '12:00 PM – 6:00 PM' },
    { key: `${dayType}_evening`,   label: '6:00 PM – 11:00 PM' },
  ];
  const low = slots.find(s => branch.schedule[s.key] === 'Low');
  if (low) return low.label;
  const med = slots.find(s => branch.schedule[s.key] === 'Medium');
  if (med) return med.label;
  return 'No low-congestion window today';
}

function avatarLetter(name) {
  return name ? name.charAt(0).toUpperCase() : 'G';
}

function goToDetails(locationId, branchId) {
  window.location.href = `details.html?locationId=${locationId}&branchId=${branchId}`;
}

// Add or remove a branch from favoritesList
function toggleFavorite(locationId, branchId, btn) {
  const already = favoritesList.find(
    f => f.locationId === locationId && f.branchId === branchId
  );

  if (already) {
    favoritesList = favoritesList.filter(
      f => !(f.locationId === locationId && f.branchId === branchId)
    );
  } else {
    // Find the branch and save it with its parent location info
    const location = LOCATIONS.find(l => l.locationId === locationId);
    const branch   = location.branches.find(b => b.branchId === branchId);
    favoritesList.push({
      locationId:   location.locationId,
      locationName: location.name,
      locationCategory: location.category,
      branchId:     branch.branchId,
      branchName:   branch.name,
      address:      branch.address,
      coordinates:  branch.coordinates,
    });
  }

  saveFavs();
  if (btn) {
    const isSaved = favoritesList.find(
      f => f.locationId === locationId && f.branchId === branchId
    );
    btn.textContent = isSaved ? '❤️ Saved' : '🤍 Save';
  }
}


// ─── Sidebar ─────────────────────────────────

function buildSidebar() {
  const nav = document.querySelector('nav#mainNav');
  if (!nav) return;

  const currentFile = window.location.pathname.split('/').pop() || 'index.html';

  function isActive(page) {
    return currentFile === page || (currentFile === '' && page === 'index.html') ? 'active' : '';
  }

  if (currentUser) {
    nav.innerHTML = `
      <a class="nav-link ${isActive('index.html')}" href="index.html">
        <span class="nav-icon">🏠</span> Home
      </a>
      <a class="sidebar-profile ${isActive('profile.html')}" href="profile.html">
        <div class="profile-avatar">${avatarLetter(currentUser.name)}</div>
        <div class="profile-info">
          <span class="profile-name">${currentUser.name}</span>
          <span class="profile-role">View profile</span>
        </div>
      </a>
      <a class="nav-link ${isActive('favorites.html')}" href="favorites.html">
        <span class="nav-icon">❤️</span> Favorites
      </a>`;
  } else {
    nav.innerHTML = `
      <a class="nav-link ${isActive('index.html')}" href="index.html">
        <span class="nav-icon">🏠</span> Home
      </a>
      <a class="nav-link ${isActive('signin.html')}" href="signin.html">
        <span class="nav-icon">🔑</span> Sign in
      </a>`;
  }
}


// ─── Home Page ───────────────────────────────

function initHomePage() {
  buildSidebar();

  const input = document.getElementById('searchInput');
  if (!input) return;

  renderResults('');

  input.addEventListener('input', () => renderResults(input.value));
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') renderResults(input.value);
  });

  document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      renderResults(document.getElementById('searchInput').value, chip.dataset.cat);
    });
  });
}

// One card per location — branch selected via dropdown inside the card
function renderResults(query = '', cat = 'all') {
  const q = query.toLowerCase().trim();

  const filtered = LOCATIONS.filter(loc => {
    const matchQ = !q || loc.name.toLowerCase().includes(q) || loc.category.toLowerCase().includes(q);
    const matchC = cat === 'all' || loc.category.toLowerCase() === cat.toLowerCase();
    return matchQ && matchC;
  });

  const countEl = document.getElementById('resultsCount');
  if (countEl) {
    countEl.textContent = `${filtered.length} place${filtered.length !== 1 ? 's' : ''} found`;
  }

  const container = document.getElementById('resultsContainer');
  if (!container) return;

  if (!filtered.length) {
    container.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">🔍</div>
        <p>Nothing matched — try a different name or area.</p>
      </div>`;
    return;
  }

  container.innerHTML = filtered.map(loc => {
    // Build the branch dropdown options
    const options = loc.branches.map(b =>
      `<option value="${b.branchId}">${b.name}</option>`
    ).join('');

    return `
      <div class="card location-card">
        <div class="card-top">
          <div>
            <h3>${loc.name}</h3>
            <div class="branch-label">${loc.category}</div>
          </div>
        </div>

        <div style="margin-bottom: 14px;">
          <span class="chip">${loc.category}</span>
        </div>

        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-size:13px; color:var(--gray-400); margin-bottom:6px; display:block;">Select branch</label>
          <select class="form-input" id="branch-select-${loc.locationId}">
            ${options}
          </select>
        </div>

        <div class="card-actions">
          <button
            class="btn btn-primary btn-sm"
            onclick="goToDetails(${loc.locationId}, parseInt(document.getElementById('branch-select-${loc.locationId}').value))">
            View details
          </button>
        </div>
      </div>`;
  }).join('');
}


// ─── Details Page ────────────────────────────

function initDetailsPage() {
  buildSidebar();

  // Read locationId and branchId from the URL
  const params     = new URLSearchParams(window.location.search);
  const locationId = parseInt(params.get('locationId'));
  const branchId   = parseInt(params.get('branchId'));

  if (!locationId || !branchId) { window.location.href = 'index.html'; return; }

  const location = LOCATIONS.find(l => l.locationId === locationId);
  if (!location) { window.location.href = 'index.html'; return; }

  const branch = location.branches.find(b => b.branchId === branchId);
  if (!branch) { window.location.href = 'index.html'; return; }

  renderDetails(location, branch);
  initDatetimePicker(branch);
}

function renderDetails(location, branch) {
  const isSaved = favoritesList.find(
    f => f.locationId === location.locationId && f.branchId === branch.branchId
  );

  setEl('detailName',    location.name);
  setEl('detailBranch',  `${branch.name} · ${location.category}`);
  setEl('detailAddress', `📍 ${branch.address}`);
  setHTML('statCurrent', badge(branch.current));

  // Save button — only for logged-in users
  const favBtn = document.getElementById('favBtn');
  if (favBtn) {
    if (currentUser) {
      favBtn.style.display = 'inline-flex';
      favBtn.textContent   = isSaved ? '❤️ Saved' : '🤍 Save';
      favBtn.onclick = () => toggleFavorite(location.locationId, branch.branchId, favBtn);
    } else {
      favBtn.style.display = 'none';
    }
  }

  // Opens Google Maps using the branch coordinates
  document.getElementById('mapsBtn').onclick = () => {
    window.open(`https://www.google.com/maps?q=${branch.coordinates}`, '_blank');
  };

  // Alternative suggestions
  const altBox = document.getElementById('alternativesBox');
  if (altBox) {
    altBox.innerHTML = branch.alternatives.map(alt => `
      <div class="item-row">
        <div><strong>${alt}</strong><div class="sub">Nearby — tends to be less busy</div></div>
        <span class="badge badge-low">Less busy</span>
      </div>`).join('');
  }

  // Compare all branches of the same location
  const compareBox = document.getElementById('compareBox');
  if (compareBox) {
    compareBox.innerHTML = location.branches.map(b => `
      <div class="item-row">
        <div><strong>${b.name}</strong><div class="sub">${b.address}</div></div>
        <div class="item-actions">
          <button class="btn btn-primary btn-sm" onclick="goToDetails(${location.locationId}, ${b.branchId})">Go</button>
        </div>
      </div>`).join('');
  }
}

function initDatetimePicker(branch) {
  const dtInput  = document.getElementById('dtInput');
  const dtBtn    = document.getElementById('dtBtn');
  const dtResult = document.getElementById('dtResult');
  if (!dtInput || !dtBtn || !dtResult) return;

  // Prevent picking a time in the past
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
    const predicted = getPredictedCongestion(branch, chosen);
    const bestTime  = getBestTimeLabel(branch, chosen);
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


// ─── Favorites Page ──────────────────────────

function initFavoritesPage() {
  buildSidebar();
  if (!currentUser) { window.location.href = 'index.html'; return; }
  renderFavorites();
}

function renderFavorites() {
  const box = document.getElementById('favoritesBox');
  if (!box) return;

  if (!favoritesList.length) {
    box.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">🔖</div>
        <p>Nothing saved yet. Search for a place and tap "Save".</p>
      </div>`;
    return;
  }

  box.innerHTML = favoritesList.map(fav => `
    <div class="item-row">
      <div>
        <strong>${fav.locationName} – ${fav.branchName}</strong>
        <div class="sub">📍 ${fav.address}</div>
      </div>
      <div class="item-actions">
        <button class="btn btn-primary btn-sm" onclick="goToDetails(${fav.locationId}, ${fav.branchId})">View</button>
        <button class="btn btn-ghost btn-sm" onclick="removeFavorite(${fav.locationId}, ${fav.branchId})">Remove</button>
      </div>
    </div>`).join('');
}

function removeFavorite(locationId, branchId) {
  favoritesList = favoritesList.filter(
    f => !(f.locationId === locationId && f.branchId === branchId)
  );
  saveFavs();
  renderFavorites();
}


// ─── Profile Page ────────────────────────────

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
      status.textContent = notificationsEnabled
        ? "You'll get updates when your saved places get busy."
        : 'Notifications are off.';
    });
    status.textContent = notificationsEnabled
      ? "You'll get updates when your saved places get busy."
      : 'Notifications are off.';
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

  document.getElementById('logoutBtn').addEventListener('click', () => {
    currentUser = null;
    favoritesList = [];
    sessionStorage.removeItem('t2g_user');
    sessionStorage.removeItem('t2g_favs');
    window.location.href = 'index.html';
  });
}


// ─── Sign In Page ────────────────────────────

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
    const rawName = email.split('@')[0];
    currentUser = { name: rawName.charAt(0).toUpperCase() + rawName.slice(1), email };
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


// ─── Boot ────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const page = window.location.pathname.split('/').pop() || 'index.html';
  if (page === 'index.html'     || page === '') initHomePage();
  if (page === 'details.html')                  initDetailsPage();
  if (page === 'favorites.html')                initFavoritesPage();
  if (page === 'profile.html')                  initProfilePage();
  if (page === 'signin.html')                   initSignInPage();
});