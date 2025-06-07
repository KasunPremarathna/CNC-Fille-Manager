<?php
session_start();
include 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to submit a comment.");
}

$user_id = $_SESSION['user_id'];

// Check if the comment and file_id are provided via POST
if (isset($_POST['comment']) && isset($_POST['file_id'])) {
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $file_id = (int)$_POST['file_id'];

    // Check if the comment is not empty
    if (!empty($comment)) {
        // Insert the comment into the database
        $query = "INSERT INTO comments (file_id, user_id, comment_text, created_at) 
                  VALUES ('$file_id', '$user_id', '$comment', NOW())";

        if ($conn->query($query) === TRUE) {
            echo "Comment added successfully.";
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Please enter a comment.";
    }
} else {
    echo "Invalid request. File ID or comment missing.";
}
?>
