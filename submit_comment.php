<?php
session_start();
include 'db.php';
include 'log_activity.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_POST['file_id'])) {
    $comment_text = trim($_POST['comment']);
    $file_id = (int)$_POST['file_id'];

    if (empty($comment_text)) {
        http_response_code(400);
        echo "Comment cannot be empty.";
        exit();
    }

    // Verify file exists
    $stmt = $conn->prepare("SELECT filename FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        http_response_code(404);
        echo "File not found.";
        exit();
    }
    $file = $result->fetch_assoc();
    $filename = $file['filename'];
    $stmt->close();

    // Insert comment
    $stmt = $conn->prepare("INSERT INTO comments (file_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $file_id, $user_id, $comment_text);
    if ($stmt->execute()) {
        // Log the comment action
        logActivity($user_id, 'comment', "Added comment to file ID: $file_id ($filename)");
        http_response_code(200);
        echo "Comment added successfully.";
    } else {
        error_log("Comment insert failed: " . $conn->error);
        http_response_code(500);
        echo "Failed to add comment.";
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "Invalid request.";
}

$conn->close();
?>