<?php
require_once 'doctor_check.php';
require_once 'db_connect.php';

$doctor_id = $_SESSION['doctor_id'];
$success_message = '';
$error_message = '';

// Get doctor information
$query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    try {
        $appointment_id = $_POST['appointment_id'];
        $new_status = $_POST['status'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

        // Check for existing appointments at the same time
        $check_query = "SELECT COUNT(*) FROM appointments 
                       WHERE doctor_id = ? 
                       AND appointment_date = (
                           SELECT appointment_date 
                           FROM appointments 
                           WHERE id = ?
                       )
                       AND id != ? 
                       AND status != 'cancelled'";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$doctor_id, $appointment_id, $appointment_id]);
        $exists = $check_stmt->fetchColumn();

        if ($exists > 0 && $new_status != 'cancelled') {
            $error_message = "Another appointment already exists at this time slot.";
        } else {
            $update_query = "UPDATE appointments SET status = ?, notes = ? WHERE id = ? AND doctor_id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$new_status, $notes, $appointment_id, $doctor_id]);
            $success_message = "Appointment status updated successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error updating appointment status: " . $e->getMessage();
    }
}

// Add new appointment (if this functionality exists)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_appointment'])) {
    try {
        $patient_id = $_POST['patient_id'];
        $appointment_date = $_POST['appointment_date'];

        // Check for existing doctor appointments at the same time
        $check_doctor_query = "SELECT COUNT(*) FROM appointments 
                             WHERE doctor_id = ? 
                             AND appointment_date = ? 
                             AND status != 'cancelled'";
        $check_doctor_stmt = $pdo->prepare($check_doctor_query);
        $check_doctor_stmt->execute([$doctor_id, $appointment_date]);
        $doctor_exists = $check_doctor_stmt->fetchColumn();

        // Check for existing patient appointments at the same time
        $check_patient_query = "SELECT COUNT(*) FROM appointments 
                              WHERE patient_id = ? 
                              AND appointment_date = ? 
                              AND status != 'cancelled'";
        $check_patient_stmt = $pdo->prepare($check_patient_query);
        $check_patient_stmt->execute([$patient_id, $appointment_date]);
        $patient_exists = $check_patient_stmt->fetchColumn();

        if ($doctor_exists > 0) {
            $error_message = "This time slot is already booked with the doctor.";
        } elseif ($patient_exists > 0) {
            $error_message = "This patient already has an appointment at this time.";
        } else {
            $insert_query = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, status, added_by_type) 
                            VALUES (?, ?, ?, 'scheduled', 'doctor')";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([$doctor_id, $patient_id, $appointment_date]);
            $success_message = "Appointment scheduled successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error scheduling appointment: " . $e->getMessage();
    }
}

// Handle appointment deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    try {
        $appointment_id = $_POST['appointment_id'];
        $delete_query = "DELETE FROM appointments WHERE id = ? AND doctor_id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$appointment_id, $doctor_id]);
        $success_message = "Appointment deleted successfully!";
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        $error_message = "Error deleting appointment: " . $e->getMessage();
    }
}

// Get appointments with patient information
$appointments_query = "SELECT 
    a.*, 
    d.first_name AS doctor_first_name, 
    d.last_name AS doctor_last_name,
    d.specializations AS doctor_specialization,
    p.first_name AS patient_first_name, 
    p.last_name AS patient_last_name,
    p.phone as patient_phone,
    CASE 
        WHEN a.added_by_type = 'doctor' THEN 'Doctor'
        WHEN a.added_by_type = 'admin' THEN 'Admin'
        ELSE 'Patient'
    END as added_by_role
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC";

$appointments_stmt = $pdo->prepare($appointments_query);
$appointments_stmt->execute([$doctor_id]);
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <style>
         :root {
            --primary-color: #10b981;        /* Green primary */
            --secondary-color: #059669;      /* Darker green */
            --success-color: #34d399;        /* Light green */
            --warning-color: #f59e0b;        /* Keep warning orange */
            --danger-color: #ef4444;         /* Keep danger red */
            --dark-color: #064e3b;           /* Dark green */
            --light-color: #f0fdf4;          /* Very light green */
        }

        body {
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .navbar {
            background: linear-gradient(135deg, var(--dark-color), var(--secondary-color));
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            width: 250px;
            background: white;
            box-shadow: 2px 0 5px rgba(16, 185, 129, 0.1);
            position: fixed;
            left: 0;
            padding: 1rem 0;
        }
        .nav-link {
            color: var(--dark-color);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
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
        .status-badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        
        .delete-appointment {
            transition: all 0.3s ease;
        }
        
        .delete-appointment:hover {
            background-color: #dc3545;
            border-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="doctor_dashboard.php">
                 Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="doctors_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="doctors_logout.php"> Logout</a></li>
                        </ul>
                    </li>
                 
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="position-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="doctor_dashboard.php">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="doctors_appointments.php">
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_patients.php">
                        My Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_schedule.php">
                        My Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="medical_certificates.php">
                        Medical Certificates
                    </a>
                </li>
            </ul>
        </div>
    </div>

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
                    <h5 class="card-title mb-0">My Appointments</h5>
                </div>
                <div class="card-body">
                    <table id="appointmentsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Patient</th>
                                <th>Status</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($appointments)): ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                        <td>
                                            Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['doctor_specialization']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match(strtolower($appointment['status'])) {
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'scheduled' => 'warning',
                                                    default => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $appointment['added_by_role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updateModal<?php echo $appointment['id']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#appointmentsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 10,
                language: {
                    search: "Search appointments:"
                }
            });
        });
    </script>

    <!-- Update Modal for each appointment -->
    <?php foreach ($appointments as $appointment): ?>
    <div class="modal fade" id="updateModal<?php echo $appointment['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="scheduled" <?php echo $appointment['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>