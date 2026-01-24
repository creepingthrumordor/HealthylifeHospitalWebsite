<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$staff_success = '';
$staff_error   = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType']) && $_POST['formType'] === 'staff-form') {
    $staffId    = $_POST['staffId'] ?? '';
    $name       = trim($_POST['staffName'] ?? '');
    $email      = trim($_POST['staffEmail'] ?? '');
    $phone      = trim($_POST['staffPhone'] ?? '');
    $roleType   = $_POST['staffRole'] ?? '';
    $dept       = trim($_POST['staffDepartment'] ?? '');
    $joinDate   = $_POST['staffJoinDate'] ?? null;

    if ($name && $email && $phone && $roleType && $dept && $joinDate) {
        try {
            if ($staffId === '') {
                $password = bin2hex(random_bytes(4));
                $hash     = password_hash($password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();

                $stmtU = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash, password_plain, role)
                     VALUES (?, ?, ?, ?, 'receptionist')"
                );
                $stmtU->execute([$name, $email, $hash, $password]);
                $user_id = $pdo->lastInsertId();

                $stmtS = $pdo->prepare(
                    "INSERT INTO staff (user_id, role_type, email, phone, department_name, position, status)
                     VALUES (?, ?, ?, ?, ?, ?, 'active')"
                );
                $stmtS->execute([$user_id, $roleType, $email, $phone, $dept, $roleType]);

                $pdo->commit();
                $staff_success = 'Staff member added.';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE staff
                     SET role_type = ?, email = ?, phone = ?, department_name = ?, position = ?
                     WHERE staff_id = ?"
                );
                $stmt->execute([$roleType, $email, $phone, $dept, $roleType, $staffId]);
                $staff_success = 'Staff member updated.';
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $staff_error = 'Error saving staff: ' . $e->getMessage();
        }
    } else {
        $staff_error = 'Please fill all required staff fields.';
    }
}


if (isset($_GET['deleteStaff'])) {
    $id = (int)$_GET['deleteStaff'];
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE staff_id = ?");
        $stmt->execute([$id]);
        $st = $stmt->fetch();

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM staff WHERE staff_id = ?")->execute([$id]);
        if ($st) {
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$st['user_id']]);
        }
        $pdo->commit();
        $staff_success = 'Staff member removed.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $staff_error = 'Error deleting staff: ' . $e->getMessage();
    }
}

$stmt = $pdo->query(
    "SELECT s.staff_id, s.role_type, s.department_name, s.email, s.phone, u.full_name
     FROM staff s
     JOIN users u ON s.user_id = u.user_id
     ORDER BY s.staff_id DESC"
);
$staffRows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Directory - Healthylife</title>
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
        <h1 style="margin-bottom: 2rem;">Staff Directory</h1>

        <div class="card">
            <div class="card-header">
                <h2>Add New Staff Member</h2>
            </div>

            <?php if (!empty($staff_error)): ?>
                <div class="alert alert-warning" style="margin: 1rem 1.5rem 0;">
                    <?php echo htmlspecialchars($staff_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($staff_success)): ?>
                <div class="alert alert-success" style="margin: 1rem 1.5rem 0;">
                    <?php echo htmlspecialchars($staff_success); ?>
                </div>
            <?php endif; ?>

            <div style="padding: 1.5rem;">
            <form method="POST" action="admin-staff-directory.php">
                <input type="hidden" name="formType" value="staff-form">
                <input type="hidden" name="staffId" id="staffId">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="staffName">Full Name</label>
                        <input type="text" id="staffName" name="staffName" required>
                    </div>
                    <div class="form-group">
                        <label for="staffEmail">Email</label>
                        <input type="email" id="staffEmail" name="staffEmail" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="staffPhone">Phone</label>
                        <input type="tel" id="staffPhone" name="staffPhone" required>
                    </div>
                    <div class="form-group">
                        <label for="staffRole">Role</label>
                        <select id="staffRole" name="staffRole" required>
                            <option value="">Select Role</option>
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="lab-technician">Lab Technician</option>
                            <option value="pharmacist">Pharmacist</option>
                            <option value="support">Support Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="staffDepartment">Department</label>
                        <input type="text" id="staffDepartment" name="staffDepartment" required>
                    </div>
                    <div class="form-group">
                        <label for="staffJoinDate">Join Date</label>
                        <input type="date" id="staffJoinDate" name="staffJoinDate" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Staff Member</button>
            </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Staff Members</h2>
            </div>
            <div style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <select style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">All Roles</option>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="lab-technician">Lab Technician</option>
                    <option value="support">Support Staff</option>
                </select>
                <input type="text" placeholder="Search staff..." style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; flex: 1; min-width: 200px;">
                <button class="btn btn-primary">Search</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Join Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($staffRows)): ?>
                            <?php foreach ($staffRows as $row): ?>
                                <tr>
                                    <td>STF-<?php echo str_pad($row['staff_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $row['role_type']))); ?></td>
                                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td>N/A</td>
                                    <td>
                                        <a href="admin-staff-directory.php?editStaff=<?php echo $row['staff_id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; margin-right: 0.5rem;">Update</a>
                                        <a href="admin-staff-directory.php?deleteStaff=<?php echo $row['staff_id']; ?>" class="btn btn-danger" style="padding: 0.5rem 1rem;" onclick="return confirm('Are you sure?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">No staff found.</td>
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
