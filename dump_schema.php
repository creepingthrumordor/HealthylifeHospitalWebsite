<?php
require 'config.php';
$tables = ['appointments', 'activity_logs', 'medical_reports'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
}
?>
