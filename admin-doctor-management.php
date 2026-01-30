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

// Initialize edit variables
$e_id = '';
$e_name = '';
$e_email = '';
$e_phone = '';
$e_spec = '';
$e_dept = '';
$e_exp = '';
$e_sch = '';
$e_avail = 'available';

if (isset($_GET['editDoctor'])) {
    $id = (int)$_GET['editDoctor'];
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, u.full_name 
            FROM doctors d 
            JOIN users u ON d.user_id = u.user_id 
            WHERE d.doctor_id = ?
        ");
        $stmt->execute([$id]);
        $editDoc = $stmt->fetch();
        
        if ($editDoc) {
            $e_id = $editDoc['doctor_id'];
            $e_name = $editDoc['full_name'];
            $e_email = $editDoc['email'];
            $e_phone = $editDoc['phone'];
            $e_spec = $editDoc['specialization'];
            $e_dept = $editDoc['department_name'];
            $e_exp = $editDoc['experience_years'];
            $e_sch = $editDoc['schedule_text'];
            $e_avail = $editDoc['availability'];
        }
    } catch (Exception $e) {
        $doc_error = "Error fetching doctor data: " . $e->getMessage();
    }
}


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
    $password   = $_POST['doctorPassword'] ?? '';

    if ($name && $email && $phone && $spec && $dept && $schedule) {
        try {
            if ($doctorId === '') {
                if (empty($password)) {
                    throw new Exception("Password is required for new doctors.");
                }
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
                if (!empty($password)) {
                    // Update password if provided
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_plain = ? WHERE user_id = (SELECT user_id FROM doctors WHERE doctor_id = ?)");
                    $stmt->execute([$hash, $password, $doctorId]);
                    $doc_success = 'Doctor and password updated successfully.';
                }
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
        
        // Remove associated records first to avoid foreign key constraints
        $pdo->prepare("DELETE FROM appointments WHERE doctor_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM medical_reports WHERE doctor_id = ?")->execute([$id]);
        
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
        <h1 style="margin-bottom: 2rem;">Doctor Management</h1>

        <div class="card">
            <div class="card-header">
                <h2><?php echo $e_id ? 'Edit Doctor' : 'Add New Doctor'; ?></h2>
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
                <input type="hidden" name="doctorId" id="doctorId" value="<?php echo htmlspecialchars($e_id); ?>">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="doctorName">Full Name</label>
                        <input type="text" id="doctorName" name="doctorName" value="<?php echo htmlspecialchars($e_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorEmail">Email</label>
                        <input type="email" id="doctorEmail" name="doctorEmail" value="<?php echo htmlspecialchars($e_email); ?>" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="doctorPhone">Phone</label>
                        <input type="tel" id="doctorPhone" name="doctorPhone" value="<?php echo htmlspecialchars($e_phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorSpecialization">Specialization</label>
                        <select id="doctorSpecialization" name="doctorSpecialization" required>
                            <option value="">Select Specialization</option>
                            <option value="cardiology" <?php echo $e_spec === 'cardiology' ? 'selected' : ''; ?>>Cardiology</option>
                            <option value="neurology" <?php echo $e_spec === 'neurology' ? 'selected' : ''; ?>>Neurology</option>
                            <option value="orthopedics" <?php echo $e_spec === 'orthopedics' ? 'selected' : ''; ?>>Orthopedics</option>
                            <option value="dermatology" <?php echo $e_spec === 'dermatology' ? 'selected' : ''; ?>>Dermatology</option>
                            <option value="pediatrics" <?php echo $e_spec === 'pediatrics' ? 'selected' : ''; ?>>Pediatrics</option>
                            <option value="general" <?php echo $e_spec === 'general' ? 'selected' : ''; ?>>General Medicine</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="doctorDepartment">Department</label>
                        <input type="text" id="doctorDepartment" name="doctorDepartment" value="<?php echo htmlspecialchars($e_dept); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="doctorExperience">Years of Experience</label>
                        <input type="number" id="doctorExperience" name="doctorExperience" min="0" value="<?php echo htmlspecialchars($e_exp); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="doctorSchedule">Schedule (e.g., Mon-Fri 9AM-5PM)</label>
                    <input type="text" id="doctorSchedule" name="doctorSchedule" value="<?php echo htmlspecialchars($e_sch); ?>" required>
                </div>
                <div class="form-group">
                    <label for="doctorAvailability">Availability</label>
                    <select id="doctorAvailability" name="doctorAvailability" required>
                        <option value="available" <?php echo $e_avail === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo $e_avail === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        <option value="on-leave" <?php echo $e_avail === 'on-leave' ? 'selected' : ''; ?>>On Leave</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="doctorPassword">Password <?php echo $e_id ? '(Leave blank to keep current)' : '(Required)'; ?></label>
                    <input type="password" id="doctorPassword" name="doctorPassword" <?php echo $e_id ? '' : 'required'; ?>>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $e_id ? 'Update Doctor' : 'Add Doctor'; ?></button>
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

