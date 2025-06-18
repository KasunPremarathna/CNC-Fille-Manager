<?php
session_start();
require_once 'auth.php';
require_once 'db.php';
require_once 'log_activity.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$fileId = $_GET['id'];
$stmt = $conn->prepare("SELECT file_name, file_path FROM files WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $fileId, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    logActivity($_SESSION['user_id'], 'preview_failed', 'File not found or unauthorized: ID ' . $fileId);
    header("Location: file_list.php?error=File not found");
    exit();
}

$file = $result->fetch_assoc();
logActivity($_SESSION['user_id'], 'preview', 'Previewed file: ' . $file['file_name']);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($file['file_name']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        #gcodeCanvas { width: 100%; height: 500px; border: 1px solid #ddd; margin-top: 20px; background-color: #fff; }
        .error { color: red; font-weight: bold; }
        @media (max-width: 576px) {
            #gcodeCanvas { height: 300px; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/gcode-viewer@1.0.0"></script>
</head>
<body>
    <h2>Preview: <?php echo htmlspecialchars($file['file_name']); ?></h2>
    <canvas id="gcodeCanvas"></canvas>
    <script>
        fetch('<?php echo htmlspecialchars($file['file_path']); ?>')
            .then(response => response.text())
            .then(gcode => {
                const viewer = new GCodeViewer(document.getElementById('gcodeCanvas'));
                viewer.loadGCode(gcode);
                viewer.render();
            })
            .catch(error => {
                console.error('Error loading G-code:', error);
                document.getElementById('gcodeCanvas').style.display = 'none';
                document.body.insertAdjacentHTML('beforeend', '<p class="error">Invalid G-code file. Please check the file format.</p>');
            });
    </script>
    <a href="file_list.php">Back to Files</a>
</body>
</html>