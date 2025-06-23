<?php
session_start();
include 'db.php';
include 'log_activity.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'engineer'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $file_id = (int)$_GET['id'];

    // Update file status
    $query = "UPDATE files SET status = 'approved' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $file_id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        // Log approval
        logActivity($user_id, 'approve', "Approved file ID: $file_id");

        // Mark notifications as read
        $query = "UPDATE notifications SET is_read = 1 WHERE file_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->close();

        // Output SweetAlert2 script
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Done',
                        text: 'File approved successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'file_browser.php';
                    });
                });
              </script>";
    } else {
        echo "Failed to approve file.";
    }
} else {
    echo "Invalid file ID.";
}
$conn->close();
?>