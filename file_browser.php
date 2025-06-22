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
$role = $_SESSION['role']; // Get the user's role

// Pagination or Show All
$limit = 10; // Pagination limit
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Show all flag (when "Show All" button is clicked)
$show_all = isset($_GET['show_all']) ? true : false;

// Search and Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Base query
$sql = "SELECT files.*, users.username, users.role FROM files JOIN users ON files.uploaded_by = users.id";

if (!empty($search)) {
    $sql .= " WHERE (files.filename LIKE '%$search%' OR files.drawing_number LIKE '%$search%' OR files.description LIKE '%$search%')";
}

// If not showing all files, add pagination
if (!$show_all) {
    $sql .= " ORDER BY files.created_at DESC LIMIT $limit OFFSET $offset";
}

// Count total records (for pagination)
$count_sql = str_replace('files.*, users.username, users.role', 'COUNT(*) AS total', $sql);
$count_result = $conn->query($count_sql);

if ($count_result === false) {
    die("Error in count query: " . $conn->error);
}

$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get files based on the query
$result = $conn->query($sql);

if ($result === false) {
    die("Error in main query: " . $conn->error);
}

// Function to get the count of comments for a file
function getCommentCount($fileId, $conn) {
    $query = "SELECT COUNT(*) AS count FROM comments WHERE file_id = $fileId";
    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    return $data['count'];
}

// Function to get the comments for a file
function displayComments($fileId, $conn) {
    $query = "SELECT comments.comment_text, comments.created_at, users.username 
              FROM comments JOIN users ON comments.user_id = users.id 
              WHERE comments.file_id = $fileId 
              ORDER BY comments.created_at DESC";
    $result = $conn->query($query);

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
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Browser</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .comment-box {
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Make the file table scrollable */
        .scrollable-table-container {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">CNC File Management</a>
            <div class="navbar-nav">
                <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="bi bi-folder2-open"></i> File Browser</h2>

        <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
            <a href="upload.php" class="btn btn-primary mb-3">
                <i class="bi bi-upload"></i> Upload File
            </a>
        <?php endif; ?>

        <form method="GET" class="mb-4">
            <div class="row g-2">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by Filename, Drawing Number, or Description" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="file_browser.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>

        <!-- Show All Files Option -->
        <form method="GET" class="mb-4">
            <button type="submit" name="show_all" class="btn btn-warning w-100"><i class="bi bi-eye"></i> Show All Files</button>
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
                        <div class="scrollable-table-container">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Filename</th>
                                        <th>Description</th>
                                        <th>Uploader</th>
                                        <th>Revision</th>
                                        <th>Uploaded On</th>
                                        <th>Comments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as $file): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($file['filename']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($file['description']) ?></td>
                                            <td><span class="badge bg-<?= $file['role'] == 'admin' ? 'danger' : ($file['role'] == 'engineer' ? 'warning' : 'info') ?>"><?= htmlspecialchars($file['username']) ?></span></td>
                                            <td><?= htmlspecialchars($file['revision_number']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                            <td>
                                                <?php
                                                // Display the comments for this file
                                                displayComments($file['id'], $conn);
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="download_file.php?id=<?= $file['id'] ?>" class="btn btn-primary" title="Download"><i class="bi bi-download"></i></a>
                                                    
                                                    <!-- Add comment button with "+" icon -->
                                                    <button class="btn btn-success" onclick="showAddCommentForm(<?= $file['id'] ?>)">
                                                        <i class="bi bi-plus"></i> Comment
                                                    </button>

                                                    <?php if ($role == 'admin' || $role == 'engineer' || $role == 'programmer'): ?>
                                                        <button class="btn btn-danger" onclick="confirmDelete(<?= $file['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
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
        <?php endif; ?>
    </div>

    <!-- Modal for adding a comment -->
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
        // Function to open comment form modal
        function showAddCommentForm(fileId) {
            document.getElementById('file-id').value = fileId;
            var addCommentModal = new bootstrap.Modal(document.getElementById('addCommentModal'));
            addCommentModal.show();
        }

        // Submit comment function
        function submitComment() {
            var commentText = document.getElementById('new-comment').value;
            var fileId = document.getElementById('file-id').value;

            if (!commentText.trim()) {
                alert("Please enter a comment.");
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "submit_comment.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    alert('Comment added successfully');
                    window.location.reload();
                }
            };

            xhr.send("comment=" + encodeURIComponent(commentText) + "&file_id=" + encodeURIComponent(fileId));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
