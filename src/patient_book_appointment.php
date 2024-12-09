<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Verify session
    if (!isset($_SESSION['patient_id'])) {
        throw new Exception('Session expired. Please login again.');
    }

    // Validate input
    if (!isset($_POST['doctor_id']) || !isset($_POST['appointment_date']) || 
        !isset($_POST['appointment_time']) || !isset($_POST['reason'])) {
        throw new Exception('Missing required fields.');
    }

    // Sanitize inputs
    $patient_id = (int)$_SESSION['patient_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $reason = trim(strip_tags($_POST['reason']));
    $appointment_date = trim($_POST['appointment_date']);
    $appointment_time = trim($_POST['appointment_time']);

    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;

    // Validate datetime
    if (!strtotime($appointment_datetime)) {
        throw new Exception('Invalid appointment date/time.');
    }

    if (strtotime($appointment_datetime) < time()) {
        throw new Exception('Appointment cannot be in the past.');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Check if doctor is available at this time
    $check_doctor = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND status != 'cancelled'
    ");
    $check_doctor->execute([$doctor_id, $appointment_datetime]);
    if ($check_doctor->fetchColumn() > 0) {
        throw new Exception('This time slot is already booked with the doctor.');
    }

    // Check if patient already has an appointment at this time
    $check_patient = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE patient_id = ? 
        AND DATE(appointment_date) = DATE(?) 
        AND status != 'cancelled'
    ");
    $check_patient->execute([$patient_id, $appointment_datetime]);
    if ($check_patient->fetchColumn() > 0) {
        throw new Exception('You already have an appointment scheduled for this date.');
    }

    // Check if patient has a pending appointment with the same doctor
    $check_pending = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE patient_id = ? 
        AND doctor_id = ? 
        AND status = 'pending'
    ");
    $check_pending->execute([$patient_id, $doctor_id]);
    if ($check_pending->fetchColumn() > 0) {
        throw new Exception('You already have a pending appointment with this doctor.');
    }

    // Insert appointment
    $insert = $pdo->prepare("
        INSERT INTO appointments 
        (patient_id, doctor_id, appointment_date, reason, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");

    $success = $insert->execute([
        $patient_id,
        $doctor_id,
        $appointment_datetime,
        $reason
    ]);

    if (!$success) {
        throw new Exception('Failed to book appointment.');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully.'
    ]);

} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    error_log("Error in book_appointment.php: " . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}