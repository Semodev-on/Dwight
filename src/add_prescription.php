<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin or doctor
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

// Fetch all doctors
$doctors_query = "SELECT id, first_name, last_name FROM doctors ORDER BY first_name, last_name";
$doctors_stmt = $pdo->query($doctors_query);
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all patients
$patients_query = "SELECT id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patients_stmt = $pdo->query($patients_query);
$patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $doctor_id = $_POST['doctor_id'] ?? '';
    $patient_id = $_POST['patient_id'] ?? '';
    $medication = $_POST['medication'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $instructions = $_POST['instructions'] ?? '';

    // Basic validation
    if (empty($doctor_id) || empty($patient_id) || empty($medication) || empty($dosage) || empty($frequency) || empty($duration)) {
        $error_message = "All fields except instructions are required.";
    } else {
        try {
            // Insert new prescription
            $insert_query = "INSERT INTO prescriptions (doctor_id, patient_id, medication, dosage, frequency, duration, instructions, prescribed_date) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $pdo->prepare($insert_query);
            
            if ($insert_stmt->execute([$doctor_id, $patient_id, $medication, $dosage, $frequency, $duration, $instructions])) {
                $_SESSION['success_message'] = "Prescription added successfully!";
                header("Location: admin_patients.php");
                exit();
            } else {
                $error_message = "Error adding prescription. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Retrieve success message from session if it exists
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Prescription - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;        /* Green primary */
            --secondary-color: #059669;      /* Darker green */
            --accent-color: #34d399;         /* Light green */
            --light-green: #d1fae5;          /* Very light green */
            --dark-green: #064e3b;           /* Dark green */
        }

        body {
            background-color: #f0fdf4;       /* Light green background */
            font-family: Arial, sans-serif;
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-green), var(--secondary-color)) !important;
        }

        .navbar-brand {
            color: #ffffff !important;
            font-weight: bold;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
        }

        .card-header {
            background: var(--primary-color);
            color: #ffffff;
            border-radius: 10px 10px 0 0;
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

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Form Controls */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
        }

        /* Labels */
        label {
            color: var(--dark-green);
            font-weight: 500;
        }

        /* Alert customization */
        .alert-success {
            background-color: var(--light-green);
            border-color: var(--primary-color);
            color: var(--dark-green);
        }

        /* Select dropdown */
        select.form-control {
            border: 1px solid #e2e8f0;
        }

        select.form-control:focus {
            border-color: var(--primary-color);
        }

        /* Form groups spacing */
        .form-group {
            margin-bottom: 1rem;
        }

        /* Textarea styling */
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        img{
            width: 75px;
            height: 75px;
            margin-left:50px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="admin_dashboard.php">
            <img src="barangay_victoria.png" alt="logo">
        </a>
        <div class="ml-auto">
            <a href="admin_patients.php" class="btn btn-outline-light btn-sm mr-2">Back to patients</a>
            <a href="admin_logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Prescription</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <div class="form-group">
                                <label for="doctor_id">Doctor</label>
                                <select class="form-control" id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="medication">Medication</label>
                                <input type="text" class="form-control" id="medication" name="medication" value="<?php echo htmlspecialchars($_POST['medication'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="dosage">Dosage</label>
                                <input type="text" class="form-control" id="dosage" name="dosage" value="<?php echo htmlspecialchars($_POST['dosage'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="frequency">Frequency</label>
                                <input type="text" class="form-control" id="frequency" name="frequency" value="<?php echo htmlspecialchars($_POST['frequency'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration</label>
                                <input type="text" class="form-control" id="duration" name="duration" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="instructions">Special Instructions</label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Prescription</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jQuery3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>