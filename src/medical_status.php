<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: admin_index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $query = "INSERT INTO medical_status (patient_id, status, notes) VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id, $status, $notes]);

    header("Location: admin_patients.php");
    exit();
}

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    $query = "SELECT * FROM medical_status WHERE patient_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id]);
    $medical_status = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Status - Patient Record Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Update Medical Status</h2>
        <form action="medical_status.php" method="POST">
            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Stable" <?php echo (isset($medical_status) && $medical_status['status'] == 'Stable') ? 'selected' : ''; ?>>Stable</option>
                    <option value="Critical" <?php echo (isset($medical_status) && $medical_status['status'] == 'Critical') ? 'selected' : ''; ?>>Critical</option>
                    <option value="Recovering" <?php echo (isset($medical_status) && $medical_status['status'] == 'Recovering') ? 'selected' : ''; ?>>Recovering</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($medical_status) ? htmlspecialchars($medical_status['notes']) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Medical Status</button>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>