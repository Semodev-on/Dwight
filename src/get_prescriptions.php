<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized access";
    exit();
}

// Check if patient_id is provided
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    echo "Invalid patient ID";
    exit();
}

$patient_id = $_GET['patient_id'];

try {
    // Get prescriptions with basic column names
    $query = "SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($prescriptions)) {
        echo '<div class="alert alert-info">No prescriptions found for this patient.</div>';
    } else {
        echo '<div class="list-group">';
        foreach ($prescriptions as $prescription) {
            echo '<div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <div class="prescription-content">
                            <h6 class="mb-1">Medication: ' . htmlspecialchars($prescription['medication']) . '</h6>
                            <p class="mb-1"><strong>Dosage:</strong> ' . htmlspecialchars($prescription['dosage']) . '</p>
                            <p class="mb-1"><strong>Frequency:</strong> ' . htmlspecialchars($prescription['frequency']) . '</p>
                            <p class="mb-1"><strong>Duration:</strong> ' . htmlspecialchars($prescription['duration']) . '</p>
                            <p class="mb-1"><strong>Instructions:</strong> ' . htmlspecialchars($prescription['instructions']) . '</p>
                            ' . (!empty($prescription['notes']) ? '<p class="mb-1"><strong>Notes:</strong> ' . htmlspecialchars($prescription['notes']) . '</p>' : '') . '
                        </div>
                        <div class="prescription-meta">
                            <small class="text-muted">Prescribed: ' . date('M d, Y', strtotime($prescription['prescribed_date'])) . '</small>
                            <br>
                            <span class="badge bg-' . ($prescription['status'] === 'active' ? 'success' : 'secondary') . '">
                                ' . ucfirst(htmlspecialchars($prescription['status'])) . '
                            </span>
                        </div>
                    </div>
                  </div>';
        }
        echo '</div>';
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error fetching prescriptions: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<style>
    .list-group {
        max-height: 400px;
        overflow-y: auto;
    }

    .list-group-item {
        border-left: 4px solid var(--primary-color);
        margin-bottom: 8px;
        border-radius: 4px;
        padding: 1rem;
    }

    .prescription-content {
        flex: 1;
        padding-right: 15px;
    }

    .prescription-content p {
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .prescription-content h6 {
        color: var(--primary-color);
        font-weight: 600;
    }

    .prescription-meta {
        text-align: right;
        min-width: 120px;
    }

    .text-muted {
        font-size: 0.85rem;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.4em 0.8em;
        margin-top: 0.5rem;
    }
</style>