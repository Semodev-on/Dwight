<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_POST['patient_id'];
    $illness = $_POST['illness'];
    $symptoms = $_POST['symptoms'];

    if (empty($patient_id) || empty($illness) || empty($symptoms)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Check for existing illness/symptoms record
            $check_query = "SELECT COUNT(*) FROM illness_symptoms 
                           WHERE patient_id = ? 
                           AND illness = ? 
                           AND symptoms = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$patient_id, $illness, $symptoms]);
            $exists = $check_stmt->fetchColumn();

            if ($exists > 0) {
                $error_message = "This illness and symptoms combination already exists for this patient.";
            } else {
                // If no duplicate found, proceed with insertion
                $insert_query = "INSERT INTO complaints (patient_id, complaint_text, created_at) 
                               VALUES (?, ?, CURRENT_TIMESTAMP)";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([$patient_id, "$illness - $symptoms"]);

                // Get updated count
                $count_query = "SELECT COUNT(*) as count FROM complaints WHERE patient_id = ?";
                $count_stmt = $pdo->prepare($count_query);
                $count_stmt->execute([$patient_id]);
                $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                $_SESSION['success_message'] = "Illness and symptoms added successfully! Count: $new_count";
                header("Location: admin_patients.php");
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Error adding illness and symptoms: " . $e->getMessage();
        }
    }
}

$patients_query = "SELECT id, first_name, last_name FROM patients";
$patients_stmt = $pdo->query($patients_query);
$patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Illness/Symptoms - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
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
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-green), var(--secondary-color)) !important;
        }

        .navbar-brand {
            color: white !important;
        }


        img{
            width: 75px;
            height: 75px;
            margin-left:75px;
            margin-right:50px;
        }

        /* Sidebar styling */
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: var(--light-green);
            padding-top: 15px;
        }

        .list-group-item.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .list-group-item:hover {
            background-color: var(--accent-color);
            color: white;
            transition: all 0.3s ease;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
        }

        .card-header {
            background: var(--primary-color) !important;
            color: white !important;
            border-radius: 10px 10px 0 0;
        }

        /* Form Controls */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
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

        /* Labels */
        .form-label {
            color: var(--dark-green);
            font-weight: 500;
        }

        /* Alert customization */
        .alert-success {
            background-color: var(--light-green);
            border-color: var(--primary-color);
            color: var(--dark-green);
        }

        .alert-danger {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }

        /* Main content area */
        .main-content {
            padding: 20px;
            background-color: #ffffff;
        }

        /* Textarea styling */
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="barangay_victoria.png" alt="Logo">
            </a>
            <div class="ms-auto">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="list-group">
                    <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                       Dashboard
                    </a>
                    <a href="admin_patients.php" class="list-group-item list-group-item-action">
                        Patients
                    </a>
                    <a href="add_illness_symptoms.php" class="list-group-item list-group-item-action active">
                       Add Illness/Symptoms
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add Illness/Symptoms</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($error_message)) {
                            echo "<div class='alert alert-danger'>$error_message</div>";
                        }
                        if (!empty($success_message)) {
                            echo "<div class='alert alert-success'>$success_message</div>";
                        }
                        ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="patient_id" class="form-label">Patient</label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Select a patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="illness" class="form-label">Illness</label>
                                <input type="text" class="form-control" id="illness" name="illness" required>
                            </div>
                            <div class="mb-3">
                                <label for="symptoms" class="form-label">Symptoms</label>
                                <textarea class="form-control" id="symptoms" name="symptoms" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Illness/Symptoms</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>