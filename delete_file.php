<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // Get the user's role

// Ensure the file ID is passed and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid file ID.";
    exit();
}

$file_id = $_GET['id']; // Get file ID from URL

// Fetch the file data
$sql = "SELECT * FROM files WHERE id = $file_id";

// Allow admins, engineers, and programmers to delete any file, but regular users can only delete their own files
if ($role !== 'admin' && $role !== 'engineer' && $role !== 'programmer') {
    $sql .= " AND uploaded_by = $user_id"; // Only allow users to delete their own files
}

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "File not found or you do not have permission to delete this file.";
    exit();
}

$file = $result->fetch_assoc();

// Get the file path from the database
$file_path = $file['filepath'];

// Delete the file from the server
if (file_exists($file_path)) {
    unlink($file_path); // This will delete the file from the server
}

// Delete the file record from the database
$delete_sql = "DELETE FROM files WHERE id = $file_id";
if ($conn->query($delete_sql) === TRUE) {
    // Redirect back to file browser page after successful delete
    header("Location: file_browser.php");
    exit();
} else {
    echo "Error deleting file: " . $conn->error;
}
?>
