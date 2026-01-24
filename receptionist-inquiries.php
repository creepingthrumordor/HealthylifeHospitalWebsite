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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'respond') {
    $inquiryId = $_POST['inquiryId'] ?? '';
    $responseMsg = $_POST['response'] ?? '';
    $newStatus = $_POST['responseStatus'] ?? 'in-progress';

    if ($inquiryId && $responseMsg) {
        try {
            $pdo->beginTransaction();


            $stmt = $pdo->prepare("INSERT INTO inquiry_responses (inquiry_id, staff_id, response_msg) VALUES (?, ?, ?)");


            $stmtStaff = $pdo->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
            $stmtStaff->execute([$user_id]);
            $staff = $stmtStaff->fetch();
            $staffId = $staff['staff_id'] ?? null;

            if ($staffId) {
                $stmt->execute([$inquiryId, $staffId, $responseMsg]);


                $stmtUpdate = $pdo->prepare("UPDATE inquiries SET status = ?, assigned_staff_id = ? WHERE inquiry_id = ?");
                $stmtUpdate->execute([$newStatus, $staffId, $inquiryId]);

                $pdo->commit();
                $message = "Response sent successfully!";
            } else {
                throw new Exception("Staff record not found for current user.");
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving response: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}


$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$sql = "SELECT * FROM inquiries WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}
if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
if ($searchQuery) {
    $sql .= " AND (name LIKE ? OR message LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Management - Healthylife</title>
    <link rel="stylesheet" href="styles.css?v=3">
    <script>
        function openRespondModal(id, name, msg) {
            document.getElementById('inquiryId').value = id;
            document.getElementById('patientName').value = name;
            document.getElementById('originalMessage').value = msg;
            document.getElementById('respondSection').scrollIntoView({behavior: 'smooth'});
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
        <h1 style="margin-bottom: 2rem;">Inquiry Management</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Patient Inquiries</h2>
            </div>
            <form method="GET" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <select name="category" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">All Categories</option>
                    <option value="General" <?php echo $categoryFilter === 'General' ? 'selected' : ''; ?>>General</option>
                    <option value="Appointment" <?php echo $categoryFilter === 'Appointment' ? 'selected' : ''; ?>>Appointment</option>
                    <option value="Billing" <?php echo $categoryFilter === 'Billing' ? 'selected' : ''; ?>>Billing</option>
                    <option value="Feedback" <?php echo $categoryFilter === 'Feedback' ? 'selected' : ''; ?>>Feedback</option>
                </select>
                <select name="status" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">All Status</option>
                    <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="in-progress" <?php echo $statusFilter === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search..." style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; flex: 1; min-width: 200px;">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Inquiry ID</th>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($inquiries) > 0): ?>
                            <?php foreach ($inquiries as $row): ?>
                                <tr>
                                    <td>INQ-<?php echo $row['inquiry_id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['message'], 0, 50)) . '...'; ?></td>
                                    <td>
                                        <?php
                                            $statusColor = 'var(--text-dark)';
                                            if ($row['status'] === 'new') $statusColor = 'var(--danger-color)';
                                            elseif ($row['status'] === 'in-progress') $statusColor = 'var(--warning-color)';
                                            elseif ($row['status'] === 'resolved') $statusColor = 'var(--secondary-color)';
                                        ?>
                                        <span style="color: <?php echo $statusColor; ?>; font-weight: bold;"><?php echo ucfirst($row['status']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" style="padding: 0.5rem 1rem;"
                                            onclick="openRespondModal('<?php echo $row['inquiry_id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars(addslashes($row['message'])); ?>')">
                                            Respond
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No inquiries found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" id="respondSection">
            <div class="card-header">
                <h2>Respond to Inquiry</h2>
            </div>
            <form method="POST" action="receptionist-inquiries.php">
                <input type="hidden" name="action" value="respond">
                <div class="form-group">
                    <label for="inquiryId">Inquiry ID</label>
                    <input type="text" id="inquiryId" name="inquiryId" readonly required>
                </div>
                <div class="form-group">
                    <label for="patientName">Patient Name</label>
                    <input type="text" id="patientName" name="patientName" readonly disabled>
                </div>
                <div class="form-group">
                    <label for="originalMessage">Original Message</label>
                    <textarea id="originalMessage" name="originalMessage" readonly style="background: var(--bg-light);" disabled></textarea>
                </div>
                <div class="form-group">
                    <label for="response">Your Response</label>
                    <textarea id="response" name="response" placeholder="Enter your response..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="responseStatus">Set Status</label>
                    <select id="responseStatus" name="responseStatus" required>
                        <option value="in-progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Send Response</button>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2025 HealthyLife. All rights reserved.</p>
    </footer>
</body>
</html>
