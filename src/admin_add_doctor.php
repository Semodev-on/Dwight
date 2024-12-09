<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($specialization) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            // Check if email already exists
            $check_email_query = "SELECT id FROM doctors WHERE email = ?";
            $check_email_stmt = $pdo->prepare($check_email_query);
            $check_email_stmt->execute([$email]);
            
            if ($check_email_stmt->fetchColumn()) {
                $error_message = "Email already exists in the system.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new doctor
                $insert_query = "INSERT INTO doctors (first_name, last_name, email, password, specialization, phone, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'active')";
                $insert_stmt = $pdo->prepare($insert_query);
                
                if ($insert_stmt->execute([$first_name, $last_name, $email, $hashed_password, $specialization, $phone])) {
                    $_SESSION['success_message'] = "Doctor added successfully!";
                    header("Location: admin_Doctors.php");
                    exit();
                } else {
                    $error_message = "Error adding doctor. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor - HMS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            background-color: #f8f9fa;
            padding-top: 76px;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #198754;
            color: white;
            text-align: center;
            font-weight: bold;
            border-radius: 1rem 1rem 0 0;
        }

        /* Form Styling */
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        /* Button Styling */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }

        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }

        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }

        .btn-outline-secondary:hover {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        /* Alert Styling */
        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Input Group Styling */
        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 0.5rem 0 0 0.5rem;
            padding: 0.75rem;
        }

        .input-group .form-control {
            border-radius: 0 0.5rem 0.5rem 0;
        }

        /* Specialization Select Styling */
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        /* Card Body Padding */
        .card-body {
            padding: 2rem;
        }

        /* Form Section Spacing */
        .form-section {
            margin-bottom: 2rem;
        }

        /* Row Gutters */
        .row {
            margin-bottom: 1rem;
        }

        /* Back Button */
        .btn-back {
            color: #198754;
            border-color: #198754;
        }

        .btn-back:hover {
            background-color: #198754;
            color: white;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <i class="fas fa-user-md"></i>
                Add New Doctor
            </a>
            <div class="ms-auto d-flex gap-3">
                <a href="admin_Doctors.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Doctors
                </a>
                <a href="admin_logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-md me-2"></i>Add New Doctor
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-user form-section-icon"></i>
                                    Personal Information
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-address-book form-section-icon"></i>
                                    Contact Information
                                </h6>
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-user-md form-section-icon"></i>
                                    Professional Information
                                </h6>
                                <div class="form-group mb-3">
                                    <label for="specialization" class="form-label">Specialization</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-stethoscope"></i>
                                        </span>
                                        <select class="form-control" id="specialization" name="specialization" required>
                                            <option value="">Select Specialization...</option>
                                            <option value="General Medicine">General Medicine</option>
                                            <option value="Pediatrics">Pediatrics</option>
                                            <option value="Cardiology">Cardiology</option>
                                            <option value="Dermatology">Dermatology</option>
                                            <option value="Orthopedics">Orthopedics</option>
                                            <option value="Neurology">Neurology</option>
                                            <option value="Psychiatry">Psychiatry</option>
                                            <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
                                            <option value="Ophthalmology">Ophthalmology</option>
                                            <option value="ENT">ENT (Ear, Nose, and Throat)</option>
                                            <option value="Dentistry">Dentistry</option>
                                            <option value="Endocrinology">Endocrinology</option>
                                            <option value="Gastroenterology">Gastroenterology</option>
                                            <option value="Pulmonology">Pulmonology</option>
                                            <option value="Urology">Urology</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6 class="form-section-title">
                                    <i class="fas fa-lock form-section-icon"></i>
                                    Security
                                </h6>
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i>Add Doctor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>