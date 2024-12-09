<?php
session_start();
require_once 'db_connect.php';

$error_message = '';

// Handle doctor login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        // Fetch doctor by email
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE email = ?");
        $stmt->execute([$email]);
        $doctor = $stmt->fetch();

        if ($doctor && password_verify($password, $doctor['password'])) {
            // Check account status - with fallback to 'active' if status is not set
            $account_status = isset($doctor['account_status']) ? $doctor['account_status'] : 'active';
            
            if ($account_status === 'active') {
                // Set session variables
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['user_type'] = 'doctor';
                $_SESSION['first_name'] = $doctor['first_name'];
                $_SESSION['last_name'] = $doctor['last_name'];
                $_SESSION['email'] = $doctor['email'];
                
                // Update last login timestamp
                $update = $pdo->prepare("UPDATE doctors SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $update->execute([$doctor['id']]);

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
    <title>Doctor Login - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
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
            margin-top:35px;
        }

        a button{
            text-decoration:none;
            color:black;
        }
        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
        }

        .btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transition: all 0.3s ease;
        }
        img{
            width: 75px;
            height: 75px;
            margin-left:-130px;
            margin-right:35px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="doctor_login.php">
                <img src="barangay_victoria.png" alt="logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Back to Main Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center py-3">
                        <h4 class="mb-0">
                           Doctor Login
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                  Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success py-2">
                                  Sign In
                                </button>
                                <a href="doctors_signup.php" class="btn btn-outline-success">
                                   New Doctor? Sign Up
                                </a>
                                <a href="reset_password_doctors.php" class="btn btn-link">
                                  Forgot Password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 