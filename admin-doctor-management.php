<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$doc_success = '';
$doc_error   = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType']) && $_POST['formType'] === 'doctor-form') {
    $doctorId   = $_POST['doctorId'] ?? '';
    $name       = trim($_POST['doctorName'] ?? '');
    $email      = trim($_POST['doctorEmail'] ?? '');
    $phone      = trim($_POST['doctorPhone'] ?? '');
    $spec       = trim($_POST['doctorSpecialization'] ?? '');
    $dept       = trim($_POST['doctorDepartment'] ?? '');
    $exp        = (int)($_POST['doctorExperience'] ?? 0);
    $schedule   = trim($_POST['doctorSchedule'] ?? '');
    $avail      = $_POST['doctorAvailability'] ?? 'available';

    if ($name && $email && $phone && $spec && $dept && $schedule) {
        try {
            if ($doctorId === '') {

                $password = bin2hex(random_bytes(4));
                $hash     = password_hash($password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();

                $stmtU = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash, password_plain, role)
                     VALUES (?, ?, ?, ?, 'doctor')"
                );
                $stmtU->execute([$name, $email, $hash, $password]);
                $user_id = $pdo->lastInsertId();

                $stmtD = $pdo->prepare(
                    "INSERT INTO doctors (user_id, email, phone, specialization,
                                          department_name, experience_years,
                                          schedule_text, availability)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmtD->execute([
                    $user_id, $email, $phone, $spec,
                    $dept, $exp, $schedule, $avail
                ]);

                $pdo->commit();
                $doc_success = 'Doctor added successfully (temporary password generated).';
            } else {

                $stmt = $pdo->prepare(
                    "UPDATE doctors
                     SET email = ?, phone = ?, specialization = ?,
                         department_name = ?, experience_years = ?,
                         schedule_text = ?, availability = ?
                     WHERE doctor_id = ?"
                );
                $stmt->execute([$email, $phone, $spec, $dept, $exp, $schedule, $avail, $doctorId]);
                $doc_success = 'Doctor updated successfully.';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $doc_error = 'Error saving doctor: ' . $e->getMessage();
        }
    } else {
        $doc_error = 'Please fill all required doctor fields.';
    }
}


if (isset($_GET['deleteDoctor'])) {
    $id = (int)$_GET['deleteDoctor'];
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE doctor_id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?")->execute([$id]);
        if ($doc) {
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$doc['user_id']]);
        }
        $pdo->commit();
        $doc_success = 'Doctor deleted.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $doc_error = 'Error deleting doctor: ' . $e->getMessage();
    }
}

$stmt = $pdo->query(
    "SELECT doctor_id, email, phone, specialization,
            department_name, experience_years, schedule_text, availability
     FROM doctors
     ORDER BY doctor_id DESC"
);
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li><a href="admin-doctor-management.php">Doctors</a></li>
                <li><a href="admin-staff-directory.php">Staff</a></li>
                <li><a href="admin-billing-management.php">Billing</a></li>
                <li><a href="admin-patient-records.php">Patients</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <a href="index.html" class="logo"><img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Doctor Management</h1>

        <div class="card">
            <div class="card-header">
                <h2>Add New Doctor</h2>
            </div>

            <?php if (!empty($doc_error)): ?>
                <div class="alert alert-warning" style="margin: 1rem 1.5rem 0;">
                    <?php echo htmlspecialchars($doc_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($doc_success)): ?>
                <div class="alert alert-success" style="margin: 1rem 1.5rem 0;">
                    <?php echo htmlspecialchars($doc_success); ?>
                </div>
            <?php endif; ?>

            <div style="padding: 1.5rem;">
            <form method="POST" action="admin-doctor-management.php">
                <input type="hidden" name="formType" value="doctor-form">
                <input type="hidden" name="doctorId" id="doctorId">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="doctorName">Full Name</label>
                        <input type="text" id="doctorName" name="doctorName" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorEmail">Email</label>
                        <input type="email" id="doctorEmail" name="doctorEmail" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="doctorPhone">Phone</label>
                        <input type="tel" id="doctorPhone" name="doctorPhone" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorSpecialization">Specialization</label>
                        <select id="doctorSpecialization" name="doctorSpecialization" required>
                            <option value="">Select Specialization</option>
                            <option value="cardiology">Cardiology</option>
                            <option value="neurology">Neurology</option>
                            <option value="orthopedics">Orthopedics</option>
                            <option value="dermatology">Dermatology</option>
                            <option value="pediatrics">Pediatrics</option>
                            <option value="general">General Medicine</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="doctorDepartment">Department</label>
                        <input type="text" id="doctorDepartment" name="doctorDepartment" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorExperience">Years of Experience</label>
                        <input type="number" id="doctorExperience" name="doctorExperience" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="doctorSchedule">Schedule (e.g., Mon-Fri 9AM-5PM)</label>
                    <input type="text" id="doctorSchedule" name="doctorSchedule" required>
                </div>
                <div class="form-group">
                    <label for="doctorAvailability">Availability</label>
                    <select id="doctorAvailability" name="doctorAvailability" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="on-leave">On Leave</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Doctor</button>
            </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Doctors</h2>
            </div>
            <div style="margin-bottom: 1rem;">
                <input type="text" placeholder="Search doctors..." style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; width: 300px;">
                <button class="btn btn-primary" style="margin-left: 0.5rem;">Search</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Doctor ID</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Department</th>
                            <th>Schedule</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $row): ?>
                                <tr>
                                    <td>DOC-<?php echo str_pad($row['doctor_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['schedule_text']); ?></td>
                                    <td>
                                        <?php
                                        $status_color = '';
                                        switch($row['availability']) {
                                            case 'available': $status_color = 'var(--secondary-color)'; break;
                                            case 'on-leave': $status_color = 'var(--warning-color)'; break;
                                            case 'unavailable': $status_color = 'var(--danger-color)'; break;
                                        }
                                        ?>
                                        <span style="color: <?php echo $status_color; ?>;"><?php echo ucfirst(str_replace('-', ' ', $row['availability'])); ?></span>
                                    </td>
                                    <td>
                                        <a href="admin-doctor-management.php?editDoctor=<?php echo $row['doctor_id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; margin-right: 0.5rem;">Edit</a>
                                        <a href="admin-doctor-management.php?deleteDoctor=<?php echo $row['doctor_id']; ?>" class="btn btn-danger" style="padding: 0.5rem 1rem;" onclick="return confirm('Are you sure you want to delete this doctor?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-light);">No doctors found</td>
                            </tr>
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
