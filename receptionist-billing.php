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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_bill') {
    $patient_id = $_POST['patient_id'] ?? '';
    $appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
    $bill_date = $_POST['bill_date'] ?: date('Y-m-d');
    $due_date = $_POST['due_date'] ?: null;
    $total_amount = $_POST['total_amount'] ?? 0;
    $paid_amount = $_POST['paid_amount'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $notes = $_POST['notes'] ?? '';


    $external_code = 'B' . time() . mt_rand(10, 99);

    if ($patient_id && $total_amount >= 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO bills (external_code, patient_id, appointment_id, bill_date, due_date, total_amount, paid_amount, status, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$external_code, $patient_id, $appointment_id, $bill_date, $due_date, $total_amount, $paid_amount, $status, $payment_method, $notes]);
            $message = "Bill created successfully! (Code: $external_code)";


            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity_type, description) VALUES (?, 'Billing', ?)");
            $stmt->execute([$user_id, "Created bill for patient ID $patient_id, amount: LKR $total_amount"]);

        } catch (Exception $e) {
            $error = "Error creating bill: " . $e->getMessage();
        }
    } else {
        $error = "Please fill all required fields.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bill') {
    $bill_id = $_POST['bill_id'] ?? '';
    $paid_amount = $_POST['paid_amount'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $payment_method = $_POST['payment_method'] ?? 'cash';

    if ($bill_id) {
        try {
            $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, status = ?, payment_method = ? WHERE bill_id = ?");
            $stmt->execute([$paid_amount, $status, $payment_method, $bill_id]);
            $message = "Bill updated successfully.";
        } catch (Exception $e) {
            $error = "Error updating bill: " . $e->getMessage();
        }
    }
}


$stmt = $pdo->query("SELECT p.patient_id, u.full_name FROM patients p JOIN users u ON p.user_id = u.user_id WHERE u.status = 'active' ORDER BY u.full_name ASC");
$patients = $stmt->fetchAll();


$stmt = $pdo->query("
    SELECT b.*, u.full_name as patient_name
    FROM bills b
    JOIN patients p ON b.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    ORDER BY b.bill_date DESC
    LIMIT 20
");
$recent_bills = $stmt->fetchAll();

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
            <a href="index.html" class="logo">
                <img src="logo.png" alt="Healthylife" style="height: 40px; vertical-align: middle; margin-right: 8px;">Healthylife
            </a>
            <button class="menu-toggle" id="mobile-menu-toggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links" id="nav-links">
                <li><a href="receptionist-dashboard.php">Dashboard</a></li>
                <li><a href="receptionist-appointments.php">Appointments</a></li>
                <li><a href="receptionist-billing.php">Billing</a></li>
                <li><a href="receptionist-inquiries.php">Inquiries</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
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

        <div class="card">
            <div class="card-header">
                <h2>Create New Bill</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_bill">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="patient_id">Select Patient</label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?> (ID: <?php echo $p['patient_id']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="total_amount">Total Amount (LKR)</label>
                        <input type="number" step="0.01" id="total_amount" name="total_amount" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="bill_date">Bill Date</label>
                        <input type="date" id="bill_date" name="bill_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date">
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Invoice</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recent Bills</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_bills) > 0): ?>
                            <?php foreach ($recent_bills as $bill): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['external_code']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars($bill['bill_date']); ?></td>
                                    <td>LKR <?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td>LKR <?php echo number_format($bill['paid_amount'], 2); ?></td>
                                    <td>
                                        <span style="color: <?php
                                            echo $bill['status'] === 'paid' ? 'var(--secondary-color)' :
                                                ($bill['status'] === 'pending' ? 'var(--warning-color)' : 'var(--danger-color)');
                                        ?>; font-weight: bold;">
                                            <?php echo ucfirst($bill['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                                onclick="openUpdateModal('<?php echo $bill['bill_id']; ?>', '<?php echo $bill['paid_amount']; ?>', '<?php echo $bill['status']; ?>', '<?php echo $bill['payment_method']; ?>')">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No bills found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Modal (Simple Scroll-to-form for this demo) -->
    <div class="container" id="update-form-container" style="display: none; margin-top: 2rem;">
        <div class="card">
            <div class="card-header">
                <h2>Update Payment</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_bill">
                <input type="hidden" name="bill_id" id="update_bill_id">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="update_paid_amount">Paid Amount (LKR)</label>
                        <input type="number" step="0.01" id="update_paid_amount" name="paid_amount" required>
                    </div>
                    <div class="form-group">
                        <label for="update_status">Status</label>
                        <select id="update_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="update_payment_method">Payment Method</label>
                    <select id="update_payment_method" name="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                        <option value="insurance">Insurance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('update-form-container').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal(id, paid, status, method) {
            document.getElementById('update_bill_id').value = id;
            document.getElementById('update_paid_amount').value = paid;
            document.getElementById('update_status').value = status;
            document.getElementById('update_payment_method').value = method;
            document.getElementById('update-form-container').style.display = 'block';
            document.getElementById('update-form-container').scrollIntoView({behavior: 'smooth'});
        }
    </script>

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

