<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];


$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
$patient_id = $patient['patient_id'] ?? null;


$upcoming_count = 0;
$pending_bills = 0;
$report_count = 0;
$total_visits = 0;
$upcoming_appointments = [];

if ($patient_id) {

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status IN ('pending', 'confirmed') AND appointment_date >= CURRENT_DATE");
    $stmt->execute([$patient_id]);
    $upcoming_count = $stmt->fetchColumn();


    $stmt = $pdo->prepare("SELECT SUM(total_amount - paid_amount) FROM bills WHERE patient_id = ? AND status = 'pending'");
    $stmt->execute([$patient_id]);
    $pending_bills = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_reports WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $report_count = $stmt->fetchColumn();


    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
    $stmt->execute([$patient_id]);
    $total_visits = $stmt->fetchColumn();


    $stmt = $pdo->prepare("
        SELECT a.*, d_user.full_name as doctor_name, d.specialization, d.department_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users d_user ON d.user_id = d_user.user_id
        WHERE a.patient_id = ? AND a.status IN ('pending', 'confirmed') AND a.appointment_date >= CURRENT_DATE
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5
    ");
    $stmt->execute([$patient_id]);
    $upcoming_appointments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Healthylife</title>
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
                <li><a href="patient-dashboard.php">Dashboard</a></li>
                <li><a href="patient-appointment.php">Book Appointment</a></li>
                <li><a href="patient-medical-report.php">Medical Reports</a></li>
                <li><a href="patient-billing.php">Billing</a></li>
                <li><a href="patient-feedback.php">Feedback</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Welcome, <?php echo htmlspecialchars($full_name); ?></h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Upcoming Appointments</h3>
                <div class="stat-value"><?php echo $upcoming_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Bills</h3>
                <div class="stat-value">LKR <?php echo number_format($pending_bills, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Medical Reports</h3>
                <div class="stat-value"><?php echo $report_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Visits</h3>
                <div class="stat-value"><?php echo $total_visits; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Upcoming Appointments</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($upcoming_appointments) > 0): ?>
                            <?php foreach ($upcoming_appointments as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                    <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No upcoming appointments.</td></tr>
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
                <a href="patient-appointment.php" class="btn btn-primary">Book New Appointment</a>
                <a href="patient-medical-report.php" class="btn btn-secondary">View Medical Reports</a>
                <a href="patient-billing.php" class="btn btn-outline">Pay Bills</a>
                <a href="patient-feedback.php" class="btn btn-outline">Submit Feedback</a>
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

