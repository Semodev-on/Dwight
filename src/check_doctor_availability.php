<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['available' => false]);
    exit();
}

if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
    try {
        $check_query = "SELECT * FROM doctor_schedule 
                       WHERE doctor_id = ? 
                       AND DAYNAME(?) = day_of_week 
                       AND status = 'available'";
        $stmt = $pdo->prepare($check_query);
        $stmt->execute([$_GET['doctor_id'], $_GET['date']]);
        
        echo json_encode(['available' => (bool)$stmt->fetch()]);
    } catch (PDOException $e) {
        echo json_encode(['available' => false]);
    }
}