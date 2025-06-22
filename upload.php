<?php
session_start();
require_once 'db.php';
require_once 'log_activity.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uploadDir = 'uploads/';
$allowedExtensions = ['nc', 'gcode'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $filePath = $uploadDir . time() . '_' . $fileName;
    $userId = $_SESSION['user_id'];

    // Validate file
    if (!in_array($fileExt, $allowedExtensions)) {
        header("Location: file_list.php?error=Invalid file type");
        exit();
    }
    if ($file['size'] > $maxFileSize) {
        header("Location: file_list.php?error=File too large");
        exit();
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: file_list.php?error=Upload failed");
        exit();
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Insert into files table
        $stmt = $conn->prepare("INSERT INTO files (user_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $fileName, $filePath);
        if ($stmt->execute()) {
            // Log upload action
            logActivity($userId, 'upload', 'Uploaded file: ' . $fileName);
            header("Location: file_list.php?success=File uploaded successfully");
        } else {
            logActivity($userId, 'upload_failed', 'Failed to save file to database: ' . $fileName);
            header("Location: file_list.php?error=Database error");
        }
        $stmt->close();
    } else {
        logActivity($userId, 'upload_failed', 'Failed to move file: ' . $fileName);
        header("Location: file_list.php?error=Upload failed");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload CNC File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fa; }
        .container { margin-top: 30px; }
        .upload-form { max-width: 500px; }
        .btn-upload { background-color: #4e73df; color: white; }
        .btn-upload:hover { background-color: #1c3d8a; }
        @media (max-width: 576px) {
            .upload-form { padding: 0 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Upload CNC File</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <form class="upload-form" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="file" class="form-label">Select CNC File (.nc, .gcode)</label>
                <input type="file" class="form-control" id="file" name="file" accept=".nc,.gcode" required>
            </div>
            <button type="submit" class="btn btn-upload">Upload</button>
        </form>
        <a href="file_list.php" class="btn btn-primary mt-3">Back to Files</a>
    </div>
</body>
</html>