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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $billId = $_POST['billSelect'] ?? '';

    if ($billId) {
        try {

            $stmt = $pdo->prepare("UPDATE bills SET status = 'paid', paid_amount = total_amount WHERE bill_id = ? AND patient_id = ?");
            $stmt->execute([$billId, $patient_id]);
            $message = "Payment successful!";
        } catch (Exception $e) {
            $error = "Payment failed: " . $e->getMessage();
        }
    } else {
        $error = "Please select a bill to pay.";
    }
}


$totalOutstanding = 0;
$paidThisMonth = 0;
$pendingCount = 0;

if ($patient_id) {

    $stmt = $pdo->prepare("SELECT SUM(total_amount - paid_amount) FROM bills WHERE patient_id = ? AND status = 'pending'");
    $stmt->execute([$patient_id]);
    $totalOutstanding = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->prepare("SELECT SUM(paid_amount) FROM bills WHERE patient_id = ? AND status = 'paid' AND MONTH(bill_date) = MONTH(CURRENT_DATE) AND YEAR(bill_date) = YEAR(CURRENT_DATE)");
    $stmt->execute([$patient_id]);
    $paidThisMonth = $stmt->fetchColumn() ?: 0;


    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE patient_id = ? AND status = 'pending'");
    $stmt->execute([$patient_id]);
    $pendingCount = $stmt->fetchColumn();
}


$bills = [];
$unpaidBills = [];
if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
    $stmt->execute([$patient_id]);
    $bills = $stmt->fetchAll();

    foreach ($bills as $b) {
        if ($b['status'] === 'pending') {
            $unpaidBills[] = $b;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Payment - Healthylife</title>
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

    <div class="container">
        <h1 style="margin-bottom: 2rem;">Billing & Payment</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Outstanding</h3>
                <div class="stat-value">LKR <?php echo number_format($totalOutstanding, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Paid This Month</h3>
                <div class="stat-value">LKR <?php echo number_format($paidThisMonth, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Bills</h3>
                <div class="stat-value"><?php echo $pendingCount; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Billing History</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bills) > 0): ?>
                            <?php foreach ($bills as $row): ?>
                                <tr>
                                    <td>BILL-<?php echo $row['bill_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['bill_date']); ?></td>
                                    <td>Bill #<?php echo $row['bill_id']; ?></td>
                                    <td>LKR <?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($row['status']);
                                            $color = ($st === 'paid') ? 'var(--secondary-color)' : 'var(--warning-color)';
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo ucfirst($st); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($st === 'pending'): ?>
                                            <button onclick="selectBill('<?php echo $row['bill_id']; ?>', '<?php echo $row['total_amount']; ?>')" class="btn btn-primary" style="padding: 0.5rem 1rem;">Pay Now</button>
                                        <?php else: ?>
                                            <button class="btn btn-outline" disabled style="padding: 0.5rem 1rem;">Paid</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No bills found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="paymentSection">
            <div class="card-header">
                <h2>Make Payment</h2>
            </div>
            <form method="POST" action="patient-billing.php">
                <input type="hidden" name="action" value="pay">
                <div class="form-group">
                    <label for="billSelect">Select Bill to Pay</label>
                    <select id="billSelect" name="billSelect" required>
                        <option value="">Select a bill</option>
                        <?php foreach ($unpaidBills as $b): ?>
                            <option value="<?php echo $b['bill_id']; ?>">
                                BILL-<?php echo $b['bill_id']; ?> - LKR <?php echo number_format($b['total_amount'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="paymentMethod">Payment Method</label>
                    <select id="paymentMethod" name="paymentMethod" required>
                        <option value="">Select Payment Method</option>
                        <option value="credit">Credit Card</option>
                        <option value="debit">Debit Card</option>
                        <option value="netbanking">Net Banking</option>
                        <option value="upi">UPI</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cardNumber">Card Number</label>
                    <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Process Payment</button>
            </form>
        </div>
    </div>

    <script>
        function selectBill(id, amount) {
            const select = document.getElementById('billSelect');
            select.value = id;
            document.getElementById('paymentSection').scrollIntoView({behavior: 'smooth'});
        }
    </script>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
