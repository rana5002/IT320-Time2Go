<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php?msg=auth_required");
    exit;
}

$userId   = $_SESSION['user_id'];
$branchId = intval($_POST['branch_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'index.php';

if ($branchId <= 0) {
    header("Location: " . $redirect . (strpos($redirect, '?') ? '&' : '?') . "msg=fav_failed");
    exit;
}

$success = false;

// Check if already favorited
$stmt = $conn->prepare("SELECT favorite_id FROM Favorite WHERE user_id = ? AND branch_id = ?");
if ($stmt) {
    $stmt->bind_param("ii", $userId, $branchId);
    if ($stmt->execute()) {
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Remove
            $stmt = $conn->prepare("DELETE FROM Favorite WHERE favorite_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $existing['favorite_id']);
                if ($stmt->execute()) $success = true;
                $stmt->close();
            }
        } else {
            // Add
            $stmt = $conn->prepare("INSERT INTO Favorite (user_id, branch_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ii", $userId, $branchId);
                if ($stmt->execute()) $success = true;
                $stmt->close();
            }
        }
    }
}

if (!$success) {
    $redirect .= (strpos($redirect, '?') ? '&' : '?') . "msg=fav_failed";
}

header("Location: $redirect");
exit;
?>