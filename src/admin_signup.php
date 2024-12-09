<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

// Handle admin signup
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_key = trim($_POST['admin_key']);
    
    if ($admin_key !== 'HMSADMIN') {
        $error_message = "Invalid admin creation key";
    } elseif (strlen($username) < 4) {
        $error_message = "Username must be at least 4 characters long";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error_message = "Username already exists";
            } else {
                // Create new admin user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, status) VALUES (?, ?, 'active')");
                
                if ($stmt->execute([$username, $hashed_password])) {
                    $success_message = "Admin account created successfully! You can now login.";
                } else {
                    $error_message = "Failed to create account. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error_message = "Database error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <script src="js/form-validation.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding-top: 76px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }

        .form-text {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hospital-user me-2"></i>HMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Back to Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>Create Admin Account
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="adminSignupForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" 
                                       name="username" required minlength="4" pattern="[A-Za-z\s]+" oninput="validateLettersOnly(this)" title="Please enter letters only">
                                <div class="form-text">Must be at least 4 characters long</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" 
                                       name="password" required minlength="8" pattern="[A-Za-z\s]+" oninput="validateLettersOnly(this)" title="Please enter letters only">
                                <div class="form-text">Must be at least 8 characters long</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirm Password
                                </label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required pattern="[A-Za-z\s]+" oninput="validateLettersOnly(this)" title="Please enter letters only">
                            </div>

                            <div class="mb-4">
                                <label for="admin_key" class="form-label">
                                    <i class="fas fa-key me-2"></i>Admin Creation Key
                                </label>
                                <input type="password" class="form-control" id="admin_key" 
                                       name="admin_key" required>
                                <div class="form-text">Enter the admin creation key (HMSADMIN)</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
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
        const form = document.getElementById('adminSignupForm');
        
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const adminKey = document.getElementById('admin_key').value;
            
            let isValid = true;
            let errorMessage = '';

            if (username.length < 4) {
                isValid = false;
                errorMessage = 'Username must be at least 4 characters long';
            } else if (password.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long';
            } else if (password !== confirmPassword) {
                isValid = false;
                errorMessage = 'Passwords do not match!';
            } else if (!adminKey) {
                isValid = false;
                errorMessage = 'Admin creation key is required';
            }

            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
            }
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        validateForm('adminSignupForm');
    });
    </script>
</body>
</html> 