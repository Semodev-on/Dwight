<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id']) && isset($_POST['medical_status'])) {
    $patient_id = $_POST['patient_id'];
    $medical_status = $_POST['medical_status'];

    $query = "INSERT INTO medical_status (patient_id, status) VALUES (?, ?)";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$patient_id, $medical_status]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Medical status added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding medical status']);
    }
}