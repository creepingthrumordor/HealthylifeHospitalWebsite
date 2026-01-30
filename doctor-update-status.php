<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($appointment_id && $status && in_array($status, ['completed', 'cancelled'])) {
        try {
            // Verify the appointment belongs to the logged-in doctor
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $doctor_id = $stmt->fetchColumn();

            if ($doctor_id) {
                // Update the status
                $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
                $stmt->execute([$status, $appointment_id, $doctor_id]);
            }
        } catch (Exception $e) {
            // Log error if needed, but for now just redirect
        }
    }
}

header('Location: doctor-dashboard.php');
exit;
