<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    try {
        $doctor_id = $_SESSION['doctor_id'];
        $appointment_id = $_POST['appointment_id'];

        // Verify the appointment belongs to this doctor
        $check_query = "SELECT id FROM appointments WHERE id = ? AND doctor_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$appointment_id, $doctor_id]);

        if ($check_stmt->fetch()) {
            // Delete the appointment
            $delete_query = "DELETE FROM appointments WHERE id = ? AND doctor_id = ?";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute([$appointment_id, $doctor_id]);

            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}