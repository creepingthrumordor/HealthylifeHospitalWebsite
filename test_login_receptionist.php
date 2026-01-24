<?php

require 'config.php';

$email = 'receptionist@healthylife.com';
$password = 'receptionist123';

echo "Testing login for $email\n";

$stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found in DB.\n";
    exit;
}

echo "User found. Role: " . $user['role'] . "\n";
echo "Status: " . $user['status'] . "\n";

if (password_verify($password, $user['password_hash'])) {
    echo "Password verify: SUCCESS\n";
} else {
    echo "Password verify: FAILURE\n";
    echo "Hash in DB: " . $user['password_hash'] . "\n";
    echo "New hash of '$password': " . password_hash($password, PASSWORD_DEFAULT) . "\n";
}

if ($user['role'] === 'receptionist') {
    echo "Role check: SUCCESS\n";
} else {
    echo "Role check: FAILURE (Expected 'receptionist', got '{$user['role']}')\n";
}

if ($user['status'] === 'active') {
    echo "Status check: SUCCESS\n";
} else {
    echo "Status check: FAILURE\n";
}
?>
