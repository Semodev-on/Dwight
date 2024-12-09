<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_index.php");
    exit();
}

require_once 'db_connect.php';

// Fetch some basic statistics
$stmt = $pdo->query("SELECT COUNT(*) as patient_count FROM patients");
$patient_count = $stmt->fetch()['patient_count'];

$stmt = $pdo->query("SELECT COUNT(*) as doctor_count FROM doctors");
$doctor_count = $stmt->fetch()['doctor_count'];

$stmt = $pdo->query("SELECT COUNT(*) as appointment_count FROM appointments WHERE status = 'Scheduled'");
$appointment_count = $stmt->fetch()['appointment_count'];

$stmt = $pdo->query("SELECT 
    status, 
    COUNT(*) as count 
FROM appointments 
GROUP BY status");
$appointment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month, 
    COUNT(*) as count 
FROM patients 
WHERE created_at IS NOT NULL 
GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
ORDER BY month DESC 
LIMIT 6");
$patient_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hospital Management System</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/jQuery3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard-charts.js"></script>
    <style>
        :root {
            --primary-color: #10b981;        /* Green primary */
            --secondary-color: #059669;      /* Darker green */
            --accent-color: #34d399;         /* Light green */
            --light-green: #d1fae5;          /* Very light green */
            --dark-green: #064e3b;           /* Dark green */
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-green), var(--secondary-color)) !important;
        }

        .navbar-brand, .nav-link {
            color: white !important;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2);
        }

        .card-title {
            color: var(--dark-green);
        }

        .card-text {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: bold;
        }

        /* Welcome text */
        h1 {
            color: var(--dark-green);
        }
        a{
            text-decoration:none;
        }
        img{
            width:100px;
            height:100px;
            margin-left:75px;
        }

        /* Add chart container styles */
        .chart-wrapper {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            margin-bottom: 20px;
            min-height: 400px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="index.php"><img src="barangay_victoria.png    " alt=""></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active">
                    <a class="nav-link" href="admin_dashboard.php" >Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_patients.php">Patients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_Doctors.php">Doctors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_Appointments.php">Appointments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_users.php">Admins</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="admin_logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <a href="admin_patients.php" target="blank">
                        <div class="card-body">
                            <h5 class="card-title">Total Patients</h5>
                            <p class="card-text"><?php echo $patient_count; ?></p>
                        </div>
                    </a>    
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <a href="admin_Doctors.php" target="blank">
                        <div class="card-body">
                            <h5 class="card-title">Total Doctors</h5>
                            <p class="card-text"><?php echo $doctor_count; ?></p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <a href="admin_Appointments.php" target="blank">
                        <div class="card-body">
                            <h5 class="card-title">Scheduled Appointments</h5>
                            <p class="card-text"><?php echo $appointment_count; ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-wrapper">
                    <canvas id="appointmentsChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-wrapper">
                    <canvas id="patientsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare appointment data
            const appointmentData = {
                labels: <?php echo json_encode(array_column($appointment_stats, 'status')); ?>,
                counts: <?php echo json_encode(array_column($appointment_stats, 'count')); ?>
            };

            // Prepare patient trends data
            const patientTrendsData = {
                months: <?php echo json_encode(array_map(function($item) {
                    return date('M Y', strtotime($item['month']));
                }, array_reverse($patient_trends))); ?>,
                counts: <?php echo json_encode(array_column(array_reverse($patient_trends), 'count')); ?>
            };

            // Initialize charts
            DashboardCharts.initAppointmentChart(appointmentData);
            DashboardCharts.initPatientTrendsChart(patientTrendsData);
        });
    </script>
</body>
</html>