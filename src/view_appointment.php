<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$success_message = '';
$error_message = '';

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    header("Location: doctor_appointments.php");
    exit();
}

$appointment_id = $_GET['id'];

// Get appointment details with patient information
$query = "SELECT 
    a.*,
    p.first_name as patient_first_name,
    p.last_name as patient_last_name,
    p.email as patient_email,
    p.phone as patient_phone,
    p.date_of_birth as patient_dob,
    p.gender as patient_gender
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.id = ? AND a.doctor_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();

// If appointment not found or doesn't belong to this doctor
if (!$appointment) {
    header("Location: doctor_appointments.php");
    exit();
}

// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $new_status = $_POST['status'];
        $notes = $_POST['notes'];
        $prescription = $_POST['prescription'];
        $diagnosis = $_POST['diagnosis'];

        $update_query = "UPDATE appointments 
                        SET status = ?, 
                            notes = ?, 
                            prescription = ?,
                            diagnosis = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ? AND doctor_id = ?";
        
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$new_status, $notes, $prescription, $diagnosis, $appointment_id, $doctor_id]);

        $success_message = "Appointment updated successfully!";
        
        // Refresh appointment data
        $stmt->execute([$appointment_id, $doctor_id]);
        $appointment = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Error updating appointment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
            width: 240px;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #198754;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital"></i> HMS - Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_appointments.php">
                            <i class="fas fa-arrow-left"></i> Back to Appointments
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Appointment Details</h5>
                </div>
                <div class="card-body">
                    <!-- Patient Information -->
                    <div class="patient-info">
                        <h6>Patient Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($appointment['patient_email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date of Birth:</strong> <?php echo date('M d, Y', strtotime($appointment['patient_dob'])); ?></p>
                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($appointment['patient_gender']); ?></p>
                                <p><strong>Appointment Date:</strong> <?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Form -->
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $appointment_id); ?>">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['diagnosis'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Prescription</label>
                            <textarea name="prescription" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['prescription'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="doctor_appointments.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>