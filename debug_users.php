<?php
require 'config.php';

echo "<h2>Users Table Content</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();

    echo "<table border='1'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>Table Schema</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
