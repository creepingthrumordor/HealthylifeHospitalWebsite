<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];


$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';


try {
    $sql = "SELECT p.*, u.full_name, u.email, u.status as account_status
            FROM patients p
            JOIN users u ON p.user_id = u.user_id
            WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR p.external_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY p.registration_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $patients = $stmt->fetchAll();


    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $total_patients = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE MONTH(registration_date) = MONTH(CURRENT_DATE) AND YEAR(registration_date) = YEAR(CURRENT_DATE)");
    $new_this_month = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'");
    $active_patients = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT AVG(DATEDIFF(CURRENT_DATE, date_of_birth)/365.25) FROM patients");
    $avg_age = round($stmt->fetchColumn());

} catch (Exception $e) {
    $patients = [];
    $total_patients = 0;
    $new_this_month = 0;
    $active_patients = 0;
    $avg_age = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records Management - Healthylife</title>
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
        <h1 style="margin-bottom: 2rem;">Patient Records Management</h1>

        <div class="card">
            <div class="card-header">
                <h2>All Registered Patients</h2>
            </div>
            <form method="GET" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, ID, or email..." style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; flex: 1; min-width: 300px;">
                <select name="status" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">All Patients</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Reg. Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $p): ?>
                                <tr>
                                    <td>PAT-<?php echo $p['patient_id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                                    <td><?php echo htmlspecialchars($p['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($p['date_of_birth']); ?></td>
                                    <td><?php echo htmlspecialchars($p['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($p['registration_date']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $p['status'] === 'active' ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>; font-weight: bold;">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">No patient records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Patient Statistics</h2>
            </div>
            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                </div>
                <div class="stat-card">
                    <h3>New This Month</h3>
                    <div class="stat-value"><?php echo $new_this_month; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Patients</h3>
                    <div class="stat-value"><?php echo $active_patients; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Age</h3>
                    <div class="stat-value"><?php echo $avg_age; ?></div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
