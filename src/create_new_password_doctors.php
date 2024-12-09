<?php
session_start();
require_once 'db_connect.php';

// Check if session exists
if (!isset($_SESSION['doctor_reset_email']) || !isset($_SESSION['doctor_reset_otp'])) {
    header("Location: reset_password_doctors.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['verify_otp'])) {
            $entered_otp = trim($_POST['otp']);
            $stored_otp = $_SESSION['doctor_reset_otp'];
            
            // Verify OTP
            if ($entered_otp === $stored_otp && time() - $_SESSION['doctor_otp_generated_time'] <= 900) {
                $_SESSION['doctor_otp_verified'] = true;
                $success_message = "OTP verified successfully. Please set your new password.";
            } else {
                throw new Exception("Invalid or expired OTP. Please try again.");
            }
        } elseif (isset($_POST['reset_password'])) {
            if (!isset($_SESSION['doctor_otp_verified'])) {
                throw new Exception("Please verify OTP first.");
            }

            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            $email = $_SESSION['doctor_reset_email'];

            // Validate password
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Passwords do not match.");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in doctors table
            $update_password = "UPDATE doctors 
                              SET password = ? 
                              WHERE email = ?";
            $stmt = $pdo->prepare($update_password);
            
            if ($stmt->execute([$hashed_password, $email])) {
                // Clear all session variables
                unset($_SESSION['doctor_reset_email']);
                unset($_SESSION['doctor_otp_verified']);
                unset($_SESSION['doctor_otp_generated_time']);
                unset($_SESSION['doctor_reset_otp']);

                $success_message = "Password reset successfully!";
                
                // Add JavaScript for redirect
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000); // Redirect after 2 seconds
                </script>";
            } else {
                throw new Exception("Failed to reset password. Please try again.");
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - Doctor Portal</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --success-color: #34d399;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #064e3b;
            --light-color: #f0fdf4;
            --border-color: #d1fae5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            font-weight: bold;
            border-radius: 20px 20px 0 0;
            padding: 1.75rem 2rem;
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
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-md me-2"></i>
                            Create New Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                                <div class="mt-2">
                                    <small>Redirecting to login page...</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!isset($_SESSION['doctor_otp_verified'])): ?>
                            <!-- OTP Verification Form -->
                            <form method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="otp" name="otp" 
                                           required pattern="[0-9]{4}" maxlength="4">
                                    <label for="otp">Enter OTP</label>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-submit" type="submit" name="verify_otp">
                                        Verify OTP
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- New Password Form -->
                            <form method="POST">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required minlength="6">
                                    <label for="new_password">New Password</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required minlength="6">
                                    <label for="confirm_password">Confirm Password</label>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-submit" type="submit" name="reset_password">
                                        Reset Password
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="login.php">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>