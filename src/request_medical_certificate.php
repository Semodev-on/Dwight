<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Verify session
    if (!isset($_SESSION['patient_id'])) {
        throw new Exception('Session expired. Please login again.');
    }

    // Validate input
    if (!isset($_POST['doctor_id']) || !isset($_POST['reason']) || 
        !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
        throw new Exception('Missing required fields.');
    }

    // Sanitize inputs
    $patient_id = (int)$_SESSION['patient_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $reason = trim(strip_tags($_POST['reason']));
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);

    // Begin transaction
    $pdo->beginTransaction();

    // First, verify that the patient exists
    $patient_check = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
    $patient_check->execute([$patient_id]);
    if (!$patient_check->fetch()) {
        throw new Exception('Patient record not found.');
    }

    // Verify doctor exists and is active
    $doctor_check = $pdo->prepare("SELECT id FROM doctors WHERE id = ? AND account_status = 'active'");
    $doctor_check->execute([$doctor_id]);
    if (!$doctor_check->fetch()) {
        throw new Exception('Selected doctor is not available.');
    }

    // Validate dates
    if (!strtotime($start_date) || !strtotime($end_date)) {
        throw new Exception('Invalid date format.');
    }

    if (strtotime($end_date) < strtotime($start_date)) {
        throw new Exception('End date cannot be before start date.');
    }

    // Insert request
    $insert = $pdo->prepare("
        INSERT INTO medical_certificates 
        (patient_id, doctor_id, reason, start_date, end_date, request_date, status) 
        VALUES (?, ?, ?, ?, ?, NOW(), 'pending')
    ");

    $success = $insert->execute([
        $patient_id,
        $doctor_id,
        $reason,
        $start_date,
        $end_date
    ]);

    if (!$success) {
        throw new Exception('Failed to save request.');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Medical certificate request submitted successfully.'
    ]);

} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    error_log("Error in request_medical_certificate.php: " . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}   