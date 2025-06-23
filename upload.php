<?php
session_start();
include 'db.php';
include 'log_activity.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$uploaded_by = $_SESSION['user_id'];
$uploadDir = 'Uploads/';

// Ensure Uploads directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file uploads
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
        $filename = $_FILES['files']['name'][$key];
        // Sanitize filename
        $sanitized_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filepath = $uploadDir . $sanitized_filename;

        // Validate file
        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
            $error_codes = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $error = $error_codes[$_FILES['files']['error'][$key]] ?? 'Unknown error';
            logActivity($uploaded_by, 'upload_failed', 'Failed to upload file: ' . $filename . ' (' . $error . ')');
            continue;
        }
        if ($_FILES['files']['size'][$key] == 0) {
            logActivity($uploaded_by, 'upload_failed', 'Empty file: ' . $filename);
            continue;
        }

        // Check MIME type for .nc, .txt, .pdf, .step
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        $allowed_mimes = [
            'text/plain' => ['txt', 'nc'],
            'application/octet-stream' => ['nc'],
            'application/pdf' => ['pdf'],
            'application/step' => ['step'],
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_mimes[$mime] ?? [])) {
            logActivity($uploaded_by, 'upload_failed', 'Invalid MIME type for file: ' . $filename . ' (' . $mime . ')');
            continue;
        }

        // Attempt to move uploaded file
        if (move_uploaded_file($tmp_name, $filepath)) {
            // Extract Drawing Number and Revision Number
            preg_match('/^\d+/', $filename, $drawing_matches);
            $drawing_number = $drawing_matches[0] ?? '';

            preg_match('/_Rev\s+([^-]+)/', $filename, $revision_matches);
            $revision_number = trim($revision_matches[1] ?? '');

            // Sanitize description
            $description = mysqli_real_escape_string($conn, $_POST['file_description'][$key] ?? '');

            // Insert file metadata with status
            $sql = "INSERT INTO files (drawing_number, revision_number, filename, filepath, description, uploaded_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $drawing_number, $revision_number, $filename, $filepath, $description, $uploaded_by);
            
            if ($stmt->execute()) {
                $file_id = $conn->insert_id;
                logActivity($uploaded_by, 'upload', 'Uploaded file: ' . $filename . ' (Pending Approval)');

                // Create notifications for admins and engineers
                $query = "SELECT id FROM users WHERE role IN ('admin', 'engineer')";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    $notify_sql = "INSERT INTO notifications (file_id, user_id, message) VALUES (?, ?, ?)";
                    $message = "New file '$filename' pending approval.";
                    $notify_stmt = $conn->prepare($notify_sql);
                    $notify_stmt->bind_param("iis", $file_id, $row['id'], $message);
                    $notify_stmt->execute();
                    $notify_stmt->close();
                }
            } else {
                logActivity($uploaded_by, 'upload_failed', 'Failed to save file to database: ' . $filename);
                unlink($filepath);
            }
            $stmt->close();
        } else {
            $error = error_get_last()['message'] ?? 'Unknown error';
            logActivity($uploaded_by, 'upload_failed', 'Failed to move file: ' . $filename . ' (' . $error . ')');
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
    <title>Upload Files - CNC File Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        document.getElementById('uploadForm').onsubmit = function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            document.getElementById('progress-container').style.display = 'block';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);

            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percent = (event.loaded / event.total) * 100;
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('progress-bar').setAttribute('aria-valuenow', percent);
                }
            };

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
<?php $conn->close(); ?>