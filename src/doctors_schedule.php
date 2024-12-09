<?php
require_once 'doctor_check.php';
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$success_message = '';
$error_message = '';

// Get doctor information
$query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

// Handle schedule updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Delete existing schedule
        $delete_query = "DELETE FROM doctor_schedule WHERE doctor_id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$doctor_id]);

        // Initialize schedule array if not set
        $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : [];

        // Only proceed if we have schedule data
        if (!empty($schedule) && is_array($schedule)) {
            // Insert new schedule
            $insert_query = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, status) 
                            VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_query);

            foreach ($schedule as $day => $times) {
                // Check if the day is marked as available
                if (isset($times['is_available']) && $times['is_available'] == 1) {
                    // Set default times if not provided
                    $start_time = !empty($times['start_time']) ? $times['start_time'] : '09:00';
                    $end_time = !empty($times['end_time']) ? $times['end_time'] : '17:00';
                    
                    $insert_stmt->execute([
                        $doctor_id,
                        $day,
                        $start_time,
                        $end_time,
                        'available'
                    ]);
                }
            }
        }

        $pdo->commit();
        $success_message = "Schedule updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error updating schedule: " . $e->getMessage();
    }
}

// Get current schedule
$schedule_query = "SELECT * FROM doctor_schedules WHERE doctor_id = ?";
$schedule_stmt = $pdo->prepare($schedule_query);
$schedule_stmt->execute([$doctor_id]);
$current_schedule = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize schedule array
$schedule_array = [];

// Convert schedule to associative array for easier access
if (!empty($current_schedule)) {
    foreach ($current_schedule as $schedule) {
        $schedule_array[$schedule['day_of_week']] = [
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'is_available' => ($schedule['status'] == 'available' ? 1 : 0)
        ];
    }
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedule - Hospital Management System</title>
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
            margin-top: 50px;
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
            <a class="navbar-brand" href="#">
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
                    <a class="nav-link active" href="doctors_schedule.php">
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
                    <h5 class="card-title mb-0">My Schedule</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="scheduleForm">
                        <?php foreach ($days_of_week as $day): ?>
                            <div class="time-slot">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="available_<?php echo $day; ?>" 
                                                   name="schedule[<?php echo $day; ?>][is_available]" 
                                                   value="1"
                                                   <?php echo isset($schedule_array[$day]) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="available_<?php echo $day; ?>">
                                                <?php echo $day; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Start Time</label>
                                            <input type="time" 
                                                   class="form-control" 
                                                   name="schedule[<?php echo $day; ?>][start_time]"
                                                   value="<?php echo isset($schedule_array[$day]) ? $schedule_array[$day]['start_time'] : '09:00'; ?>"
                                                   <?php echo !isset($schedule_array[$day]) ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>End Time</label>
                                            <input type="time" 
                                                   class="form-control" 
                                                   name="schedule[<?php echo $day; ?>][end_time]"
                                                   value="<?php echo isset($schedule_array[$day]) ? $schedule_array[$day]['end_time'] : '17:00'; ?>"
                                                   <?php echo !isset($schedule_array[$day]) ? 'disabled' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <button type="submit" class="btn btn-success">
                                Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('scheduleForm');
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            
            checkboxes.forEach(checkbox => {
                const timeInputs = checkbox.closest('.time-slot').querySelectorAll('input[type="time"]');
                
                // Initial state
                timeInputs.forEach(input => {
                    input.disabled = !checkbox.checked;
                });

                // Change handler
                checkbox.addEventListener('change', function() {
                    timeInputs.forEach(input => {
                        input.disabled = !this.checked;
                    });
                });
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const checkedDays = form.querySelectorAll('input[type="checkbox"]:checked');
                if (checkedDays.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one day for your schedule.');
                }
            });
        });
    </script>
</body>
</html>