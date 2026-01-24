<?php
require 'config.php';

$email = 'receptionist@healthylife.com';
$password = 'receptionist123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'receptionist', status = 'active' WHERE email = ?");
$stmt->execute([$hash, $email]);

if ($stmt->rowCount() > 0) {
    echo "Password and role updated for $email.\n";
} else {
    echo "User $email not found or already correct.\n";


    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        echo "Creating user...\n";
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES ('Jane Smith', ?, ?, 'receptionist', 'active')");
        $stmt->execute([$email, $hash]);
        echo "User created.\n";
    }
}
?>
