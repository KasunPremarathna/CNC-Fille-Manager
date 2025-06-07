<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
       body {
    background: url('/images/im1.webp') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Arial', sans-serif;
    color: white;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}


        .login-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-card h3 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .login-card .form-label {
            color: #333;
        }

        .login-card input {
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
            width: 100%;
            border: 1px solid #ccc;
        }

        .login-card button {
            background-color: #4e73df;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-card button:hover {
            background-color: #1c3d8a;
        }

        .login-card .form-text {
            font-size: 14px;
            color: #888;
            text-align: center;
        }

        .login-card a {
            color: #4e73df;
        }

        .login-card a:hover {
            text-decoration: underline;
        }

        .footer-text {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3>Welcome Back!</h3>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="footer-text">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
