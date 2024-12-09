<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['request_otp'])) {
        $email = trim($_POST['email']);
        
        try {
            // Check if email exists in patients_login table
            $check_email = "SELECT * FROM patients_login WHERE email = ?";
            $stmt = $pdo->prepare($check_email);
            $stmt->execute([$email]);
            $patient = $stmt->fetch();

            if ($patient) {
                // Generate a simple 4-digit OTP for local testing
                $otp = sprintf("%04d", mt_rand(1000, 9999));
                
                // Store email and OTP in session
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['otp_generated_time'] = time();
                
                // Display OTP without immediate redirect
                $success_message = "Your OTP is: <strong>" . $otp . "</strong><br>
                    <div class='mt-3'>
                        <a href='create_new_password.php' class='btn btn-success'>
                            Continue to Reset Password
                        </a>
                    </div>";
            } else {
                $error_message = "No patient account found with that email address.";
            }
        } catch (PDOException $e) {
            $error_message = "An error occurred. Please try again.";
            error_log("Patient OTP Generation Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Patient Portal</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --text-dark: #064e3b;
            --text-light: #047857;
            --bg-light: #f0fdf4;
            --bg-soft: #ecfdf5;
            --border-color: #d1fae5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            font-weight: bold;
            border-radius: 20px 20px 20px 20px;
            padding: 1.75rem 2rem;
        }

        .otp-display {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.2em;
        }

        .otp-display strong {
            color: #2e7d32;
            font-size: 1.5em;
            letter-spacing: 3px;
        }

        .form-floating .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            height: calc(3.5rem + 2px);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }

        .btn-outline-secondary {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        .navbar {
            padding: 1rem 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--primary-color);
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .navbar a > img{
            margin-left:-250px;
            margin-right:40px;
        }
        .navnar a{
            font-size:10px;
        }
        a > img{
            width:100px;
            height:100px;
        }
        a{
            text-decoration:none;
            color:black;
        }
        .content{
            margin-top:300px;
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
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top"><img src="" alt="">    
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="barangay_victoria.png" alt="image not found">
                    Barangay Victoria Reyes Dasmariñas Health Center
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>   
            </div>
        </nav>
    <div class="container">
        <div class="row justify-content-center content">
            <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Reset Patient Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="otp-display">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-floating mb-3">
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="name@example.com" 
                                       required>
                                <label for="email">Patient Email Address</label>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg" 
                                        type="submit" 
                                        name="request_otp">
                                    Request OTP
                                </button>
                                <a href="patients_index.php" 
                                   class="btn btn-outline-secondary">
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
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>