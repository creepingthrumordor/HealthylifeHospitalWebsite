<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || trim($_SESSION['role']) !== 'receptionist') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];


try {

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient'");
    $total_patients = $stmt->fetch()['count'];


    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURRENT_DATE");
    $stmt->execute();
    $today_appointments = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inquiries WHERE status IN ('new', 'pending', 'in-progress')");
    $pending_inquiries = $stmt->fetch()['count'];


    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $upcoming_week = $stmt->fetch()['count'];


    $stmt = $pdo->prepare("
        SELECT a.*, p_user.full_name AS patient_name, d_user.full_name AS doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users p_user ON p.user_id = p_user.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users d_user ON d.user_id = d_user.user_id
        WHERE a.appointment_date = CURRENT_DATE
        ORDER BY a.appointment_time ASC
        LIMIT 5
    ");
    $stmt->execute();
    $todays_list = $stmt->fetchAll();

} catch (Exception $e) {
    $total_patients = 0;
    $today_appointments = 0;
    $pending_inquiries = 0;
    $upcoming_week = 0;
    $todays_list = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - Healthylife</title>
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
                <li><a href="receptionist-dashboard.php">Dashboard</a></li>
                <li><a href="receptionist-appointments.php">Appointments</a></li>
                <li><a href="receptionist-billing.php">Billing</a></li>
                <li><a href="receptionist-inquiries.php">Inquiries</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Welcome, <?php echo htmlspecialchars($full_name); ?></h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Inquiries</h3>
                <div class="stat-value"><?php echo $pending_inquiries; ?></div>
            </div>
            <div class="stat-card">
                <h3>Upcoming (This Week)</h3>
                <div class="stat-value"><?php echo $upcoming_week; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-value"><?php echo $total_patients; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Today's Appointments Overview</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($todays_list) > 0): ?>
                            <?php foreach ($todays_list as $row): ?>
                                <tr>
                                    <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name'] ?? $row['specialization']); ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($row['status']);
                                            $color = 'var(--text-dark)';
                                            if ($st === 'confirmed') $color = 'var(--secondary-color)';
                                            elseif ($st === 'pending') $color = 'var(--warning-color)';
                                            elseif ($st === 'cancelled') $color = 'var(--danger-color)';
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo ucfirst($st); ?></span>
                                    </td>
                                    <td><a href="receptionist-appointments.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Manage</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No appointments for today.</td></tr>
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
                <a href="receptionist-appointments.php" class="btn btn-primary">Manage All Appointments</a>
                <a href="receptionist-billing.php" class="btn btn-secondary">Billing Management</a>
                <a href="receptionist-inquiries.php" class="btn btn-outline">View Inquiries</a>
                <a href="patient-registration.php" class="btn btn-outline">Register New Patient</a>
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

