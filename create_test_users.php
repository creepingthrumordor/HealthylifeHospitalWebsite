<?php
require 'config.php';

try {

    $users = [
        [
            'name' => 'Admin User',
            'email' => 'admin@healthylife.com',
            'password' => 'admin123',
            'role' => 'admin'
        ],
        [
            'name' => 'Dr. Sarah Johnson',
            'email' => 'doctor@healthylife.com',
            'password' => 'doctor123',
            'role' => 'doctor'
        ],
        [
            'name' => 'Jane Smith',
            'email' => 'receptionist@healthylife.com',
            'password' => 'receptionist123',
            'role' => 'receptionist'
        ],
        [
            'name' => 'John Doe',
            'email' => 'patient@healthylife.com',
            'password' => 'patient123',
            'role' => 'patient'
        ]
    ];

    foreach ($users as $user) {

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);

        if (!$stmt->fetch()) {

            $hash = password_hash($user['password'], PASSWORD_DEFAULT);


            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role, status)
                 VALUES (?, ?, ?, ?, 'active')"
            );
            $stmt->execute([$user['name'], $user['email'], $hash, $user['role']]);

            echo "Created user: {$user['email']} (Password: {$user['password']})\n";


            if ($user['role'] === 'patient') {
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare(
                    "INSERT INTO patients (user_id, phone, date_of_birth, gender, address)
                     VALUES (?, '555-0123', '1990-01-15', 'Male', '123 Main St, City, State')"
                );
                $stmt->execute([$user_id]);
                echo "Added patient details for {$user['email']}\n";
            }
        } else {
            echo "User already exists: {$user['email']}\n";
        }
    }

    echo "\nTest users created successfully!\n";
    echo "\nLogin credentials:\n";
    echo "Admin: admin@healthylife.com / admin123\n";
    echo "Doctor: doctor@healthylife.com / doctor123\n";
    echo "Receptionist: receptionist@healthylife.com / receptionist123\n";
    echo "Patient: patient@healthylife.com / patient123\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
