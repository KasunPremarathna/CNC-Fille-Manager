<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Remove in production
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// No role check to allow all users to view logs

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$stmt = $conn->prepare("SELECT l.id, u.username, l.user_id, l.action, l.details, l.timestamp FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC");
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fa; }
        .container { margin-top: 30px; }
        .table th { background-color: #4e73df; color: white; }
        @media (max-width: 576px) {
            .table { font-size: 0.9rem; }
        }
        .text-center { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Activity Logs</h2>
        <?php if ($result->num_rows === 0): ?>
            <div class="alert alert-info">No logs found.</div>
        <?php endif; ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>User ID</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['action']); ?></td>
                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
</html>

<?php $stmt->close(); ?>