<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($doctor_id && $status) {
        try {
            // Update the account_status in the doctors table
            $query = "UPDATE doctors SET account_status = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([$status, $doctor_id]);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>