<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $update_query = "UPDATE medical_certificates SET status = 'approved' WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$request_id]);
    // Optionally notify the patient about the approval
    header("Location: medical_certificates.php");
    exit();
}