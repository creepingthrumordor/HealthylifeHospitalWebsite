<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$success = '';
$error = '';

// Get doctor details
try {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        $error = "Doctor profile not found.";
    }
} catch (Exception $e) {
    $error = "Error fetching doctor profile: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $schedule_text = trim($_POST['schedule_text'] ?? '');
    $availability = $_POST['availability'] ?? 'available';
    
    try {
        $stmt = $pdo->prepare("UPDATE doctors SET schedule_text = ?, availability = ? WHERE user_id = ?");
        $stmt->execute([$schedule_text, $availability, $user_id]);
        $success = "Schedule updated successfully!";
        
        // Refresh doctor data
        $doctor['schedule_text'] = $schedule_text;
        $doctor['availability'] = $availability;
    } catch (Exception $e) {
        $error = "Error updating schedule: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Schedule - Healthylife</title>
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
                <li><a href="doctor-dashboard.php">Dashboard</a></li>
                <li><a href="doctor-upload-report.php">Upload Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="max-width: 800px; margin-top: 2rem;">
        <div class="card">
            <div class="card-header">
                <h2>Manage Your Schedule & Availability</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="doctor-update-schedule.php">
                <div class="form-group">
                    <label for="availability">Current Availability Status</label>
                    <select name="availability" id="availability">
                        <option value="available" <?php echo ($doctor['availability'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo ($doctor['availability'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        <option value="on-leave" <?php echo ($doctor['availability'] ?? '') === 'on-leave' ? 'selected' : ''; ?>>On Leave</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="schedule_text">Weekly Schedule (e.g., Mon-Fri, 9 AM - 5 PM)</label>
                    <textarea name="schedule_text" id="schedule_text" placeholder="Describe your working hours..."><?php echo htmlspecialchars($doctor['schedule_text'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="doctor-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                </div>
            </form>
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
