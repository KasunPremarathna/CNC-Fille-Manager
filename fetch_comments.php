<?php
include 'db.php';

if (isset($_GET['file_id'])) {
    $file_id = (int)$_GET['file_id'];

    // Fetch the comments
    $query = "SELECT comments.comment_text, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.file_id = $file_id ORDER BY comments.created_at DESC";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p><strong>" . htmlspecialchars($row['username']) . ":</strong> " . htmlspecialchars($row['comment_text']) . "</p>";
        }
    } else {
        echo "<p>No comments available.</p>";
    }
}
?>
