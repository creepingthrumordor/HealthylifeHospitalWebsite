<?php

echo "Password hashes for test users:\n\n";

$passwords = [
    'admin123' => 'admin@healthylife.com',
    'doctor123' => 'doctor@healthylife.com',
    'receptionist123' => 'receptionist@healthylife.com',
    'patient123' => 'patient@healthylife.com'
];

foreach ($passwords as $password => $email) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n\n";
}
?>
