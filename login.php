<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare(
            "SELECT user_id, full_name, email, password_hash, password_plain, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $is_valid = false;
        if ($user && $user['status'] === 'active') {
            if (password_verify($password, $user['password_hash']) || $password === $user['password_plain']) {
                $is_valid = true;
            }
        }

        if ($is_valid) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $role = trim($user['role']);
            $_SESSION['role']      = $role;


            switch ($role) {
                case 'patient':
                    header('Location: patient-dashboard.php');
                    break;
                case 'doctor':
                    header('Location: doctor-dashboard.php');
                    break;
                case 'receptionist':
                    header('Location: receptionist-dashboard.php');
                    break;
                case 'admin':
                    header('Location: admin-dashboard.php');
                    break;
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="patient-registration.php">Register</a></li>
        </ul>
        <a href="index.html" class="logo"><img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife</a>
    </div>
</nav>

<div class="container" style="max-width: 500px; margin-top: 4rem;">
    <div class="card">
        <div class="card-header">
            <h2>Login to Your Account</h2>
        </div>

        <?php if (!empty($error)): ?>
            <p style="color: red; margin: 1rem 1.5rem 0;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <div style="padding: 1.5rem;">
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>

            <p style="text-align: center; margin-top: 1rem;">
                Don't have an account?
                <a href="patient-registration.php" style="color: var(--primary-color);">
                    Register here
                </a>
            </p>
        </div>
    </div>
</div>

<footer>
    <p>© 2025 HealthyLife. All rights reserved.</p>
</footer>
</body>
</html>
