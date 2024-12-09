<?php
require_once 'doctor_check.php';
require_once 'db_connect.php';

// Get doctor information
$doctor_id = $_SESSION['doctor_id'];
$query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Get today's appointments
$today = date('Y-m-d');
$appointments_query = "SELECT a.*, 
                      p.first_name as patient_first_name, 
                      p.last_name as patient_last_name 
                      FROM appointments a 
                      JOIN patients_login p ON a.patient_id = p.id 
                      WHERE a.doctor_id = ? 
                      AND DATE(a.appointment_date) = ? 
                      ORDER BY a.appointment_date";
$appointments_stmt = $pdo->prepare($appointments_query);
$appointments_stmt->execute([$doctor_id, $today]);
$today_appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming appointments
$upcoming_query = "SELECT a.*, 
                  p.first_name as patient_first_name, 
                  p.last_name as patient_last_name 
                  FROM appointments a 
                  JOIN patients_login p ON a.patient_id = p.id 
                  WHERE a.doctor_id = ? 
                  AND DATE(a.appointment_date) > ? 
                  ORDER BY a.appointment_date 
                  LIMIT 5";
$upcoming_stmt = $pdo->prepare($upcoming_query);
$upcoming_stmt->execute([$doctor_id, $today]);
$upcoming_appointments = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count today's appointments
$today_count_query = "SELECT COUNT(*) as count 
                     FROM appointments 
                     WHERE doctor_id = ? 
                     AND DATE(appointment_date) = CURRENT_DATE 
                     AND status != 'cancelled'";
$count_stmt = $pdo->prepare($today_count_query);
$count_stmt->execute([$doctor_id]);
$today_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Count upcoming appointments
$upcoming_count_query = "SELECT COUNT(*) as count 
                        FROM appointments 
                        WHERE doctor_id = ? 
                        AND DATE(appointment_date) > CURRENT_DATE 
                        AND status != 'cancelled'";
$upcoming_count_stmt = $pdo->prepare($upcoming_count_query);
$upcoming_count_stmt->execute([$doctor_id]);
$upcoming_count = $upcoming_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch medical certificate requests for the doctor
$requests_query = "SELECT mc.*, 
                  p.first_name as patient_first_name, 
                  p.last_name as patient_last_name 
                  FROM medical_certificates mc 
                  JOIN patients_login p ON mc.patient_id = p.id 
                  WHERE mc.doctor_id = ? 
                  AND mc.status = 'pending' 
                  ORDER BY mc.request_date DESC";
$requests_stmt = $pdo->prepare($requests_query);
$requests_stmt->execute([$doctor_id]);
$pending_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
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
        .navbar , .navbar-brand{
            color:white;
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
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="doctor_dashboard.php">
                Doctor Portal
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    Welcome, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                </span>
                <a href="doctors_logout.php" class="btn btn-outline-light btn-sm">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar">
        <div class="position-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="doctor_dashboard.php">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_appointments.php">
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
            <!-- Welcome Message -->
            <div class="row mb-4">
                <div class="col">
                    <h2>Welcome, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h2>
                    <p class="text-muted">Here's your daily overview</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Today's Appointments
                            </h5>
                        </div>
                        <div class="card-body d-flex align-items-center">
                            <div class="display-4 me-3 <?php echo $today_count > 0 ? 'text-success' : 'text-muted'; ?>">
                                <?php echo $today_count; ?>
                            </div>
                            <div>
                                <p class="mb-0 text-muted">Appointments</p>
                                <small class="text-<?php echo $today_count > 0 ? 'success' : 'muted'; ?>">
                                    <?php echo $today_count > 0 ? 'Scheduled for today' : 'No appointments today'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Upcoming
                            </h5>
                        </div>
                        <div class="card-body d-flex align-items-center">
                            <div class="display-4 me-3 <?php echo $upcoming_count > 0 ? 'text-primary' : 'text-muted'; ?>">
                                <?php echo $upcoming_count; ?>
                            </div>
                            <div>
                                <p class="mb-0 text-muted">Appointments</p>
                                <small class="text-<?php echo $upcoming_count > 0 ? 'primary' : 'muted'; ?>">
                                    <?php echo $upcoming_count > 0 ? 'Future scheduled' : 'No upcoming appointments'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Today's Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_appointments)): ?>
                                <p class="text-muted">No appointments scheduled for today.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Patient Name</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($today_appointments as $appointment): ?>
                                                <tr>
                                                    <td class="appointment-time">
                                                        <?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $appointment['status'] == 'completed' ? 'success' : ($appointment['status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="update_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Upcoming Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_appointments)): ?>
                                <p class="text-muted">No upcoming appointments.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($upcoming_appointments as $appointment): ?>
                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?>
                                            </small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>