<?php
session_start();
include 'db.php';

// Check if the user is logged in and session variables exist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Redirect to login page if session is not set
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

// Fetch the file data to populate the edit form
$sql = "SELECT * FROM files WHERE id = $file_id";

// Allow admins, engineers, and programmers to edit any file, but regular users can only edit their own files
if ($role !== 'admin' && $role !== 'engineer' && $role !== 'programmer') {
    $sql .= " AND uploaded_by = $user_id"; // Only allow users to edit their own files
}

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "File not found or you do not have permission to edit this file.";
    exit();
}

$file = $result->fetch_assoc();

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $filename = mysqli_real_escape_string($conn, $_POST['filename']);
    $revision_number = mysqli_real_escape_string($conn, $_POST['revision_number']);

    // Update the file metadata (only filename and revision number) in the database
    $update_sql = "UPDATE files SET filename = '$filename', revision_number = '$revision_number' WHERE id = $file_id";

    if ($conn->query($update_sql) === TRUE) {
        // Redirect back to file browser page after successful update
        header("Location: file_browser.php");
        exit();
    } else {
        echo "Error updating file: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">CNC File Management</a>
            <div class="navbar-nav">
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Edit File</h2>

        <!-- Edit File Form (only Filename and Revision Number) -->
        <form method="POST">
            <div class="mb-3">
                <label for="filename" class="form-label">Filename</label>
                <input type="text" class="form-control" name="filename" id="filename" value="<?php echo htmlspecialchars($file['filename']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="revision_number" class="form-label">Revision Number</label>
                <input type="text" class="form-control" name="revision_number" id="revision_number" value="<?php echo htmlspecialchars($file['revision_number']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update File</button>
        </form>
    </div>
</body>
</html>
