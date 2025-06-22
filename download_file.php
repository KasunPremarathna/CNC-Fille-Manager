<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (isset($_GET['id'])) {
    $file_id = (int)$_GET['id'];
    
    // Check file status
    $query = "SELECT filename, filepath, status FROM files WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    $stmt->close();

    if ($file) {
        if ($role == 'operator' || $role == 'supervisor') {
            if ($file['status'] != 'approved') {
                die("You can only download approved files.");
            }
        }

        $filepath = $file['filepath'];
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
            readfile($filepath);
            exit();
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid file ID.";
    }
} else {
    echo "No file ID provided.";
}
$conn->close();
?>