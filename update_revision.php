<?php
// Ensure we return JSON no matter what
header('Content-Type: application/json; charset=UTF-8');

// Disable any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session and include DB
session_start();
include 'db.php';

// Function to send consistent JSON responses
function jsonResponse($success, $message = '', $code = 200) {
    http_response_code($code);
    exit(json_encode([
        'success' => $success,
        'message' => $message
    ]));
}

try {
    // Validate session
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Not authenticated', 401);
    }

    // Validate input
    if (!isset($_POST['file_id']) || !isset($_POST['revision_number'])) {
        jsonResponse(false, 'Missing parameters', 400);
    }

    // Sanitize input
    $file_id = (int)$_POST['file_id'];
    $revision = trim($_POST['revision_number']);

    // Validate revision format
    if (empty($revision)) {
        jsonResponse(false, 'Revision cannot be empty', 400);
    }

    if (!preg_match('/^[A-Za-z0-9.-]+$/', $revision)) {
        jsonResponse(false, 'Only letters, numbers, dots and hyphens allowed', 400);
    }

    // Update database
    $stmt = $conn->prepare("UPDATE files SET revision_number = ? WHERE id = ?");
    if (!$stmt) {
        jsonResponse(false, 'Database prepare failed: ' . $conn->error, 500);
    }

    $stmt->bind_param("si", $revision, $file_id);
    
    if ($stmt->execute()) {
        // Verify the update actually occurred
        if ($stmt->affected_rows > 0) {
            jsonResponse(true, 'Revision updated successfully');
        } else {
            jsonResponse(false, 'No records were updated', 200);
        }
    } else {
        jsonResponse(false, 'Database update failed: ' . $stmt->error, 500);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
}
?>