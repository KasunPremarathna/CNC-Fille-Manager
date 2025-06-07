<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id > 0) {
    $sql = "SELECT * FROM files WHERE id = $file_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $filepath = $file['filepath'];

        if (file_exists($filepath)) {
            // Set headers to trigger file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit();
        } else {
            echo "File not found.";
        }
    } else {
        echo "Invalid file.";
    }
} else {
    echo "Invalid file ID.";
}
?>
