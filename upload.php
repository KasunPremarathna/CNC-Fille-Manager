<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $drawing_number = mysqli_real_escape_string($conn, $_POST['drawing_number']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $revision_number = mysqli_real_escape_string($conn, $_POST['revision_number']);
    $file = $_FILES['file'];

    // Validate file
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        die("Invalid file type.");
    }

    // Define upload path
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Insert file with pending status
        $query = "INSERT INTO files (filename, drawing_number, description, uploaded_by, revision_number, status, filepath) 
                  VALUES (?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssiss", $filename, $drawing_number, $description, $user_id, $revision_number, $filepath);
        $stmt->execute();
        $file_id = $conn->insert_id;
        $stmt->close();

        // Create notifications for engineers and admins
        $query = "SELECT id FROM users WHERE role IN ('engineer', 'admin')";
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $notify_query = "INSERT INTO notifications (file_id, user_id, message) 
                             VALUES (?, ?, ?)";
            $message = "New file '$filename' pending approval.";
            $stmt = $conn->prepare($notify_query);
            $stmt->bind_param("iis", $file_id, $row['id'], $message);
            $stmt->execute();
            $stmt->close();
        }

        // Log activity
        include 'log_activity.php';
        log_activity($user_id, "Uploaded file: $filename (Pending Approval)");

        header("Location: file_browser.php");
        exit();
    } else {
        echo "File upload failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File - CNC File Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fa; }
        .container-fluid { padding-left: 0; padding-right: 0; }
        .content-wrapper { padding-left: 15px; padding-right: 15px; }
        @media (max-width: 767px) { .content-wrapper { padding-left: 10px; padding-right: 10px; } }
        @media (max-width: 576px) { .content-wrapper { padding-left: 5px; padding-right: 5px; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">CNC File Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="file_browser.php"><i class="bi bi-folder2-open"></i> Files</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logs.php"><i class="bi bi-journal-text"></i> View Logs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="content-wrapper">
            <h2 class="mb-4"><i class="bi bi-upload"></i> Upload File</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="drawing_number" class="form-label">Drawing Number</label>
                    <input type="text" class="form-control" id="drawing_number" name="drawing_number" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label for="revision_number" class="form-label">Revision Number</label>
                    <input type="text" class="form-control" id="revision_number" name="revision_number">
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">Select File</label>
                    <input type="file" class="form-control" id="file" name="file" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>