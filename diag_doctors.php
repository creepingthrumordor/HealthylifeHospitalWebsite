<?php
require 'config.php';
try {
    echo "--- User Roles ---\n";
    $stmt = $pdo->query("SELECT user_id, email, role, status FROM users");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['user_id']} | Email: {$row['email']} | Role: '{$row['role']}' | Status: {$row['status']}\n";
    }

    echo "\n--- Doctors Table ---\n";
    $stmt = $pdo->query("SELECT doctor_id, user_id, email FROM doctors");
    while ($row = $stmt->fetch()) {
        echo "DocID: {$row['doctor_id']} | UserID: {$row['user_id']} | Email: {$row['email']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
