<?php
require 'config.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) AFTER password_hash");




    echo "Successfully added 'password_plain' column to 'users' table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
