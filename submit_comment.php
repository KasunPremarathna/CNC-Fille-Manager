<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Remove after debugging

include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    error_log("Unauthorized access attempt in submit_comment.php");
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comment']) || !isset($_POST['file_id'])) {
    http_response_code(400);
    error_log("Invalid request in submit_comment.php: " . print_r($_POST, true));
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

$comment_text = trim($_POST['comment']);
$file_id = (int)$_POST['file_id'];

if (empty($comment_text)) {
    http_response_code(400);
    error_log("Empty comment submitted for file_id: $file_id");
    echo json_encode(['error' => 'Comment cannot be empty.']);
    exit();
}

// Verify file exists
$stmt = $conn->prepare("SELECT filename FROM files WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    error_log("Prepare failed for file check: " . $conn->error);
    echo json_encode(['error' => 'Database error.']);
    exit();
}
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    error_log("File not found for file_id: $file_id");
    echo json_encode(['error' => 'File not found.']);
    exit();
}
$file = $result->fetch_assoc();
$filename = $file['filename'];
$stmt->close();

// Insert comment
$stmt = $conn->prepare("INSERT INTO comments (file_id, user_id, comment_text) VALUES (?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    error_log("Prepare failed for comment insert: " . $conn->error);
    echo json_encode(['error' => 'Database error.']);
    exit();
}
$stmt->bind_param("iis", $file_id, $user_id, $comment_text);
if ($stmt->execute()) {
    // Log the comment action
    if (file_exists('log_activity.php')) {
        include 'log_activity.php';
        logActivity($user_id, 'comment', "Added comment to file ID: $file_id ($filename)");
    } else {
        error_log("log_activity.php not found, skipping comment logging");
    }
    http_response_code(200);
    echo json_encode(['success' => 'Comment added successfully.']);
} else {
    http_response_code(500);
    error_log("Comment insert failed: " . $conn->error);
    echo json_encode(['error' => 'Failed to add comment.']);
}
$stmt->close();

$conn->close();
?>