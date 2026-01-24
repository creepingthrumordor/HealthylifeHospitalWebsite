<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';


$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();
$patient_id = $patient['patient_id'] ?? null;



$stmt = $pdo->query("
    SELECT d.doctor_id, u.full_name, d.specialization, d.department_name, d.department_id
    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.availability = 'available'
");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$patient_id) {
    $error = "Error: Patient record not found for your account. Please contact support.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$patient_id) {
        $error = "Cannot perform action: Patient record missing.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'book') {
        $doctorId = $_POST['doctor'] ?? '';
        $date = $_POST['appointmentDate'] ?? '';
        $time = $_POST['appointmentTime'] ?? '';
        $reason = trim($_POST['reason'] ?? '');


        $deptName = '';
        $deptId = null;
        foreach ($doctors as $doc) {
            if ($doc['doctor_id'] == $doctorId) {
                $deptName = $doc['department_name'] ?? $doc['specialization'];
                $deptId   = $doc['department_id'] ?? null;
                break;
            }
        }

        if ($doctorId && $date && $time) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, department_name, department_id, reason, created_by_user_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$patient_id, $doctorId, $date, $time, $deptName, $deptId, $reason, $user_id]);
                $message = "Appointment booked successfully!";
            } catch (Exception $e) {
                $error = "Error booking appointment: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        $aptId = $_POST['appointmentId'];
        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?");
            $stmt->execute([$aptId, $patient_id]);
            $message = "Appointment cancelled.";
        } catch (Exception $e) {
            $error = "Error cancelling appointment.";
        }
    }
}


$myAppointments = [];
if ($patient_id) {
    $stmt = $pdo->prepare("
        SELECT a.*, d_user.full_name as doctor_name, d.department_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users d_user ON d.user_id = d_user.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute([$patient_id]);
    $myAppointments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="patient-dashboard.php">Dashboard</a></li>
                <li><a href="patient-appointment.php">Book Appointment</a></li>
                <li><a href="patient-medical-report.php">Medical Reports</a></li>
                <li><a href="patient-billing.php">Billing</a></li>
                <li><a href="patient-feedback.php">Feedback</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <a href="index.html" class="logo"><img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife</a>
        </div>
    </nav>

    <div class="container" style="max-width: 900px;">
        <h1 style="margin-bottom: 2rem;">Appointments</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Book an Appointment</h2>
            </div>
            <form method="POST" action="patient-appointment.php">
                <input type="hidden" name="action" value="book">
                <!-- Removed separate Department select to simplify logic, as Doctor selection determines department -->
                <div class="form-group">
                    <label for="doctor">Select Doctor</label>
                    <select id="doctor" name="doctor" required>
                        <option value="">Select Doctor</option>
                        <?php if (empty($doctors)): ?>
                            <option value="" disabled>No doctors available</option>
                        <?php endif; ?>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['doctor_id']; ?>">
                                <?php echo htmlspecialchars($doc['full_name'] . " - " . ($doc['department_name'] ?? $doc['specialization'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="appointmentDate">Preferred Date</label>
                    <input type="date" id="appointmentDate" name="appointmentDate" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="appointmentTime">Preferred Time</label>
                    <select id="appointmentTime" name="appointmentTime" required>
                        <option value="">Select Time</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reason">Reason for Visit</label>
                    <textarea id="reason" name="reason" placeholder="Please describe your symptoms or reason for the appointment"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Book Appointment</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>My Appointments History</h2>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($myAppointments) > 0): ?>
                            <?php foreach ($myAppointments as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                    <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
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
                                    <td>
                                        <?php if ($st === 'pending' || $st === 'confirmed'): ?>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel?');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="appointmentId" value="<?php echo $row['appointment_id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
