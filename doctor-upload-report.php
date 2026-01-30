<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$message = '';
$error = '';


$stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();
$doctor_id = $doctor['doctor_id'] ?? null;


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_report') {
        $patient_id = $_POST['patientId'] ?? '';
        $report_type = $_POST['reportType'] ?? '';
        $test_date = $_POST['testDate'] ?? '';
        $findings = $_POST['findings'] ?? '';
        $doctorWorkNotes = $_POST['doctorNotes'] ?? '';

        $file_path = "";

        if (isset($_FILES['reportFile']) && $_FILES['reportFile']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['reportFile']['tmp_name'];
            $file_name = $_FILES['reportFile']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));


            $new_file_name = "report_" . time() . "_" . mt_rand(1000, 9999) . "." . $file_ext;
            $upload_dir = 'reports/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $file_path = $dest_path;
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Please upload a valid file.";
        }

        if (empty($error) && $doctor_id && $patient_id && $report_type && $test_date && $file_path) {
            try {
                $stmt = $pdo->prepare("INSERT INTO medical_reports (patient_id, doctor_id, report_type, test_date, file_path, findings, doctor_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'uploaded')");
                $stmt->execute([$patient_id, $doctor_id, $report_type, $test_date, $file_path, $findings, $doctorWorkNotes]);
                $message = "Medical report uploaded successfully.";


                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity_type, description) VALUES (?, 'Report', ?)");
                $stmt->execute([$user_id, "Uploaded $report_type report for patient ID $patient_id"]);

            } catch (Exception $e) {
                $error = "Error uploading report: " . $e->getMessage();
            }
        } elseif (empty($error)) {
            $error = "Please fill all required fields and ensure the file is uploaded.";
        }
    }


$pre_patient_id = '';
if (isset($_GET['appointment_id'])) {
    $apt_id = $_GET['appointment_id'];
    $stmt = $pdo->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$apt_id]);
    $pre_patient_id = $stmt->fetchColumn();
}


try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name as patient_name
        FROM medical_reports r
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE r.doctor_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$doctor_id]);
    $recent_reports = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Medical Report - Healthylife</title>
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

    <div class="container" style="max-width: 800px;">
        <h1 style="margin-bottom: 2rem;">Medical Report Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Upload New Test Result</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_report">
                <div class="form-group">
                    <label for="patientId">Patient ID (Numerical)</label>
                    <input type="number" id="patientId" name="patientId" value="<?php echo htmlspecialchars($pre_patient_id); ?>" placeholder="Enter Patient ID" required>
                </div>
                <div class="form-group">
                    <label for="reportType">Report Type</label>
                    <select id="reportType" name="reportType" required>
                        <option value="">Select Report Type</option>
                        <option value="blood">Blood Test</option>
                        <option value="xray">X-Ray</option>
                        <option value="ct">CT Scan</option>
                        <option value="mri">MRI</option>
                        <option value="ecg">ECG</option>
                        <option value="ultrasound">Ultrasound</option>
                        <option value="biopsy">Biopsy</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="testDate">Test Date</label>
                    <input type="date" id="testDate" name="testDate" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="reportFile">Upload Report File (PDF/Image)</label>
                    <input type="file" id="reportFile" name="reportFile" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="form-group">
                    <label for="findings">Findings / Observations</label>
                    <textarea id="findings" name="findings" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="doctorNotes">Doctor's Recommendations</label>
                    <textarea id="doctorNotes" name="doctorNotes" rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Upload & Save Report</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recently Uploaded Reports</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_reports) > 0): ?>
                            <?php foreach ($recent_reports as $rpt): ?>
                                <tr>
                                    <td>RPT-<?php echo $rpt['report_id']; ?></td>
                                    <td><?php echo htmlspecialchars($rpt['patient_name']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($rpt['report_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($rpt['test_date']); ?></td>
                                    <td>
                                        <span style="color: var(--secondary-color); font-weight: bold;"><?php echo ucfirst($rpt['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">No reports uploaded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

