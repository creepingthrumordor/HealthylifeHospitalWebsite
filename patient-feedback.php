<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$message = '';
$error = '';


$stmt = $pdo->prepare("SELECT u.email, p.patient_id FROM users u LEFT JOIN patients p ON u.user_id = p.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();
$user_email = $user_info['email'] ?? '';
$patient_id = $user_info['patient_id'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? '';
    $msg = trim($_POST['message'] ?? '');

    if ($subject && $category && $msg) {
        try {

            $stmt = $pdo->prepare("INSERT INTO inquiries (patient_id, name, email, subject, category, message, status) VALUES (?, ?, ?, ?, ?, ?, 'new')");
            $stmt->execute([$patient_id, $full_name, $user_email, $subject, $category, $msg]);
            $message = "Feedback submitted successfully!";
        } catch (Exception $e) {
            $error = "Error submitting feedback: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}



$inquiries = [];
try {

    $stmt = $pdo->prepare("
        SELECT i.*, ir.response_msg as response
        FROM inquiries i
        LEFT JOIN (
            SELECT inquiry_id, response_msg
            FROM inquiry_responses
            WHERE response_id IN (
                SELECT MAX(response_id)
                FROM inquiry_responses
                GROUP BY inquiry_id
            )
        ) ir ON i.inquiry_id = ir.inquiry_id
        WHERE i.email = ?
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$user_email]);
    $inquiries = $stmt->fetchAll();
} catch (Exception $e) {

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Inquiry - Healthylife</title>
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
        <h1 style="margin-bottom: 2rem;">Feedback & Inquiry</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Submit Feedback or Inquiry</h2>
            </div>
            <form method="POST" action="patient-feedback.php">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter subject" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="feedback">Feedback</option>
                        <option value="complaint">Complaint</option>
                        <option value="inquiry">General Inquiry</option>
                        <option value="suggestion">Suggestion</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Please provide your feedback, inquiry, or question" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>My Previous Inquiries</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($inquiries) > 0): ?>
                            <?php foreach ($inquiries as $row): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject'] ?? 'No Subject'); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td>
                                        <?php
                                            $st = strtolower($row['status']);
                                            $color = ($st === 'resolved' || $st === 'responded') ? 'var(--secondary-color)' : 'var(--warning-color)';
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo ucfirst($st); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['response'])): ?>
                                             <button class="btn btn-outline" style="padding: 0.25rem 0.5rem;" onclick="alert('Response: <?php echo htmlspecialchars($row['response']); ?>')">View</button>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No previous inquiries found.</td></tr>
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
