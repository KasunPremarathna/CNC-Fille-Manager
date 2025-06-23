<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Pagination or Show All
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Show all flag
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

// Search and Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

// Base query with prepared statements
$sql = "SELECT files.*, users.username, users.role FROM files JOIN users ON files.uploaded_by = users.id";
$count_sql = "SELECT COUNT(*) AS total FROM files JOIN users ON files.uploaded_by = users.id";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " WHERE (files.filename LIKE ? OR files.drawing_number LIKE ? OR files.description LIKE ?)";
    $count_sql .= " WHERE (files.filename LIKE ? OR files.drawing_number LIKE ? OR files.description LIKE ?)";
    $params = [$search_param, $search_param, $search_param];
    $types = 'sss';
}

if (!$show_all) {
    $sql .= " ORDER BY files.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
}

// Count total records
$stmt = $conn->prepare($count_sql);
if ($types) {
    $stmt->bind_param($types, ...array_slice($params, 0, count($params) - ($show_all ? 0 : 2)));
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_records = 0;

if ($count_result) {
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row ? (int)$count_row['total'] : 0;
} else {
    error_log("Count query failed: " . $conn->error);
    die("Error in count query: " . htmlspecialchars($conn->error));
}
$stmt->close();

$total_pages = $show_all ? 1 : ceil($total_records / $limit);

// Get files
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    error_log("Main query failed: " . $conn->error);
    die("Error in main query: " . htmlspecialchars($conn->error));
}
$stmt->close();

// Function to get comment count
function getCommentCount($fileId, $conn) {
    $query = "SELECT COUNT(*) AS count FROM comments WHERE file_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

// Function to display comments
function displayComments($fileId, $conn) {
    $query = "SELECT comments.comment_text, comments.created_at, users.username 
              FROM comments JOIN users ON comments.user_id = users.id 
              WHERE comments.file_id = ? 
              ORDER BY comments.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='comment-box mb-3'>
                    <p><strong>" . htmlspecialchars($row['username']) . ":</strong> " . htmlspecialchars($row['comment_text']) . "</p>
                    <small>Posted on: " . date('Y-m-d H:i', strtotime($row['created_at'])) . "</small>
                  </div>";
        }
    } else {
        echo "<p>No comments available.</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Browser - CNC File Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fa; }
        .container-fluid { padding-left: 0; padding-right: 0; }
        .content-wrapper { padding-left: 15px; padding-right: 15px; }
        .comment-box {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .scrollable-table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        .table-responsive { width: 100%; }
        .btn-group-sm .btn { padding: 5px 10px; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #28a745; font-weight: bold; }
        @media (max-width: 767px) {
            .content-wrapper { padding-left: 10px; padding-right: 10px; }
            .table { font-size: 14px; }
            .table th:not(:nth-child(1)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(8)),
            .table td:not(:nth-child(1)):not(:nth-child(6)):not(:nth-child(7)):not(:nth-child(8)) {
                display: none;
            }
            .btn-group-sm .btn {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }
        }
        @media (max-width: 576px) {
            .content-wrapper { padding-left: 5px; padding-right: 5px; }
            .table { font-size: 12px; }
            .card-header h5 { font-size: 1.2rem; }
            .input-group input, .btn { font-size: 0.9rem; }
        }
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
            <h2 class="mb-4"><i class="bi bi-folder2-open"></i> File Browser</h2>

            <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                <a href="upload.php" class="btn btn-primary mb-3">
                    <i class="bi bi-upload"></i> Upload File
                </a>
            <?php endif; ?>

            <form method="GET" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-8 col-12">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by Filename, Drawing Number, or Description" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                    </div>
                    <div class="col-md-2 col-6">
                        <a href="file_browser.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                    </div>
                </div>
            </form>

            <form method="GET" class="mb-4">
                <button type="submit" name="show_all" value="1" class="btn btn-warning w-100"><i class="bi bi-eye"></i> Show All Files</button>
            </form>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info">No files found matching your criteria.</div>
            <?php else: ?>
                <?php
                $files_by_drawing = [];
                while ($row = $result->fetch_assoc()) {
                    $files_by_drawing[$row['drawing_number']][] = $row;
                }

                foreach ($files_by_drawing as $drawing_number => $files): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-drawing-pin"></i> Drawing: <?= htmlspecialchars($drawing_number) ?>
                                <span class="badge bg-primary float-end"><?= count($files) ?> files</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive scrollable-table-container">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Filename</th>
                                            <th class="d-none d-md-table-cell">Description</th>
                                            <th class="d-none d-md-table-cell">Uploader</th>
                                            <th class="d-none d-md-table-cell">Revision</th>
                                            <th class="d-none d-md-table-cell">Uploaded On</th>
                                            <th>Comments</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($files as $file): ?>
                                            <tr>
                                                <td>
                                                    <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($file['filename']) ?>
                                                </td>
                                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($file['description']) ?></td>
                                                <td class="d-none d-md-table-cell">
                                                    <span class="badge bg-<?= $file['role'] == 'admin' ? 'danger' : ($file['role'] == 'engineer' ? 'warning' : 'info') ?>"><?= htmlspecialchars($file['username']) ?></span>
                                                </td>
                                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($file['revision_number']) ?></td>
                                                <td class="d-none d-md-table-cell"><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                                <td>
                                                    <?php displayComments($file['id'], $conn); ?>
                                                </td>
                                                <td>
                                                    <span class="status-<?= $file['status'] ?>">
                                                        <?= ucfirst($file['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($file['status'] == 'approved'): ?>
                                                            <a href="download_file.php?id=<?= $file['id'] ?>" class="btn btn-primary" title="Download"><i class="bi bi-download"></i></a>
                                                        <?php endif; ?>
                                                        <button class="btn btn-success" onclick="showAddCommentForm(<?= $file['id'] ?>)">
                                                            <i class="bi bi-plus"></i> Comment
                                                        </button>
                                                        <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                                                            <button class="btn btn-danger" onclick="confirmDelete(<?= $file['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                                        <?php endif; ?>
                                                        <?php if ($role == 'admin' || $role == 'engineer'): ?>
                                                            <?php if ($file['status'] == 'pending'): ?>
                                                                <a href="approve_file.php?id=<?= $file['id'] ?>" class="btn btn-warning" title="Approve"><i class="bi bi-check-circle"></i></a>
                                                            <?php endif; ?>
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

                <?php if (!$show_all && $total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="addCommentModal" tabindex="-1" aria-labelledby="addCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCommentModalLabel">Add Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="file-id">
                    <textarea id="new-comment" class="form-control" rows="3" placeholder="Enter your comment here..."></textarea>
                    <button type="button" class="btn btn-primary mt-2" onclick="submitComment()">Submit Comment</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddCommentForm(fileId) {
            document.getElementById('file-id').value = fileId;
            var addCommentModal = new bootstrap.Modal(document.getElementById('addCommentModal'));
            addCommentModal.show();
        }

        function submitComment() {
            var commentText = document.getElementById('new-comment').value;
            var fileId = document.getElementById('file-id').value;

            if (!commentText.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Empty Comment',
                    text: 'Please enter a comment.',
                });
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "submit_comment.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Comment Added',
                        text: 'Comment added successfully',
                    }).then(() => {
                        window.location.reload();
                    });
                }
            };

            xhr.send("comment=" + encodeURIComponent(commentText) + "&file_id=" + encodeURIComponent(fileId));
        }

        function confirmDelete(fileId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete_file.php?id=' + fileId;
                }
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>