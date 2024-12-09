<?php
session_start();
require_once 'db_connect.php';

// Check if session exists
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: reset_password_patients.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['verify_otp'])) {
            $entered_otp = trim($_POST['otp']);
            $stored_otp = $_SESSION['reset_otp'];
            
            // Verify OTP
            if ($entered_otp === $stored_otp && time() - $_SESSION['otp_generated_time'] <= 900) {
                $_SESSION['otp_verified'] = true;
                $success_message = "OTP verified successfully. Please set your new password.";
            } else {
                throw new Exception("Invalid or expired OTP. Please try again.");
            }
        } elseif (isset($_POST['reset_password'])) {
            if (!isset($_SESSION['otp_verified'])) {
                throw new Exception("Please verify OTP first.");
            }

            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            $email = $_SESSION['reset_email'];

            // Validate password
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Passwords do not match.");
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_password = "UPDATE patients_login 
                              SET password = ?, 
                                  reset_token = NULL, 
                                  reset_token_expiry = NULL
                              WHERE email = ?";
            $stmt = $pdo->prepare($update_password);
            $stmt->execute([$hashed_password, $email]);

            // Clear all session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['otp_generated_time']);
            unset($_SESSION['reset_otp']);

            $success_message = "Password reset successfully! Redirecting to login...";
            header("refresh:2;url=patients_index.php");
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
    <title>Create New Password - Patient Portal</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            font-weight: bold;
            border-radius: 1rem 1rem 0 0;
        }
        a{
            text-decoration:none;
            color:black;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Create New Password</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <?php if (!isset($_SESSION['otp_verified'])): ?>
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
                            <a href="patients_index.php">Back to Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>