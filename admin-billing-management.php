<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$message = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $billId = $_POST['billId'] ?? '';
    $status = $_POST['paymentStatus'] ?? '';
    $paymentDate = $_POST['paymentDate'] ?: date('Y-m-d');

    if ($billId && $status) {
        try {
            $stmt = $pdo->prepare("UPDATE bills SET status = ?, payment_date = ? WHERE bill_id = ?");
            $stmt->execute([$status, $status === 'paid' ? $paymentDate : null, $billId]);
            $message = "Bill status updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating bill: " . $e->getMessage();
        }
    }
}


try {

    $stmt = $pdo->query("SELECT SUM(paid_amount) FROM bills WHERE MONTH(bill_date) = MONTH(CURRENT_DATE) AND YEAR(bill_date) = YEAR(CURRENT_DATE)");
    $total_revenue = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->query("SELECT SUM(total_amount - paid_amount) FROM bills WHERE status = 'pending'");
    $pending_payments = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->query("SELECT SUM(paid_amount) FROM bills WHERE status = 'paid' AND MONTH(payment_date) = MONTH(CURRENT_DATE) AND YEAR(payment_date) = YEAR(CURRENT_DATE)");
    $paid_this_month = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->query("SELECT COUNT(*) FROM bills");
    $total_invoices = $stmt->fetchColumn();


    $stmt = $pdo->query("
        SELECT b.*, u.full_name as patient_name
        FROM bills b
        JOIN patients p ON b.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        ORDER BY b.bill_date DESC
    ");
    $all_bills = $stmt->fetchAll();

} catch (Exception $e) {
    $total_revenue = 0;
    $pending_payments = 0;
    $paid_this_month = 0;
    $total_invoices = 0;
    $all_bills = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Management - Healthylife</title>
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
        <h1 style="margin-bottom: 2rem;">Billing Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Revenue (This Month)</h3>
                <div class="stat-value">LKR <?php echo number_format($total_revenue, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Payments</h3>
                <div class="stat-value">LKR <?php echo number_format($pending_payments, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Paid This Month</h3>
                <div class="stat-value">LKR <?php echo number_format($paid_this_month, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Invoices</h3>
                <div class="stat-value"><?php echo $total_invoices; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>All Bills & Payments</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Date</th>
                            <th>Patient Name</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_bills) > 0): ?>
                            <?php foreach ($all_bills as $bill): ?>
                                <tr>
                                    <td>BILL-<?php echo $bill['bill_id']; ?></td>
                                    <td><?php echo htmlspecialchars($bill['bill_date']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                                    <td>LKR <?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td>LKR <?php echo number_format($bill['paid_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($bill['status']);
                                            $color = 'var(--warning-color)';
                                            if ($st === 'paid') $color = 'var(--secondary-color)';
                                            elseif ($st === 'overdue') $color = 'var(--danger-color)';
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo ucfirst($st); ?></span>
                                    </td>
                                    <td><?php echo $bill['payment_date'] ?: '-'; ?></td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 0.5rem 1rem;"
                                                onclick="document.getElementById('billId').value='<?php echo $bill['bill_id']; ?>'; document.getElementById('paymentStatus').value='<?php echo $bill['status']; ?>'; document.getElementById('updateSection').scrollIntoView({behavior:'smooth'});">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center;">No billing records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="updateSection">
            <div class="card-header">
                <h2>Update Payment Status</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label for="billId">Bill ID (Numerical)</label>
                    <input type="text" id="billId" name="billId" placeholder="Enter Bill ID" required>
                </div>
                <div class="form-group">
                    <label for="paymentStatus">Payment Status</label>
                    <select id="paymentStatus" name="paymentStatus" required>
                        <option value="">Select Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="paymentDate">Payment Date</label>
                    <input type="date" id="paymentDate" name="paymentDate">
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
