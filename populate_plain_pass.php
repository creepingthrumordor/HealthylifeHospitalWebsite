<?php
require 'config.php';
try {

    $updates = [
        'sharaf@gmail.com' => 'admin123',
        'aswin@gmail.com' => 'doctor123',
        'receptionist@healthylife.com' => 'staff123',
        'afilm@gmail.com' => 'patient123'
    ];

    foreach ($updates as $email => $pass) {
        $stmt = $pdo->prepare("UPDATE users SET password_plain = ? WHERE email = ?");
        $stmt->execute([$pass, $email]);
    }

    echo "Updated plain passwords for testing accounts.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
