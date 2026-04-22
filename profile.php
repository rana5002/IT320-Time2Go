<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Handle notifications toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_notifications') {
    $stmt = $conn->prepare("UPDATE User SET notifications_enabled = NOT notifications_enabled WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php");
    exit;
}

// Handle mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $stmt = $conn->prepare("UPDATE Notification SET status = 'read' WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php");
    exit;
}

// Get user info
$stmt = $conn->prepare("SELECT name, email, notifications_enabled FROM User WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent notifications (last 10)
$stmt = $conn->prepare("
    SELECT n.notification_id, n.message, n.status, n.timestamp,
           b.branch_name, l.name AS location_name
    FROM Notification n
    JOIN Branch b ON n.branch_id = b.branch_id
    JOIN Location l ON b.location_id = l.location_id
    WHERE n.user_id = ?
    ORDER BY n.timestamp DESC
    LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = [];
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Get initial for avatar
$initial = strtoupper(substr($user['name'], 0, 1));

// Format time nicely
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60)       return 'just now';
    if ($diff < 3600)     return floor($diff/60) . 'm ago';
    if ($diff < 86400)    return floor($diff/3600) . 'h ago';
    if ($diff < 604800)   return floor($diff/86400) . 'd ago';
    return date('M j', strtotime($timestamp));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile — Time2Go</title>
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
        <a class="nav-link" href="favorites.php"><span class="nav-icon">⭐</span> Favorites</a>
        <a class="nav-link active" href="profile.php"><span class="nav-icon">👤</span> Profile</a>
      </nav>

      <div class="sidebar-footer">
        Signed in as <strong><?= htmlspecialchars($user['name']) ?></strong><br>
        Riyadh 🇸🇦
      </div>
    </aside>

    <main class="main-content">

      <!-- Hero -->
      <div class="profile-hero">
        <div class="profile-hero-avatar"><?= htmlspecialchars($initial) ?></div>
        <div>
          <div class="profile-hero-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="profile-hero-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px,1fr)); gap:20px;">

        <!-- Notifications -->
        <div class="card">
          <div class="section-title">Notifications</div>
          <div class="section-sub">Get alerts when your saved places get crowded.</div>

          <form method="POST" action="profile.php" style="margin:0;">
            <input type="hidden" name="action" value="toggle_notifications" />
            <div class="toggle-row">
              <div class="toggle-info">
                <strong>Congestion alerts</strong>
                <span><?= $user['notifications_enabled'] ? 'On' : 'Off' ?></span>
              </div>
              <button type="submit" class="toggle-switch <?= $user['notifications_enabled'] ? 'on' : '' ?>" 
                      style="border:none; cursor:pointer;" aria-label="Toggle notifications"></button>
            </div>
          </form>

          <div style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
              <div class="section-title" style="font-size:16px; margin-bottom:0;">Recent</div>
              <?php if (count($notifications) > 0): ?>
                <form method="POST" action="profile.php" style="margin:0;">
                  <input type="hidden" name="action" value="mark_read" />
                  <button type="submit" class="btn btn-ghost btn-sm">Mark all read</button>
                </form>
              <?php endif; ?>
            </div>

            <div>
              <?php if (count($notifications) === 0): ?>
                <p style="color: var(--gray-400); font-size: 13.5px; text-align:center; padding: 20px 0;">
                  No notifications yet.
                </p>
              <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                  <div class="notif-item">
                    <div class="notif-dot <?= $n['status'] === 'read' ? 'read' : '' ?>"></div>
                    <div class="notif-text" style="flex: 1;">
                      <strong><?= htmlspecialchars($n['location_name']) ?> — <?= htmlspecialchars($n['branch_name']) ?></strong>
                      <div style="font-size: 13px; color: var(--gray-600); margin: 2px 0;">
                        <?= htmlspecialchars($n['message']) ?>
                      </div>
                      <span><?= timeAgo($n['timestamp']) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Account -->
        <div class="card">
          <div class="section-title">Account</div>
          <div class="section-sub">Manage your session.</div>

          <div style="margin-top: 16px; padding: 14px; background: var(--gray-50); border-radius: var(--radius-md); border: 1px solid var(--gray-200);">
            <div style="font-size: 13px; color: var(--gray-400); margin-bottom: 4px;">Name</div>
            <div style="font-weight: 600; margin-bottom: 12px;"><?= htmlspecialchars($user['name']) ?></div>

            <div style="font-size: 13px; color: var(--gray-400); margin-bottom: 4px;">Email</div>
            <div style="font-weight: 600;"><?= htmlspecialchars($user['email']) ?></div>
          </div>

          <a href="logout.php" class="btn btn-ghost btn-full" style="margin-top:16px;">Sign out</a>
        </div>

      </div>
    </main>
  </div>

</body>
</html>