<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
    $last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $specializations = isset($_POST['specializations']) ? $_POST['specializations'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($specializations) || empty($phone)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM doctors WHERE email = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$email]);
        
        if ($check_stmt->rowCount() > 0) {
            $error_message = "Email already registered.";
        } else {
            try {
                // Validate first name
                $first_name = validateName($_POST['first_name']);
                $last_name = validateName($_POST['last_name']);
                
                // If we get here, the names are valid
                // Continue with your database insertion
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new doctor
                $insert_query = "INSERT INTO doctors (first_name, last_name, email, password, specializations, phone, account_statuses) 
                                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                $insert_stmt = $pdo->prepare($insert_query);
                $insert_stmt->execute([$first_name, $last_name, $email, $hashed_password, $specializations, $phone]);
                
                $success_message = "Registration successful! Please wait for admin approval before logging in.";
                
                // Optionally redirect after successful registration
                header("refresh:5;url=doctor_login.php");
            } catch (PDOException $e) {
                $error_message = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <script src="js/form-validation.js"></script>
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
        .container{
            margin-top:-70px;
        }
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

    </style>
</head>
<body>
    <div class="background"></div>
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Doctor Registration</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="doctorSignupForm">
                            <div class="form-section">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <div class="form">
                                                <input type="text" class="form-control" id="first_name" name="first_name" required pattern="[A-Za-z\s]+" oninput="validateLettersOnly(this)" title="Please enter letters only">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <div class="form">
                                                <input type="text" class="form-control" id="last_name" name="last_name" required pattern="[A-Za-z\s]+" oninput="validateLettersOnly(this)" title="Please enter letters only">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="form">
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="form">
                                        <input type="number" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-group mb-3">
                                    <label for="specializations" class="form-label">Specializations</label>
                                    <div class="form">
                                        <select class="form-control" id="specializations" name="specializations" required>
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
                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="form">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="form">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    Register
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        validateForm('doctorSignupForm');
    });
    </script>
</body>
</html>