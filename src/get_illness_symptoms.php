<?php
require_once 'db_connect.php';

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    $query = "SELECT complaint_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date 
              FROM complaints 
              WHERE patient_id = ? 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id]);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($complaints) {
        echo "<ul class='list-group'>";
        foreach ($complaints as $complaint) {
            echo "<li class='list-group-item'>";
            echo "<div class='d-flex justify-content-between align-items-center'>";
            echo "<div>";
            echo "<p class='mb-1'>" . htmlspecialchars($complaint['complaint_text']) . "</p>";
            echo "<small class='text-muted'>Added on: " . htmlspecialchars($complaint['formatted_date']) . "</small>";
            echo "</div>";
            echo "</div>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='text-muted'>No illness/symptoms recorded for this patient.</p>";
    }
} else {
    echo "<p class='text-danger'>Invalid patient ID.</p>";
}
?>