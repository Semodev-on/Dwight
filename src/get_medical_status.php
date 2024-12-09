<?php
require_once 'db_connect.php';

if (!isset($_GET['patient_id'])) {
    die('Patient ID is required');
}

$patient_id = $_GET['patient_id'];

try {
    // Updated query to match the actual table structure
    $stmt = $pdo->prepare("SELECT status, created_at 
                          FROM medical_status 
                          WHERE patient_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$patient_id]);
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($statuses) > 0) {
        echo '<div class="list-group">';
        foreach ($statuses as $status) {
            $formatted_date = date('M d, Y H:i', strtotime($status['created_at']));
            echo '<div class="list-group-item">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<p class="mb-1">' . htmlspecialchars($status['status']) . '</p>';
            echo '<small class="text-muted">' . $formatted_date . '</small>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="text-muted">No medical status records found.</p>';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo '<p class="text-danger">Error loading medical status records.</p>';
}
?>