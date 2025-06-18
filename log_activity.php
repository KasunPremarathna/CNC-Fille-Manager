<?php
session_start();
require_once 'db.php';

function logActivity($userId, $action, $details) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>