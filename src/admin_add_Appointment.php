<?php
session_start();
require_once 'db_connect.php';

//  mag CheChecking  if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

// Add this at the top of your file after the database connection
try {
    // Check patients table structure
    $table_check = $pdo->query("DESCRIBE patients");
    $patients_columns = $table_check->fetchAll(PDO::FETCH_COLUMN);
    error_log("Patients table columns: " . print_r($patients_columns, true));

    // Check patients_login table structure
    $table_check = $pdo->query("DESCRIBE patients_login");
    $patients_login_columns = $table_check->fetchAll(PDO::FETCH_COLUMN);
    error_log("Patients_login table columns: " . print_r($patients_login_columns, true));
} catch (PDOException $e) {
    error_log("Error checking table structure: " . $e->getMessage());
}

// Add this debugging code after database connection
try {
    $table_check = $pdo->query("DESCRIBE patients");
    $columns = $table_check->fetchAll(PDO::FETCH_COLUMN);
    error_log("Patients table columns: " . print_r($columns, true));
} catch (PDOException $e) {
    error_log("Error checking table structure: " . $e->getMessage());
}

// Fetch all doctors
$doctors_query = "SELECT id, first_name, last_name FROM doctors ORDER BY first_name, last_name";
$doctors_stmt = $pdo->query($doctors_query);
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Update the patients query to get all patients
$patients_query = "SELECT 
    pl.id,
    pl.first_name,
    pl.last_name
FROM patients_login pl
ORDER BY pl.first_name, pl.last_name";

try {
    $patients_stmt = $pdo->query($patients_query);
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($patients) {
        error_log("Found " . count($patients) . " patients");
    } else {
        error_log("No patients found");
    }
} catch (PDOException $e) {
    error_log("Error fetching patients: " . $e->getMessage());
    $patients = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $doctor_id = $_POST['doctor_id'];
    $patient_login_id = $_POST['patient_id']; // This is from patients_login table
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = $_POST['status'];

    // Combine date and time into a datetime string
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;

    // Validate inputs
    $current_date = date('Y-m-d');
    if ($appointment_date < $current_date) {
        $error_message = "Cannot schedule appointments in the past.";
    } elseif (empty($doctor_id) || empty($patient_login_id) || empty($appointment_date) || empty($appointment_time) || empty($status)) {
        $error_message = "All fields are required.";
    } elseif (!strtotime($appointment_datetime)) {
        $error_message = "Invalid date or time format.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // First, check if patient exists in patients table
            $check_patient = $pdo->prepare("SELECT id FROM patients WHERE patients_login_id = ?");
            $check_patient->execute([$patient_login_id]);
            $patient = $check_patient->fetch();

            if (!$patient) {
                // Create new patient record if it doesn't exist
                $insert_patient = $pdo->prepare("INSERT INTO patients (patients_login_id) VALUES (?)");
                $insert_patient->execute([$patient_login_id]);
                $patient_id = $pdo->lastInsertId();
                error_log("Created new patient record with ID: " . $patient_id);
            } else {
                $patient_id = $patient['id'];
                error_log("Using existing patient ID: " . $patient_id);
            }

            // Check for conflicting appointments
            $check_conflict_query = "SELECT id FROM appointments 
                                   WHERE doctor_id = ? 
                                   AND appointment_date = ? 
                                   AND patient_id = ?";
            $check_conflict_stmt = $pdo->prepare($check_conflict_query);
            $check_conflict_stmt->execute([$doctor_id, $appointment_datetime, $patient_id]);
            
            if ($check_conflict_stmt->fetchColumn()) {
                $pdo->rollBack();
                $error_message = "This appointment already exists.";
            } else {
                // Insert new appointment
                $insert_query = "INSERT INTO appointments (
                    doctor_id, 
                    patient_id, 
                    appointment_date, 
                    status, 
                    added_by_type
                ) VALUES (?, ?, ?, ?, 'admin')";
                
                $insert_stmt = $pdo->prepare($insert_query);
                
                if ($insert_stmt->execute([
                    $doctor_id, 
                    $patient_id,
                    $appointment_datetime, 
                    $status
                ])) {
                    $pdo->commit();
                    $_SESSION['success_message'] = "Appointment added successfully!";
                    error_log("Appointment added successfully");
                    header("Location: admin_Appointments.php");
                    exit();
                } else {
                    $pdo->rollBack();
                    $error_message = "Error adding appointment. Please try again.";
                    error_log("Error executing insert statement: " . print_r($insert_stmt->errorInfo(), true));
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Database error: " . $e->getMessage();
            error_log("PDO Exception: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Appointment - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #059669;
            --secondary-color: #34d399;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
        }

        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: #ffffff !important;
            font-weight: 600;
        }

        .btn-outline-light:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: #ffffff;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
            font-weight: 600;
        }

        /* Form Controls */
        .form-control {
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 0.75rem;
            margin-bottom: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            color: var(--dark-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Button Styling */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        /* Alert Styling */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        /* Select Styling */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23059669' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        /* Container Spacing */
        .container {
            padding: 2rem 1rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .card-header {
                padding: 1rem;
            }

            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#">Hospital Management System</a>
        <div class="ml-auto">
            <a href="admin_Appointments.php" class="btn btn-outline-light btn-sm mr-2">Back to Appointments</a>
            <a href="admin_logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Appointment</h5>
                    </div>
                    <div class="card-body">
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
                        <form action="admin_add_Appointment.php" method="POST">
                            <div class="form-group">
                                <label for="doctor_id">Doctor</label>
                                <select class="form-control" id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="patient_id">Patient</label>
                                <select class="form-control" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo htmlspecialchars($patient['id']); ?>">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a patient.</div>
                            </div>
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" required>
                            </div>
                            <div class="form-group">
                                <label for="appointment_time">Appointment Time</label>
                                <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Appointment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jQuery3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        // Set min date for appointment
        const appointmentDateInput = document.getElementById('appointment_date');
        const today = new Date();
        
        // Format today's date as YYYY-MM-DD
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const minDate = `${year}-${month}-${day}`;
        
        // Set the minimum date attribute
        appointmentDateInput.setAttribute('min', minDate);
        
        // Prevent selecting past dates
        appointmentDateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            if (selectedDate < today) {
                alert('Please select a future date for the appointment.');
                this.value = '';
            }
        });

        document.getElementById('patient_id').addEventListener('change', function() {
            console.log('Selected patient ID:', this.value);
        });

        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const patientId = document.getElementById('patient_id').value;
            if (!patientId) {
                e.preventDefault();
                alert('Please select a patient');
                return false;
            }
            console.log('Submitting form with patient ID:', patientId);
        });
    </script>
</body>
</html>