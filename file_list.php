<?php
session_start();
require_once 'auth.php';
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, file_name FROM files WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File List</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { padding: 5px 10px; text-decoration: none; color: #fff; border-radius: 3px; }
        .btn-preview { background-color: #17a2b8; }
        .btn-download { background-color: #007bff; }
        .btn-delete { background-color: #dc3545; }
        .success { color: green; }
        .error { color: red; }
        @media (max-width: 576px) {
            .btn { font-size: 0.8rem; padding: 4px 8px; }
        }
    </style>
</head>
<body>
    <h2>Your CNC Files</h2>
    <?php if (isset($_GET['success'])) echo "<p class='success'>" . htmlspecialchars($_GET['success']) . "</p>"; ?>
    <?php if (isset($_GET['error'])) echo "<p class='error'>" . htmlspecialchars($_GET['error']) . "</p>"; ?>
    <table>
        <thead>
            <tr>
                <th>File Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                    <td>
                        <a href="preview.php?id=<?php echo $row['id']; ?>" class="btn btn-preview">Preview</a>
                        <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-download">Download</a>
                        <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>

<?php $stmt->close(); ?>