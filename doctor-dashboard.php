
<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];


$stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['doctor_id'] ?? null;


try {
    if ($doctor_id) {

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = CURRENT_DATE");
        $stmt->execute([$doctor_id]);
        $today_appointments = $stmt->fetch()['count'];




        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_reports WHERE doctor_id = ? AND status = 'uploaded'");
        $stmt->execute([$doctor_id]);
        $pending_reports = $stmt->fetch()['count'];


        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        $total_patients = $stmt->fetch()['count'];


        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND MONTH(appointment_date) = MONTH(CURRENT_DATE) AND YEAR(appointment_date) = YEAR(CURRENT_DATE)");
        $stmt->execute([$doctor_id]);
        $this_month = $stmt->fetch()['count'];


        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name as patient_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN users u ON p.user_id = u.user_id
            WHERE a.doctor_id = ? AND a.appointment_date >= CURRENT_DATE
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        $stmt->execute([$doctor_id]);
        $todays_schedule = $stmt->fetchAll();
    } else {
        $today_appointments = 0;
        $pending_reports = 0;
        $total_patients = 0;
        $this_month = 0;
        $todays_schedule = [];
    }
} catch (Exception $e) {
    $today_appointments = 0;
    $pending_reports = 0;
    $total_patients = 0;
    $this_month = 0;
    $todays_schedule = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="doctor-dashboard.php">Dashboard</a></li>
                <li><a href="doctor-upload-report.php">Upload Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <a href="index.html" class="logo"><img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Welcome, Dr. <?php echo htmlspecialchars($full_name); ?></h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Reports</h3>
                <div class="stat-value"><?php echo $pending_reports; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-value"><?php echo $total_patients; ?></div>
            </div>
            <div class="stat-card">
                <h3>This Month</h3>
                <div class="stat-value"><?php echo $this_month; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
            <div class="card-header">
                <h2>Upcoming Appointments</h2>
            </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient Name</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($todays_schedule) > 0): ?>
                            <?php foreach ($todays_schedule as $appt): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?> <br>
                                        <small><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['reason'] ?? 'Consultation'); ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($appt['status']);
                                            $color = 'var(--primary-color)';
                                            if ($st === 'completed') $color = 'var(--secondary-color)';
                                            elseif ($st === 'cancelled') $color = 'var(--danger-color)';
                                            elseif ($st === 'confirmed') $color = 'var(--secondary-color)';
                                        ?>
                                        <span style="color: <?php echo $color; ?>;"><?php echo ucfirst($st); ?></span>
                                    </td>
                                    <td>
                                        <a href="doctor-upload-report.php?appointment_id=<?php echo $appt['appointment_id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">Upload Report</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No upcoming appointments found.</td></tr>
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
                <a href="doctor-upload-report.php" class="btn btn-primary">Upload Test Results</a>
                <button class="btn btn-secondary">View Patient History</button>
                <button class="btn btn-outline">Update Schedule</button>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
