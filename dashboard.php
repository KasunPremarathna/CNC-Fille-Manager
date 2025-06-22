<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Dashboard recent files query
$recent_files_sql = "SELECT * FROM files WHERE uploaded_by = $user_id ORDER BY created_at DESC LIMIT 5";
$recent_files_result = $conn->query($recent_files_sql);

// File browser pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Base query for file browser
$sql = "SELECT files.*, users.username, users.role FROM files JOIN users ON files.uploaded_by = users.id";

if (!empty($search)) {
    $sql .= " WHERE (files.filename LIKE '%$search%' OR files.drawing_number LIKE '%$search%' OR files.description LIKE '%$search%')";
}

// Count total records
$count_sql = str_replace('files.*, users.username, users.role', 'COUNT(*) AS total', $sql);
$count_result = $conn->query($count_sql);

if ($count_result === false) {
    die("Error in count query: " . $conn->error);
}

$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Add pagination
$sql .= " ORDER BY files.created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

if ($result === false) {
    die("Error in main query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Browse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar {
            position: sticky;
            top: 20px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .badge-role {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-files"></i> CNC File Management
            </a>
            <div class="navbar-nav">
                <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <div class="list-group">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                            <a href="upload.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-upload"></i> Upload File
                            </a>
                        <?php endif; ?>
                        <a href="file_browser.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-folder"></i> File Browser
                        </a>
                        
                       <a href="test.zip" class="list-group-item list-group-item-action list-group-item-primary">
    <i class="bi bi-folder"></i> TEST FILE'S 
</a>
                    </div>

                    <!-- Recent Files Panel -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Files</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_files_result->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($file = $recent_files_result->fetch_assoc()): ?>
                                        <a href="download_file.php?id=<?= $file['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($file['filename']) ?></h6>
                                                <small><?= date('M j', strtotime($file['created_at'])) ?></small>
                                            </div>
                                            <small class="text-muted">Rev: <?= htmlspecialchars($file['revision_number']) ?></small>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No recent files</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-folder2-open"></i> File Browser</h2>
                    <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                        <a href="upload.php" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload File
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Search and Filter -->
                <form method="GET" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by Filename, Drawing Number, or Description" 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="file_browser.php" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <!-- File Browser Content -->
                <?php if ($result->num_rows === 0): ?>
                    <div class="alert alert-info">No files found matching your criteria.</div>
                <?php else: ?>
                    <?php
                    $files_by_drawing = [];
                    while ($row = $result->fetch_assoc()) {
                        $files_by_drawing[$row['drawing_number']][] = $row;
                    }

                    foreach ($files_by_drawing as $drawing_number => $files): ?>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-drawing-pin"></i> Drawing: <?= htmlspecialchars($drawing_number) ?>
                                    <span class="badge bg-primary float-end"><?= count($files) ?> files</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Filename</th>
                                                <th>Description</th>
                                                <th>Uploader</th>
                                                <th>Revision</th>
                                                <th>Uploaded On</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($files as $file): ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-file-earmark"></i> 
                                                        <?= htmlspecialchars($file['filename']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($file['description']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $file['role'] == 'admin' ? 'danger' : 
                                                            ($file['role'] == 'engineer' ? 'warning' : 'info') 
                                                        ?>">
                                                            <?= htmlspecialchars($file['username']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($file['revision_number']) ?></td>
                                                    <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="download_file.php?id=<?= $file['id'] ?>" 
                                                               class="btn btn-primary" title="Download">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                            <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                                                                <a href="delete_file.php?id=<?= $file['id'] ?>" 
                                                                   class="btn btn-danger" 
                                                                   onclick="return confirm('Are you sure you want to delete this file?');"
                                                                   title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                        <a class="page-link" 
                                           href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" 
                                       href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>