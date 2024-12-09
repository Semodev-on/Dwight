<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['doctor_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$doctor_id = $_POST['doctor_id'];
$status = $_POST['status'];

try {
    $stmt = $pdo->prepare("UPDATE doctors SET status = ? WHERE id = ?");
    $stmt->execute([$status, $doctor_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}