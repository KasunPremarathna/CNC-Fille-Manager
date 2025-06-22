<?php
session_start();
include 'db.php';
include 'log_activity.php'; // Added for logging

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploaded_by = $_SESSION['user_id'];

    // Handle file uploads
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        $filename = $_FILES['files']['name'][$key];
        $filepath = "Uploads/" . basename($filename);

        // Attempt to move uploaded file
        if (move_uploaded_file($tmp_name, $filepath)) {
            // Extract Drawing Number and Revision Number from the filename
            preg_match('/^\d+/', $filename, $drawing_matches);
            $drawing_number = $drawing_matches[0] ?? ''; // Drawing Number, default empty if not matched

            preg_match('/_Rev\s+([^-]+)/', $filename, $revision_matches);
            $revision_number = trim($revision_matches[1] ?? ''); // Revision Number, default empty if not matched

            // Insert file metadata into the files table
            $description = $_POST['file_description'][$key] ?? '';
            $sql = "INSERT INTO files (drawing_number, revision_number, filename, filepath, description, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $drawing_number, $revision_number, $filename, $filepath, $description, $uploaded_by);
            
            if ($stmt->execute()) {
                // Log successful upload
                logActivity($uploaded_by, 'upload', 'Uploaded file: ' . $filename);
            } else {
                // Log failed database insert
                logActivity($uploaded_by, 'upload_failed', 'Failed to save file to database: ' . $filename);
            }
            $stmt->close();
        } else {
            // Log failed file move
            logActivity($uploaded_by, 'upload_failed', 'Failed to move file: ' . $filename);
        }
    }

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
        <h2>Upload Files</h2>
        <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <div id="file-upload-section">
                <div class="mb-3 file-upload">
                    <label for="files" class="form-label">Choose File</label>
                    <input type="file" class="form-control" name="files[]" required>
                    <label for="file_description" class="form-label">File Description</label>
                    <textarea class="form-control" name="file_description[]" rows="2"></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" onclick="addFileUpload()">Add Another File</button>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <div class="mt-3">
            <div id="progress-container" style="display: none;">
                <label for="progress" class="form-label">Upload Progress</label>
                <div class="progress">
                    <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addFileUpload() {
            const fileUploadSection = document.getElementById('file-upload-section');
            const newFileUpload = document.createElement('div');
            newFileUpload.className = 'mb-3 file-upload';
            newFileUpload.innerHTML = `
                <label for="files" class="form-label">Choose File</label>
                <input type="file" class="form-control" name="files[]" required>
                <label for="file_description" class="form-label">File Description</label>
                <textarea class="form-control" name="file_description[]" rows="2"></textarea>
            `;
            fileUploadSection.appendChild(newFileUpload);
        }

        // Handle the form submission to show progress bar
        document.getElementById('uploadForm').onsubmit = function(event) {
            event.preventDefault(); // Prevent form from submitting the normal way
            const formData = new FormData(this);

            // Show the progress bar
            document.getElementById('progress-container').style.display = 'block';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);

            // Update progress bar as the file uploads
            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percent = (event.loaded / event.total) * 100;
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('progress-bar').setAttribute('aria-valuenow', percent);
                }
            };

            // When the upload is finished
            xhr.onload = function() {
                if (xhr.status === 200) {
                    window.location.href = 'dashboard.php';
                }
            };

            xhr.send(formData);
        };
    </script>
</body> 

</html>