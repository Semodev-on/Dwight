<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];

    // Generate a random password for the patient
    $random_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($phone)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // First check if email already exists
            $check_email_query = "SELECT id FROM patients_login WHERE email = ?";
            $check_stmt = $pdo->prepare($check_email_query);
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $error_message = "A patient with this email already exists.";
            } else {
                // Start transaction
                $pdo->beginTransaction();

                // Insert into patients_login first
                $login_query = "INSERT INTO patients_login (email, password, first_name, last_name) VALUES (?, ?, ?, ?)";
                $login_stmt = $pdo->prepare($login_query);
                $login_stmt->execute([$email, $hashed_password, $first_name, $last_name]);
                
                $patients_login_id = $pdo->lastInsertId();

                // Insert into patients table
                $insert_query = "INSERT INTO patients (
                    first_name, 
                    last_name, 
                    date_of_birth, 
                    phone, 
                    email, 
                    gender, 
                    civil_status, 
                    patients_login_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([
                    $first_name, 
                    $last_name, 
                    $date_of_birth, 
                    $phone, 
                    $email, 
                    $gender, 
                    $civil_status,
                    $patients_login_id
                ]);

                // Commit the transaction
                $pdo->commit();

                $success_message = "Patient added successfully! Temporary password: " . $random_password;
                
                // Redirect after showing the password
                header("refresh:5;url=admin_patients.php");
            }
        } catch (PDOException $e) {
            // Rollback the transaction if something failed
            $pdo->rollBack();
            $error_message = "Error adding patient: " . $e->getMessage();
            error_log("Database Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient - Hospital Management System</title>
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

        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
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

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
            padding: 30px;
            margin-top: 50px;
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

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Headings */
        h2 {
            color: var(--dark-green);
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
        img{
            width: 75px;
            height: 75px;
            margin-left:100px;
            margin-right:;
        }
        .background{
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-height: 100vh;
            background-image: url(barangay_health_center.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            z-index: -1;
            filter: blur(16px);
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="barangay_victoria.png" alt="Logo">
            </a>
            <div class="ms-auto">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="admin_logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Add New Patient</h2>
        <?php
        if (!empty($error_message)) {
            echo "<div class='alert alert-danger' role='alert'>$error_message</div>";
        }
        if (!empty($success_message)) {
            echo "<div class='alert alert-success' role='alert'>$success_message</div>";
        }
        ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="number" class="form-control" id="phone" name="phone" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="civil_status" class="form-label">Civil Status</label>
                    <select class="form-select" id="civil_status" name="civil_status">
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Add Patient</button>
                <a href="admin_patients.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>