<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
$patient_id = $patient['patient_id'] ?? null;

$reports = [];
if ($patient_id) {




    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name as doctor_name
        FROM medical_reports r
        LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        WHERE r.patient_id = ?
        ORDER BY r.test_date DESC
    ");
    $stmt->execute([$patient_id]);
    $reports = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports - Healthylife</title>
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
        <h1 style="margin-bottom: 2rem;">My Medical Reports</h1>

        <div class="card">
            <div class="card-header">
                <h2>Available Reports</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Test Type</th>
                            <th>Status/File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reports) > 0): ?>
                            <?php foreach ($reports as $row): ?>
                                <tr>
                                    <td>RPT-<?php echo $row['report_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['test_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctor_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($row['report_type'] ?? 'General')); ?></td>
                                    <td>Available</td>
                                    <td>
                                        <?php if (!empty($row['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="btn btn-primary" target="_blank" style="padding: 0.5rem 1rem;">View</a>
                                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download class="btn btn-outline" style="padding: 0.5rem 1rem; margin-left: 0.5rem;">Download</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">No file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No reports found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Download Reports</h2>
            </div>
            <p>You can download your medical reports in PDF format. All reports are securely stored and can be accessed anytime.</p>
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

