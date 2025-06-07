<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploaded_by = $_SESSION['user_id'];

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Invalid file']);
        exit();
    }

    $file = $_FILES['file'];
    $description = $_POST['description'] ?? '';

    $filename = basename($file['name']);
    $filepath = 'uploads/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to move file']);
        exit();
    }

    // Extract drawing and revision numbers
    preg_match('/^\d+/', $filename, $drawing_matches);
    $drawing_number = $drawing_matches[0] ?? '';

    preg_match('/_Rev\s+([^-]+)/', $filename, $revision_matches);
    $revision_number = trim($revision_matches[1] ?? '');

    $stmt = $conn->prepare("INSERT INTO files (drawing_number, revision_number, filename, filepath, description, uploaded_by)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $drawing_number, $revision_number, $filename, $filepath, $description, $uploaded_by);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB insert failed']);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>
