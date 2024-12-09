<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'])) {
    try {
        $appointment_id = $_POST['appointment_id'];
        
        // Delete the appointment
        $delete_query = "DELETE FROM appointments WHERE id = ?";
        $stmt = $pdo->prepare($delete_query);
        $stmt->execute([$appointment_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Appointment deleted successfully!";
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting appointment']);
        error_log("Delete appointment error: " . $e->getMessage());
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
