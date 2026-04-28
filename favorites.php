<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php?msg=auth_required");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'];

// Get all favorited branches for this user
$stmt = $conn->prepare("
    SELECT 
        f.favorite_id,
        b.branch_id,
        b.branch_name,
        b.address,
        l.name AS location_name,
        l.category,
        (SELECT current_level FROM CongestionRecord 
         WHERE branch_id = b.branch_id 
         ORDER BY recorded_at DESC LIMIT 1) AS current_level
    FROM Favorite f
    JOIN Branch b ON f.branch_id = b.branch_id
    JOIN Location l ON b.location_id = l.location_id
    WHERE f.user_id = ?
    ORDER BY l.name, b.branch_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}
$stmt->close();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Favorites — Time2Go</title>
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
        <a class="nav-link active" href="favorites.php"><span class="nav-icon">⭐</span> Favorites</a>
        <a class="nav-link" href="profile.php"><span class="nav-icon">👤</span> Profile</a>
      </nav>

      <div class="sidebar-footer">
        Signed in as <strong><?= htmlspecialchars($userName) ?></strong><br>
        Riyadh 🇸🇦
      </div>
    </aside>

    <main class="main-content">

     <div class="page-header">
  <h1>Your favorites</h1>
  <p>Places you've saved. Tap a place to check how busy it is right now.</p>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'fav_failed'): ?>
  <div class="alert alert-error">Couldn't update your favorites. Please try again.</div>
<?php endif; ?>

      <div class="card">
        <?php if (count($favorites) === 0): ?>
          <div class="empty-state">
            <div class="empty-icon">⭐</div>
            <p>You haven't saved any places yet.</p>
            <p style="margin-top: 8px;">
              <a href="index.php" class="btn btn-primary btn-sm" style="margin-top: 14px;">Browse places</a>
            </p>
          </div>
        <?php else: ?>
          <?php foreach ($favorites as $fav): ?>
            <div class="item-row">
              <div>
                <strong><?= htmlspecialchars($fav['location_name']) ?></strong>
                <div class="sub">
                  <?= htmlspecialchars($fav['branch_name']) ?> — <?= htmlspecialchars($fav['address']) ?>
                </div>
                <div style="margin-top: 6px;">
                  <span class="chip"><?= htmlspecialchars($fav['category']) ?></span>
                </div>
              </div>
              <div class="item-actions">
                <span class="badge <?= badgeClass($fav['current_level']) ?>">
                  <?= badgeText($fav['current_level']) ?>
                </span>
                <a href="details.php?branch_id=<?= $fav['branch_id'] ?>" class="btn btn-primary btn-sm">View</a>
                <form method="POST" action="toggle_favorite.php" style="display:inline;">
                  <input type="hidden" name="branch_id" value="<?= $fav['branch_id'] ?>" />
                  <input type="hidden" name="redirect" value="favorites.php" />
                  <button type="submit" class="btn btn-ghost btn-sm" title="Remove from favorites">✕</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </main>
  </div>

</body>
</html>