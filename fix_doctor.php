<?php
require 'config.php';
try {
    $email = 'aswin@gmail.com';
    $password = 'doctor123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();


    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'doctor', status = 'active' WHERE email = ?");
    $stmt->execute([$hash, $email]);


    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user_id = $stmt->fetchColumn();

    if ($user_id) {

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, email, phone, specialization, department_name, experience_years, schedule_text, availability) VALUES (?, ?, '1234567890', 'General Medicine', 'General', 5, 'Mon-Fri 9AM-5PM', 'available')");
            $stmt->execute([$user_id, $email]);
            echo "Added $email to doctors table.\n";
        } else {
            echo "$email already in doctors table.\n";
        }
        echo "Password for $email reset to 'doctor123'.\n";
    } else {
        echo "User $email not found.\n";
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
