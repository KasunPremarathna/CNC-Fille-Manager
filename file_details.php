<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$file_id = $_GET['id'];
$sql = "SELECT * FROM files WHERE id = $file_id";
$result = $conn->query($sql);
$file = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Details</title>
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
        <h2>File Details</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo $file['filename']; ?></h5>
                <p class="card-text">Drawing Number: <?php echo $file['drawing_number']; ?></p>
                <p class="card-text">Revision Number: <?php echo $file['revision_number']; ?></p>
                <p class="card-text">Description: <?php echo $file['description']; ?></p>
                <a href="<?php echo $file['filepath']; ?>" class="btn btn-primary" download>Download</a>
            </div>
        </div>
    </div>
</body>
</html>