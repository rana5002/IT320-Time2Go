<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$userId   = $_SESSION['user_id'];
$branchId = intval($_POST['branch_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'index.php';

if ($branchId > 0) {
    // Check if already favorited
    $stmt = $conn->prepare("SELECT favorite_id FROM Favorite WHERE user_id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $userId, $branchId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Remove
        $stmt = $conn->prepare("DELETE FROM Favorite WHERE favorite_id = ?");
        $stmt->bind_param("i", $existing['favorite_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO Favorite (user_id, branch_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $branchId);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: $redirect");
exit;
?>