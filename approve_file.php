<?php
session_start();
include 'db.php';
include 'log_activity.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'engineer'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $file_id = (int)$_GET['id'];

    // Update file status
    $query = "UPDATE files SET status = 'approved' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $stmt->close();

    // Log approval
    log_activity($user_id, "Approved file ID: $file_id");

    // Mark notifications as read
    $query = "UPDATE notifications SET is_read = 1 WHERE file_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $stmt->close();

    header("Location: file_browser.php");
    exit();
} else {
    echo "Invalid file ID.";
}
$conn->close();
?>