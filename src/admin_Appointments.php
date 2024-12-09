<?php
require_once 'admin_check.php';
require_once 'db_connect.php';

// Add this after session_start()
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

// Fetch all appointments with additional details
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
    ORDER BY a.appointment_date DESC";

try {
    $appointments_stmt = $pdo->query($appointments_query);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add debug logging
    error_log("Found " . count($appointments) . " appointments");
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    $appointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Hospital Management System</title>
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
            --lighter-green: #ecfdf5;        /* Even lighter green */
        }

        body {
            background-color: var(--light-color);
            font-family: Arial, sans-serif;
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-color), var(--secondary-color));
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--dark-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
            background-color: var(--lighter-green);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
            border-bottom: none;
        }

        .card-body {
            background-color: var(--lighter-green);
            border-radius: 0 0 15px 15px;
            padding: 1.5rem;
        }

        /* Button styling */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Table styling */
        .table {
            background-color: var(--light-color);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1.2rem 1rem;
            font-weight: 600;
        }

        .table tbody tr {
            background-color: var(--lighter-green);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
        }

        .table tbody td {
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            padding: 1rem;
        }

        /* DataTables customization */
        .dataTables_wrapper {
            background-color: var(--lighter-green);
            padding: 1.5rem;
            border-radius: 10px;
        }

        .dataTables_filter input,
        .dataTables_length select {
            background-color: white !important;
            border: 1px solid var(--primary-color) !important;
            border-radius: 6px !important;
            padding: 0.4rem !important;
        }

        .dataTables_filter input:focus,
        .dataTables_length select:focus {
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
            outline: none !important;
        }

        /* Pagination styling */
        .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--secondary-color) !important;
            border-color: var(--secondary-color) !important;
            color: white !important;
        }

        /* Status badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-info {
            background-color: var(--secondary-color) !important;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        /* Alert styling */
        .alert-success {
            background-color: var(--light-color);
            border-color: var(--success-color);
            color: var(--dark-color);
        }

        /* Add this to your existing styles */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 2px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }

        /* Modal styling */
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        img{
            width: 95px;
            height: 95px;
            margin-right:45px;
            margin-left:50px;
        }

        .dash{
            margin-left:50px;
        }
        *{
            color:black;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="barangay_victoria.png" alt="">
                HMS Doctors
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="admin_dashboard.php">
                    <button type="button" class="btn  btn-success dash me-3">Dashboard</button>
                </a>
                <a href="admin_logout.php">
                    <button type="button"  class="btn btn-danger">
                    Log Out
                    </button>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 d-flex justify-content-between align-items-center">
                    All Appointments
                    <a href="admin_add_appointment.php" class="btn btn-success btn-sm">
                         Add New Appointment
                    </a>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info">No appointments found.</div>
                <?php else: ?>
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
                                        <td>
                                            <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['patient_phone']); ?></small>
                                        </td>
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
                                            <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateModal<?php echo $appointment['id']; ?>">
                                                Update
                                            </button>
                                            <button type="button" 
                                                class="btn btn-danger btn-sm delete-appointment" 
                                                data-id="<?php echo $appointment['id']; ?>">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add DataTables initialization -->
    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var appointmentsTable = $('#appointmentsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 10,
                "responsive": true,
                "language": {
                    "emptyTable": "No appointments found",
                    "zeroRecords": "No matching appointments found"
                },
                "columns": [
                    { "width": "15%" }, // Date & Time
                    { "width": "20%" }, // Doctor
                    { "width": "15%" }, // Patient
                    { "width": "10%" }, // Status
                    { "width": "10%" }, // Added By
                    { "width": "15%" }  // Actions
                ]
            });

            // Auto-hide success message after 5 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut('slow');
            }, 5000);

            // Handle appointment deletion
            $('.delete-appointment').click(function() {
                if (confirm('Are you sure you want to delete this appointment?')) {
                    const appointmentId = $(this).data('id');
                    $.ajax({
                        type: 'POST',
                        url: 'delete_appointment.php',
                        data: { appointment_id: appointmentId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + (response.message || 'Could not delete appointment'));
                            }
                        },
                        error: function() {
                            alert('Error: Could not connect to server');
                        }
                    });
                }
            });
        });
    </script>

    <!-- Add this right before the closing </div> of the container -->
    <?php foreach ($appointments as $appointment): ?>
        <!-- Update Modal -->
        <div class="modal fade" id="updateModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="update_appointment.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Appointment Date & Time</label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       name="appointment_date" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($appointment['appointment_date'])); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="scheduled" <?php echo $appointment['status'] == 'scheduled' ? 'selected' : ''; ?>>
                                        Scheduled
                                    </option>
                                    <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>
                                        Completed
                                    </option>
                                    <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>
                                        Cancelled
                                    </option>
                                </select>
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