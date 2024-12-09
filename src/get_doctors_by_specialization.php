<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Validate specialization_id
if (!isset($_GET['specialization_id']) || !is_numeric($_GET['specialization_id'])) {
    echo json_encode(['error' => 'Invalid specialization']);
    exit;
}

$specialization_id = (int)$_GET['specialization_id'];

try {
    // Get active doctors for the specialization
    $query = "SELECT DISTINCT 
                d.id,
                d.first_name,
                d.last_name
              FROM doctors d
              JOIN doctor_specializations ds ON d.id = ds.doctor_id
              WHERE ds.specialization_id = ?
              AND d.status = 'active'
              AND d.account_status = 'active'
              ORDER BY d.first_name, d.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$specialization_id]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($doctors)) {
        echo json_encode(['error' => 'No doctors available for this specialization']);
        exit;
    }
    
    echo json_encode($doctors);

} catch (PDOException $e) {
    error_log("Database error in get_doctors_by_specialization.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}