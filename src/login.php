<?php
session_start();
require_once 'db_connect.php';

// Default admin credentials
$default_username = 'admin';
$default_password = 'admin123';
$error_message = '';

// Redirect if already logged in
if (isset($_SESSION['user_type'])) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            exit();
        case 'doctor':
            if (isset($_SESSION['doctor_id'])) {
                header("Location: doctor_dashboard.php");
                exit();
            }
            break;
    }
}

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'admin') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // First check default admin credentials
        if ($username === $default_username && $password === $default_password) {
            $_SESSION['user_id'] = 0;
            $_SESSION['username'] = $default_username;
            $_SESSION['user_type'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        }

        // Then check database for admin users
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error_message = "Database error occurred. Please try again.";
    }
}

// Handle doctor login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_type']) && $_POST['login_type'] === 'doctor') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        // Fetch doctor by email
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE email = ?");
        $stmt->execute([$email]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doctor && password_verify($password, $doctor['password'])) {
            // Check account status
            if ($doctor['account_statuses'] === 'active') {
                // Set session variables
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['user_type'] = 'doctor';
                $_SESSION['first_name'] = $doctor['first_name'];
                $_SESSION['last_name'] = $doctor['last_name'];
                $_SESSION['email'] = $doctor['email'];
                
                // Update last login timestamp
                try {
                    $update = $pdo->prepare("UPDATE doctors SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $update->execute([$doctor['id']]);
                } catch (PDOException $e) {
                    error_log("Failed to update last login: " . $e->getMessage());
                }

                // Clear any existing error messages
                unset($_SESSION['error_message']);
                
                // Redirect to doctor dashboard
                header("Location: doctor_dashboard.php");
                exit();
            } else {
                $error_message = "Your account is pending approval. Please wait for admin activation.";
            }
        } else {
            $error_message = "Invalid email or password";
        }
    } catch (PDOException $e) {
        error_log("Doctor login error: " . $e->getMessage());
        $error_message = "Database error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --dark-color: #064e3b;
            --light-color: #f0fdf4;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding-top: 76px;
            font-family: 'Inter', sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .login-container {
            max-width: 900px;
            margin: 2rem auto;
            margin-top:100px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border: none;
            border-radius: 20px 20px 0 0;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        .nav-tabs {
            border: none;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active  {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
            margin-left:-265px;
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top"><img src="" alt="">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="barangay_victoria.png" alt="image not found">
                    Barangay Victoria Reyes Dasmariñas Health Center
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse pat_login" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="btn btn-success ms-2" href="patients_index.php">Patient Login</a>
                        </li>
                    </ul>
                </div>
            </div>
    </nav>
    <!-- Main Content -->
    <div class="container login-container">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0">Welcome to Hospital Management System</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs nav-fill" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admin-tab" data-bs-toggle="tab" 
                            data-bs-target="#admin-login" type="button" role="tab">
                            Admin Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="doctor-tab" data-bs-toggle="tab" 
                            data-bs-target="#doctor-login" type="button" role="tab">
                            Doctor Login
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="loginTabsContent">
                    <!-- Admin Login Form -->
                    <div class="tab-pane fade show active" id="admin-login" role="tabpanel">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="login_type" value="admin">
                            <div class="mb-3">
                                <label for="admin-username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="admin-username" 
                                       name="username" required>
                            </div>
                            <div class="mb-4">
                                <label for="admin-password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="admin-password" 
                                       name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-login">
                                   Sign In as Admin
                                </button>
                                <a href="admin_signup.php" class="btn btn-outline-success">
                                   Create Admin Account
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Doctor Login Form -->
                    <div class="tab-pane fade" id="doctor-login" role="tabpanel">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <input type="hidden" name="login_type" value="doctor">
                            <div class="mb-3">
                                <label for="doctor-email" class="form-label">
                                    Email
                                </label>
                                <input type="email" class="form-control" id="doctor-email" 
                                    name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="doctor-password" class="form-label">
                                    Password
                                </label>
                                <input type="password" class="form-control" id="doctor-password" 
                                       name="password" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-login">
                                    Sign In as Doctor
                                </button>
                                <a href="doctors_signup.php" class="btn btn-outline-success">
                                   New Doctor? Sign Up
                                </a>
                                <div class="text-center mt-3">
                                    <a href="reset_password_doctors.php" class="btn btn-success btn-submit text-uppercase fw-bold">Forgot Password</a>
                                </div>
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
        // Initialize Bootstrap Modal
        const modalElement = document.getElementById('createAdminModal');
        const modal = new bootstrap.Modal(modalElement);

        // Form validation
        const adminSignupForm = document.getElementById('adminSignupForm');
        if (adminSignupForm) {
            adminSignupForm.addEventListener('submit', function(e) {
                const username = document.getElementById('signup-username').value.trim();
                const password = document.getElementById('signup-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                const adminKey = document.getElementById('admin-key').value;
                
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
                    return;
                }
            });
        }

        // Handle modal events
        if (modalElement) {
            modalElement.addEventListener('shown.bs.modal', function () {
                document.getElementById('signup-username').focus();
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                if (adminSignupForm) {
                    adminSignupForm.reset();
                }
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => alert.remove());
            });
        }

        // Show modal if there was an error with admin signup
        <?php if (isset($error_message) && isset($_POST['login_type']) && $_POST['login_type'] === 'admin_signup'): ?>
            modal.show();
        <?php endif; ?>
    });
    </script>
</body>
</html> 