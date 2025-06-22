<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get notifications for engineers/admins
$notifications = [];
if ($role == 'admin' || $role == 'engineer') {
    $query = "SELECT n.id, n.message, n.created_at, f.filename 
              FROM notifications n 
              JOIN files f ON n.file_id = f.id 
              WHERE n.user_id = ? AND n.is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CNC File Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fa; }
        .container-fluid { padding-left: 0; padding-right: 0; }
        .content-wrapper { padding-left: 15px; padding-right: 15px; }
        .notification-box { background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba; border-radius: 5px; }
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
                        <a class="nav-link" href="upload.php"><i class="bi bi-upload"></i> Upload File</a>
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
            <h2 class="mb-4"><i class="bi bi-house-door"></i> Dashboard</h2>
            <?php if ($role == 'admin' || $role == 'engineer'): ?>
                <h4>Pending Approvals</h4>
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-box mb-3">
                            <p><strong><?= htmlspecialchars($notification['filename']) ?></strong>: <?= htmlspecialchars($notification['message']) ?></p>
                            <small>Posted on: <?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?></small>
                            <a href="file_browser.php" class="btn btn-sm btn-primary mt-2">Review</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No pending approvals.</p>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Additional dashboard content (e.g., file stats) -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>