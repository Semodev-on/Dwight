<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Verify patient session
    if (!isset($_SESSION['patient_id'])) {
        throw new Exception('Session expired. Please login again.');
    }

    // Validate input
    if (!isset($_POST['appointment_id'])) {
        throw new Exception('Missing appointment ID.');
    }

    $patient_id = (int)$_SESSION['patient_id'];
    $appointment_id = (int)$_POST['appointment_id'];

    // Begin transaction
    $pdo->beginTransaction();

    // Check if appointment exists and belongs to the patient
    $check_appointment = $pdo->prepare("
        SELECT appointment_date, status, doctor_id 
        FROM appointments 
        WHERE id = ? AND patient_id = ?
    ");
    $check_appointment->execute([$appointment_id, $patient_id]);
    $appointment = $check_appointment->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        throw new Exception('Appointment not found or unauthorized.');
    }

    // Check if appointment is already cancelled
    if ($appointment['status'] === 'cancelled') {
        throw new Exception('This appointment is already cancelled.');
    }

    // Check if appointment is in the past
    if (strtotime($appointment['appointment_date']) < time()) {
        throw new Exception('Cannot cancel past appointments.');
    }

    // Update appointment status to cancelled
    $cancel = $pdo->prepare("
        UPDATE appointments 
        SET status = 'cancelled',
            cancelled_at = NOW(),
            cancelled_by = 'patient'
        WHERE id = ? AND patient_id = ?
    ");

    $success = $cancel->execute([$appointment_id, $patient_id]);

    if (!$success) {
        throw new Exception('Failed to cancel appointment.');
    }

    // Optional: Notify doctor about cancellation
    try {
        $notify = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, user_type, message, created_at) 
            VALUES (?, 'doctor', ?, NOW())
        ");
        $notify->execute([
            $appointment['doctor_id'],
            "Appointment for " . date('M d, Y h:i A', strtotime($appointment['appointment_date'])) . " has been cancelled by the patient."
        ]);
    } catch (Exception $e) {
        // Log notification error but don't stop the cancellation process
        error_log("Failed to send notification: " . $e->getMessage());
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment cancelled successfully.'
    ]);

} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    error_log("Error in patient_cancel_appointment.php: " . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
