<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];


try {

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
    $total_patients = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor'");
    $total_doctors = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('receptionist', 'admin', 'doctor')");
    $total_staff = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURRENT_DATE");
    $today_appointments = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT SUM(paid_amount) as total FROM bills WHERE MONTH(bill_date) = MONTH(CURRENT_DATE) AND YEAR(bill_date) = YEAR(CURRENT_DATE)");
    $monthly_revenue = $stmt->fetch()['total'] ?: 0;


    $stmt = $pdo->query("SELECT SUM(total_amount - paid_amount) as total FROM bills WHERE status = 'pending'");
    $pending_bills = $stmt->fetch()['total'] ?: 0;


    $stmt = $pdo->query("
        SELECT al.*, u.full_name
        FROM activity_logs al
        JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll();

} catch (Exception $e) {
    $total_patients = 0;
    $total_doctors = 0;
    $total_staff = 0;
    $today_appointments = 0;
    $monthly_revenue = 0;
    $pending_bills = 0;
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Healthylife</title>
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
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li><a href="admin-doctor-management.php">Doctors</a></li>
                <li><a href="admin-staff-directory.php">Staff</a></li>
                <li><a href="admin-billing-management.php">Billing</a></li>
                <li><a href="admin-patient-records.php">Patients</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Welcome, <?php echo htmlspecialchars($full_name); ?> - Hospital Overview</h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-value"><?php echo $total_patients; ?></div>
                <small style="color: var(--text-light);">Registered users</small>
            </div>
            <div class="stat-card">
                <h3>Active Doctors</h3>
                <div class="stat-value"><?php echo $total_doctors; ?></div>
                <small style="color: var(--text-light);">Medical staff</small>
            </div>
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <small style="color: var(--text-light);">Scheduled today</small>
            </div>
            <div class="stat-card">
                <h3>Monthly Revenue</h3>
                <div class="stat-value">LKR <?php echo number_format($monthly_revenue/1000, 1); ?>K</div>
                <small style="color: var(--text-light);">This month</small>
            </div>
            <div class="stat-card">
                <h3>Pending Bills</h3>
                <div class="stat-value">LKR <?php echo number_format($pending_bills/1000, 1); ?>K</div>
                <small style="color: var(--text-light);">Outstanding</small>
            </div>
            <div class="stat-card">
                <h3>Staff Members</h3>
                <div class="stat-value"><?php echo $total_staff; ?></div>
                <small style="color: var(--text-light);">All departments</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recent Activities</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Activity</th>
                            <th>User</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d h:i A', strtotime($activity['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center;">No recent activities found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="admin-doctor-management.php" class="btn btn-primary">Manage Doctors</a>
                <a href="admin-staff-directory.php" class="btn btn-secondary">Staff Directory</a>
                <a href="admin-billing-management.php" class="btn btn-outline">Billing Management</a>
                <a href="admin-patient-records.php" class="btn btn-outline">Patient Records</a>
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

