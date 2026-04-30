<?php
session_start();
require_once 'db.php';

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['name'] ?? '';
$branchId = intval($_GET['branch_id'] ?? 0);

if ($branchId <= 0) {
    header("Location: index.php?msg=invalid_branch");
    exit;
}

// Get branch + location info
$stmt = $conn->prepare("
    SELECT b.branch_id, b.branch_name, b.address, b.location_id,
           l.name AS location_name, l.category
    FROM Branch b
    JOIN Location l ON b.location_id = l.location_id
    WHERE b.branch_id = ?
");
$stmt->bind_param("i", $branchId);
$stmt->execute();
$branch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$branch) {
    header("Location: index.php?msg=branch_not_found");
    exit;
}

// Get latest congestion record
$stmt = $conn->prepare("
    SELECT current_level, predicted_level, suggestion_time, recorded_at
    FROM CongestionRecord
    WHERE branch_id = ?
    ORDER BY recorded_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $branchId);
$stmt->execute();
$congestion = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if this branch is favorited (only if logged in)
$isFav = null;
if ($userId) {
    $stmt = $conn->prepare("SELECT favorite_id FROM Favorite WHERE user_id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $userId, $branchId);
    $stmt->execute();
    $isFav = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get other branches of the same location (for comparison)
$stmt = $conn->prepare("
    SELECT b.branch_id, b.branch_name, b.address,
           (SELECT current_level FROM CongestionRecord 
            WHERE branch_id = b.branch_id 
            ORDER BY recorded_at DESC LIMIT 1) AS current_level
    FROM Branch b
    WHERE b.location_id = ? AND b.branch_id != ?
    ORDER BY b.branch_name
");
$stmt->bind_param("ii", $branch['location_id'], $branchId);
$stmt->execute();
$otherBranches = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $otherBranches[] = $row;
}
$stmt->close();

// Get alternative spots (same category, different location, low congestion preferred)
$stmt = $conn->prepare("
    SELECT b.branch_id, b.branch_name, b.address,
           l.name AS location_name,
           (SELECT current_level FROM CongestionRecord 
            WHERE branch_id = b.branch_id 
            ORDER BY recorded_at DESC LIMIT 1) AS current_level
    FROM Branch b
    JOIN Location l ON b.location_id = l.location_id
    WHERE l.category = ? AND l.location_id != ?
    ORDER BY FIELD((SELECT current_level FROM CongestionRecord 
                    WHERE branch_id = b.branch_id 
                    ORDER BY recorded_at DESC LIMIT 1), 'low', 'medium', 'high')
    LIMIT 3
");
$stmt->bind_param("si", $branch['category'], $branch['location_id']);
$stmt->execute();
$alternatives = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alternatives[] = $row;
}
$stmt->close();

// Handle datetime check
$dtResult = null;
$dtError  = null;

if (isset($_GET['dt']) && $_GET['dt'] !== '') {
    $dt = $_GET['dt'];
    $timestamp = strtotime($dt);

    if ($timestamp === false) {
        $dtError = 'Invalid date format. Please pick a valid date and time.';
    } elseif ($timestamp < strtotime('today')) {
        $dtError = "You can't check a date in the past. Pick today or later.";
    } elseif ($timestamp > strtotime('+30 days')) {
        $dtError = "Predictions are only available for the next 30 days.";
    } else {
        $hour    = (int)date('G', $timestamp);
        $dayOfWk = (int)date('w', $timestamp);

        if ($dayOfWk === 5 || $dayOfWk === 6) {
            if ($hour >= 18 && $hour <= 23)      $predicted = 'high';
            elseif ($hour >= 14 && $hour <= 17)  $predicted = 'medium';
            else                                  $predicted = 'low';
        } else {
            if ($hour >= 19 && $hour <= 22)      $predicted = 'high';
            elseif ($hour >= 16 && $hour <= 18)  $predicted = 'medium';
            else                                  $predicted = 'low';
        }

        $dtResult = [
            'level' => $predicted,
            'time'  => date('l, M j · g:i A', $timestamp)
        ];
    }
}

// Helpers
function badgeClass($level) {
    if ($level === 'low')    return 'badge-low';
    if ($level === 'medium') return 'badge-medium';
    if ($level === 'high')   return 'badge-high';
    return 'badge-low';
}
function badgeText($level) {
    if ($level === 'low')    return 'Not busy';
    if ($level === 'medium') return 'Moderate';
    if ($level === 'high')   return 'Busy';
    return 'No data';
}

// Format suggestion_time nicely
$bestTime = null;
if (!empty($congestion['suggestion_time'])) {
    $bestTime = date('l · g:i A', strtotime($congestion['suggestion_time']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($branch['location_name']) ?> — Time2Go</title>
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
        <a class="nav-link" href="index.php"><span class="nav-icon">🔍</span> Search</a>
        <?php if ($userId): ?>
          <a class="nav-link" href="favorites.php"><span class="nav-icon">⭐</span> Favorites</a>
          <a class="nav-link" href="profile.php"><span class="nav-icon">👤</span> Profile</a>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <?php if ($userId): ?>
          Signed in as <strong><?= htmlspecialchars($userName) ?></strong><br>
          Riyadh 🇸🇦
        <?php else: ?>
          <a href="signin.php" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center; margin-bottom: 10px;">Log in</a>
          Riyadh 🇸🇦
        <?php endif; ?>
      </div>
    </aside>

    <main class="main-content">

<a href="index.php" class="btn btn-ghost btn-sm" style="margin-bottom: 20px;">← Back to search</a>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'fav_failed'): ?>
  <div class="alert alert-error">Couldn't update your favorites. Please try again.</div>
<?php endif; ?>

      <!-- Header -->
      <div class="card" style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
          <div>
            <h1 class="section-title" style="font-size: 24px;"><?= htmlspecialchars($branch['location_name']) ?></h1>
            <p style="font-size: 14px; color: var(--blue-main); font-weight: 500; margin-bottom: 4px;">
              <?= htmlspecialchars($branch['branch_name']) ?>
            </p>
            <p style="font-size: 13.5px; color: var(--gray-400);"><?= htmlspecialchars($branch['address']) ?></p>
          </div>
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($userId): ?>
              <form method="POST" action="toggle_favorite.php" style="display:inline;">
                <input type="hidden" name="branch_id" value="<?= $branchId ?>" />
                <input type="hidden" name="redirect" value="details.php?branch_id=<?= $branchId ?>" />
                <button type="submit" class="btn btn-outline">
                  <?= $isFav ? '💙 Saved' : '🤍 Save' ?>
                </button>
              </form>
            <?php endif; ?>
            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($branch['location_name'] . ' ' . $branch['branch_name'] . ' Riyadh') ?>" target="_blank" class="btn btn-primary">🗺 Open in Maps</a>
          </div>
        </div>
      </div>

      <!-- Congestion info -->
      <div class="stats-grid" style="margin-top: 32px;">

        <div class="stat-card">
          <div class="stat-label">Right now</div>
          <div class="stat-value">
            <span class="badge <?= badgeClass($congestion['current_level'] ?? null) ?>">
              <?= badgeText($congestion['current_level'] ?? null) ?>
            </span>
          </div>
          <div class="stat-note">Current congestion</div>
        </div>

        <!-- Best time to visit -->
        <div class="stat-card">
          <div class="stat-label">Best time to visit</div>
          <?php if ($bestTime): ?>
            <div class="stat-value" style="font-size: 18px;">✨ <?= htmlspecialchars($bestTime) ?></div>
            <div class="stat-note">Suggested visit time</div>
          <?php else: ?>
            <div class="stat-value" style="font-size: 18px;">😊 Anytime</div>
            <div class="stat-note">This place isn't usually busy</div>
          <?php endif; ?>
        </div>

        <!-- Date/time picker -->
        <div class="stat-card" style="grid-column: span 2;">
          <div class="stat-label">Check a specific time</div>
          <p style="font-size: 13.5px; color: var(--gray-400); margin-bottom: 14px;">
            Pick a date and time to see how busy it's likely to be.
          </p>
          <form method="GET" action="details.php">
            <input type="hidden" name="branch_id" value="<?= $branchId ?>" />
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <input class="form-input" type="datetime-local" name="dt" value="<?= htmlspecialchars($_GET['dt'] ?? '') ?>" style="max-width: 280px;" required />
              <button class="btn btn-primary" type="submit">Check</button>
            </div>
          </form>

          <?php if ($dtError): ?>
            <div class="alert alert-error" style="margin-top: 14px;"><?= htmlspecialchars($dtError) ?></div>
          <?php elseif ($dtResult): ?>
            <div class="datetime-result visible">
              <div class="datetime-answer">
                <span class="badge <?= badgeClass($dtResult['level']) ?>">
                  <?= badgeText($dtResult['level']) ?>
                </span>
              </div>
              <div class="datetime-note">Predicted for <?= htmlspecialchars($dtResult['time']) ?></div>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <div class="two-col">

        <!-- Alternative spots -->
        <div class="card">
          <div class="section-title">Alternative spots</div>
          <div class="section-sub">Similar places nearby that tend to be less busy</div>

          <?php if (count($alternatives) === 0): ?>
            <p style="color: var(--gray-400); font-size: 13.5px;">No alternatives available.</p>
          <?php else: ?>
            <?php foreach ($alternatives as $alt): ?>
              <div class="item-row">
                <div>
                  <strong><?= htmlspecialchars($alt['location_name']) ?></strong>
                  <div class="sub"><?= htmlspecialchars($alt['branch_name']) ?> — <?= htmlspecialchars($alt['address']) ?></div>
                </div>
                <div class="item-actions">
                  <span class="badge <?= badgeClass($alt['current_level']) ?>">
                    <?= badgeText($alt['current_level']) ?>
                  </span>
                  <a href="details.php?branch_id=<?= $alt['branch_id'] ?>" class="btn btn-ghost btn-sm">View</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Compare branches -->
        <div class="card">
          <div class="section-title">Compare branches</div>
          <div class="section-sub">Other branches of <?= htmlspecialchars($branch['location_name']) ?></div>

          <?php if (count($otherBranches) === 0): ?>
            <p style="color: var(--gray-400); font-size: 13.5px;">This is the only branch.</p>
          <?php else: ?>
            <?php foreach ($otherBranches as $other): ?>
              <div class="item-row">
                <div>
                  <strong><?= htmlspecialchars($other['branch_name']) ?></strong>
                  <div class="sub"><?= htmlspecialchars($other['address']) ?></div>
                </div>
                <div class="item-actions">
                  <span class="badge <?= badgeClass($other['current_level']) ?>">
                    <?= badgeText($other['current_level']) ?>
                  </span>
                  <a href="details.php?branch_id=<?= $other['branch_id'] ?>" class="btn btn-ghost btn-sm">View</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>

    </main>
  </div>

</body>
</html>