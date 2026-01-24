<?php
require 'config.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        $stmt2 = $pdo->query("DESCRIBE $table");
        $columns = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
