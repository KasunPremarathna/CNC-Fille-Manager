<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$drawing_id = $_GET['id'];
$sql = "SELECT files.*, users.username 
        FROM files 
        JOIN users ON files.uploaded_by = users.id 
        WHERE files.drawing_id = $drawing_id";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drawing Details</title>
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
        <h2>Drawing Files</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Revision Number</th>
                    <th>Description</th>
                    <th>Uploaded By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['filename']; ?></td>
                        <td><?php echo $row['revision_number']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><a href="<?php echo $row['filepath']; ?>" class="btn btn-primary" download>Download</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>