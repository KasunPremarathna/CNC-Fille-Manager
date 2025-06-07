<?php
// view_file.php

// Make sure the user is logged in
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

// Get the file ID from the URL
$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id <= 0) {
    echo "Invalid file ID.";
    exit();
}

// Fetch the file from the database
$sql = "SELECT * FROM files WHERE id = $file_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $file = $result->fetch_assoc();

    // Check if the file is a STEP or Part file and handle accordingly
    $file_extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
    if (in_array(strtolower($file_extension), ['step', 'stp', 'prt'])) {
        // Provide a path to a converted GLTF file
        $file_path = 'uploads/' . $file['filename']; // Assuming the GLTF file is stored in the 'uploads' directory
        echo "<h2>Viewing File: " . htmlspecialchars($file['filename']) . "</h2>";
        echo "<div id='viewer'></div>";
        echo "<script>
            init3DViewer('$file_path'); // Initialize Three.js viewer with the GLTF file path
        </script>";
    } else {
        echo "File format not supported for viewing.";
    }
} else {
    echo "File not found.";
}
?>
