<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$message = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update' || $action === 'cancel' || $action === 'confirm') {
        $aptId = $_POST['appointmentId'] ?? '';

        if ($aptId) {
            try {
                if ($action === 'update') {
                    $newDate = $_POST['editDate'];
                    $newTime = $_POST['editTime'];
                    $newStatus = $_POST['editStatus'];

                    $stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = ? WHERE appointment_id = ?");
                    $stmt->execute([$newDate, $newTime, $newStatus, $aptId]);
                    $message = "Appointment updated successfully.";
                } elseif ($action === 'cancel') {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
                    $stmt->execute([$aptId]);
                    $message = "Appointment cancelled.";
                } elseif ($action === 'confirm') {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ?");
                    $stmt->execute([$aptId]);
                    $message = "Appointment confirmed.";
                }
            } catch (Exception $e) {
                $error = "Error updating appointment: " . $e->getMessage();
            }
        }
    }
}


$dateFilter = $_GET['date'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$sql = "SELECT a.*,
               p_user.full_name AS patient_name,
               d_user.full_name AS doctor_name,
               d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users p_user ON p.user_id = p_user.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users d_user ON d.user_id = d_user.user_id
        WHERE 1=1";

$params = [];

if ($dateFilter) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}
if ($statusFilter) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
if ($searchQuery) {
    $sql .= " AND (p_user.full_name LIKE ? OR d_user.full_name LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
    <script>
        function editAppointment(id, date, time, status) {
            document.getElementById('appointmentId').value = id;
            document.getElementById('editDate').value = date;


            let formattedTime = time;
            if (time.length > 5) {
                formattedTime = time.substring(0, 5);
            }
            document.getElementById('editTime').value = formattedTime;
            document.getElementById('editStatus').value = status;

            document.getElementById('editSection').scrollIntoView({behavior: 'smooth'});
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="receptionist-dashboard.php">Dashboard</a></li>
                <li><a href="receptionist-appointments.php">Appointments</a></li>
                <li><a href="receptionist-billing.php">Billing</a></li>
                <li><a href="receptionist-inquiries.php">Inquiries</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <a href="index.html" class="logo"><img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Appointment Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>All Appointments</h2>
            </div>
            <form method="GET" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                <select name="status" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">All Status</option>
                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search patient or doctor..." style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Dept</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $row): ?>
                                <tr>
                                    <td>APT-<?php echo $row['appointment_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
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
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                                onclick="editAppointment('<?php echo $row['appointment_id']; ?>', '<?php echo $row['appointment_date']; ?>', '<?php echo $row['appointment_time']; ?>', '<?php echo $row['status']; ?>')">
                                                Edit
                                            </button>

                                            <?php if ($st === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <input type="hidden" name="appointmentId" value="<?php echo $row['appointment_id']; ?>">
                                                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Confirm</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($st !== 'cancelled'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="appointmentId" value="<?php echo $row['appointment_id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="editSection">
            <div class="card-header">
                <h2>Edit Appointment Details</h2>
            </div>
            <form method="POST" action="receptionist-appointments.php">
                <input type="hidden" name="action" value="update">
                <div class="form-group">
                    <label for="appointmentId">Appointment ID</label>
                    <input type="text" id="appointmentId" name="appointmentId" readonly required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="editDate">Date</label>
                        <input type="date" id="editDate" name="editDate" required>
                    </div>
                    <div class="form-group">
                        <label for="editTime">Time</label>
                        <input type="time" id="editTime" name="editTime" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="editStatus" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Appointment</button>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
