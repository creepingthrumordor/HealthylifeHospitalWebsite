<?php
require 'config.php';
try {
    $stmt = $pdo->query("SELECT * FROM bills LIMIT 5");
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bills, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
