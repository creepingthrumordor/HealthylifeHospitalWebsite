<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['fullName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $phone     = trim($_POST['phone'] ?? '');
    $dob       = $_POST['dob'] ?? null;
    $gender    = $_POST['gender'] ?? null;
    $address   = trim($_POST['address'] ?? '');

    if ($full_name && $email && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {

            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, password_plain, role)
                 VALUES (?, ?, ?, ?, 'patient')"
            );
            $stmt->execute([$full_name, $email, $hash, $password]);
            $user_id = $pdo->lastInsertId();


            $stmt2 = $pdo->prepare(
                "INSERT INTO patients (user_id, phone, date_of_birth, gender, address)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt2->execute([$user_id, $phone, $dob, $gender, $address]);

            $pdo->commit();
            $success = 'Registration successful. You can now log in.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
        }
    } else {
        $error = 'Name, email, and password are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.html" class="logo">
            <img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife
        </a>
        <button class="menu-toggle" id="mobile-menu-toggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="patient-registration.php">Register</a></li>
        </ul>
    </div>
</nav>

<div class="container auth-container-large">
    <div class="card">
        <div class="card-header">
            <h2>New Patient Registration</h2>
        </div>

        <?php if (!empty($error)): ?>
            <p style="color: red; margin: 1rem 1.5rem 0;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: green; margin: 1rem 1.5rem 0;">
                <?php echo htmlspecialchars($success); ?>
            </p>
        <?php endif; ?>

        <div style="padding: 1.5rem;">
            <form method="POST" action="patient-registration.php">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>

            <p style="text-align: center; margin-top: 1rem;">
                Already have an account?
                <a href="login.php" style="color: var(--primary-color);">
                    Login here
                </a>
            </p>
        </div>
    </div>
</div>

<footer>
    <p>© 2026 HealthyLife. All rights reserved.</p>
</footer>

<script>
    document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
        this.classList.toggle('active');
        document.getElementById('nav-links').classList.toggle('active');
    });
</script>
</body>
</html>

