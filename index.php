<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

$q   = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? 'all';

// Get LOCATIONS (not branches)
$sql = "SELECT location_id, name, category FROM Location WHERE 1=1 ";
$params = [];
$types  = "";

if ($q !== '') {
    $sql .= " AND name LIKE ? ";
    $params[] = "%$q%";
    $types .= "s";
}
if ($cat !== 'all') {
    $sql .= " AND category = ? ";
    $params[] = $cat;
    $types .= "s";
}
$sql .= " ORDER BY name";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}
$stmt->close();

// For each location, grab its branches
foreach ($locations as &$loc) {
    $stmt = $conn->prepare("SELECT branch_id, branch_name, address FROM Branch WHERE location_id = ? ORDER BY branch_name");
    $stmt->bind_param("i", $loc['location_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $loc['branches'] = [];
    while ($b = $res->fetch_assoc()) {
        $loc['branches'][] = $b;
    }
    $stmt->close();
}
unset($loc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Time2Go — Know before you go</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

  <div class="app-layout">

    <aside class="sidebar">
      <a class="logo-wrap" href="index.php">
        <img src="logoF.png" alt="Time2Go" />
        <span class="logo-text">Time2Go</span>
      </a>
      <div class="logo-tagline">Know before you go</div>

      <nav>
        <a class="nav-link active" href="index.php"><span class="nav-icon">🔍</span> Search</a>
        <a class="nav-link" href="favorites.php"><span class="nav-icon">⭐</span> Favorites</a>
        <a class="nav-link" href="profile.php"><span class="nav-icon">👤</span> Profile</a>
      </nav>

      <div class="sidebar-footer">
        Signed in as <strong><?= htmlspecialchars($userName) ?></strong><br>
        Riyadh 🇸🇦
      </div>
    </aside>

    <main class="main-content">

      <div class="page-header">
        <h1>Find a place</h1>
        <p>Search malls, cafes, and public spots in Riyadh — pick a branch to see how busy it is.</p>
      </div>

      <!-- Search box -->
      <form method="GET" action="index.php">
        <div class="card">
          <div class="search-bar">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input
                class="form-input"
                type="text"
                name="q"
                value="<?= htmlspecialchars($q) ?>"
                placeholder="Try 'River Mall' or 'Dose'…"
                autocomplete="off"
              />
            </div>
            <button class="btn btn-primary" type="submit">Search</button>
          </div>

          <!-- Category filters -->
          <div style="margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="?q=<?= urlencode($q) ?>&cat=all" class="btn btn-ghost btn-sm <?= $cat==='all' ? 'filter-chip active' : 'filter-chip' ?>">All</a>
            <a href="?q=<?= urlencode($q) ?>&cat=Mall" class="btn btn-ghost btn-sm <?= $cat==='Mall' ? 'filter-chip active' : 'filter-chip' ?>">Malls</a>
            <a href="?q=<?= urlencode($q) ?>&cat=Cafe" class="btn btn-ghost btn-sm <?= $cat==='Cafe' ? 'filter-chip active' : 'filter-chip' ?>">Cafes</a>
            <a href="?q=<?= urlencode($q) ?>&cat=Supermarket" class="btn btn-ghost btn-sm <?= $cat==='Supermarket' ? 'filter-chip active' : 'filter-chip' ?>">Supermarkets</a>
          </div>
        </div>
      </form>

      <!-- Results -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <span class="section-title">Results</span>
        <span style="font-size: 13px; color: var(--gray-400);">
          <?= count($locations) ?> <?= count($locations) === 1 ? 'place' : 'places' ?>
        </span>
      </div>

      <?php if (count($locations) === 0): ?>
        <div class="card empty-state">
          <div class="empty-icon">🔍</div>
          <p>No places found. Try a different search.</p>
        </div>
      <?php else: ?>
        <div class="results-grid">
          <?php foreach ($locations as $loc): ?>
            <div class="card location-card">
              <div class="card-top">
                <div>
                  <h3><?= htmlspecialchars($loc['name']) ?></h3>
                  <span class="chip"><?= htmlspecialchars($loc['category']) ?></span>
                </div>
              </div>

              <form method="GET" action="details.php" style="margin-top: 14px;">
                <div class="form-group" style="margin-bottom: 12px;">
                  <label>Choose a branch</label>
                  <select name="branch_id" class="form-input" required>
                    <option value="">— Select a branch —</option>
                    <?php foreach ($loc['branches'] as $b): ?>
                      <option value="<?= $b['branch_id'] ?>">
                        <?= htmlspecialchars($b['branch_name']) ?> — <?= htmlspecialchars($b['address']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-full">View details</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </main>
  </div>

</body>
</html>