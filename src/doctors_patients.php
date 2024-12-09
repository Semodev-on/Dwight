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
        $pdo->beginTransaction();
        
        $appointment_id = $_POST['appointment_id'];
        $new_status = $_POST['status'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $diagnosis = isset($_POST['diagnosis']) ? $_POST['diagnosis'] : '';
        $prescription = isset($_POST['prescription']) ? $_POST['prescription'] : '';

        // Get the appointment details first
        $get_appointment = $pdo->prepare("
            SELECT appointment_date, patient_id 
            FROM appointments 
            WHERE id = ? AND doctor_id = ?
        ");
        $get_appointment->execute([$appointment_id, $doctor_id]);
        $current_appointment = $get_appointment->fetch();

        if ($new_status === 'confirmed') {
            // Check for conflicting appointments when confirming
            $check_conflicts = $pdo->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND id != ? 
                AND status IN ('confirmed', 'pending')
            ");
            $check_conflicts->execute([
                $doctor_id, 
                $current_appointment['appointment_date'],
                $appointment_id
            ]);
            
            if ($check_conflicts->fetchColumn() > 0) {
                throw new Exception("Cannot confirm appointment: Time slot conflict exists.");
            }

            // Check if patient has other confirmed appointments on the same day
            $check_patient = $pdo->prepare("
                SELECT COUNT(*) 
                FROM appointments 
                WHERE patient_id = ? 
                AND DATE(appointment_date) = DATE(?) 
                AND id != ? 
                AND status = 'confirmed'
            ");
            $check_patient->execute([
                $current_appointment['patient_id'],
                $current_appointment['appointment_date'],
                $appointment_id
            ]);
            
            if ($check_patient->fetchColumn() > 0) {
                throw new Exception("Patient already has a confirmed appointment on this date.");
            }
        }

        // Update the appointment
        $update_query = "
            UPDATE appointments 
            SET status = ?, 
                notes = ?, 
                diagnosis = ?, 
                prescription = ?, 
                updated_at = NOW() 
            WHERE id = ? 
            AND doctor_id = ?";
        
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([
            $new_status,
            $notes,
            $diagnosis,
            $prescription,
            $appointment_id,
            $doctor_id
        ]);

        $pdo->commit();
        $success_message = "Appointment updated successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// Get appointments with patient information (updated query)
$appointments_query = "
    SELECT 
        a.id,
        a.appointment_date,
        a.status,
        a.notes,
        a.diagnosis,
        a.prescription,
        a.created_at,
        a.updated_at,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        p.phone as patient_phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'pending' THEN 1
            WHEN a.status = 'confirmed' THEN 2
            ELSE 3
        END,
        a.appointment_date DESC";

$appointments_stmt = $pdo->prepare($appointments_query);
$appointments_stmt->execute([$doctor_id]);
$appointments = $appointments_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Patients - Hospital Management System</title>
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
            font-family: 'Inter', sans-serif;
            background: #f0fdf4;
            min-height: 100vh;
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-color), var(--secondary-color));
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Sidebar styling */
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

        /* Card styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            margin-bottom: 1.5rem;
            margin-top:50px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Table styling */
        .table {
            border-collapse: separate;
            border-spacing: 0 0.8rem;
            width: 100%;
        }

        .table th {
            background: var(--light-color);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .table td {
            background: white;
            border: none;
            padding: 1.2rem 1rem;
            vertical-align: middle;
        }

        /* Status badge styling */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }

        /* Button styling */
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

        /* Add to existing styles */
        .display-4 {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .text-success {
            color: var(--success-color) !important;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .card-body.d-flex {
            min-height: 100px;
        }

        /* Appointment status badges */
        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--warning-color) !important;
        }

        .badge.bg-danger {
            background-color: var(--danger-color) !important;
        }

        /* Counter animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .display-4 {
            animation: countUp 0.5s ease-out;
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
                            <li><a class="dropdown-item" href="doctors_logout.php">Logout</a></li>
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
                    <a class="nav-link" href="doctor_dashboard.php">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link " href="doctors_appointments.php">
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="doctors_patients.php">
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
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_phone']); ?></td>
                                    <td>
                                        <span class="badge status-badge bg-<?php 
                                            echo $appointment['status'] == 'completed' ? 'success' : 
                                                ($appointment['status'] == 'cancelled' ? 'danger' : 
                                                ($appointment['status'] == 'confirmed' ? 'primary' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Update Button -->
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateModal<?php echo $appointment['id']; ?>">
                                                Update
                                            </button>
                                            
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-danger delete-appointment" 
                                                data-id="<?php echo $appointment['id']; ?>"
                                                onclick="deleteAppointment(<?php echo $appointment['id']; ?>)">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Update Modal -->
                                <div class="modal fade" id="updateModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Appointment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form id="updateForm<?php echo $appointment['id']; ?>" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Patient</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>" readonly>
                                                    </div>
                                                    
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
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
    <script>
    function deleteAppointment(appointmentId) {
        if (confirm('Are you sure you want to delete this appointment?')) {
            $.ajax({
                url: 'delete_appointment.php',
                type: 'POST',
                data: {
                    appointment_id: appointmentId,
                    doctor_id: <?php echo $doctor_id; ?>
                },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Appointment deleted successfully');
                            location.reload();
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (e) {
                        alert('Error processing response');
                    }
                },
                error: function() {
                    alert('Error deleting appointment');
                }
            });
        }
    }
    </script>
</body>
</html>