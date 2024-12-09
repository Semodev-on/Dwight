<?php
session_start();
require_once 'db_connect.php';

// Default user credentials
$default_username = 'admin';
$default_password = 'admin123';
$error_message = '';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check default credentials first
    if ($username === $default_username && $password === $default_password) {
        $_SESSION['user_id'] = 0;
        $_SESSION['username'] = $default_username;
        $_SESSION['user_type'] = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Check database for other admin users
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = 'admin';
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;        /* Green primary */
            --secondary-color: #059669;      /* Darker green */
            --accent-color: #34d399;         /* Light green */
            --dark-color: #064e3b;           /* Dark green */
            --light-color: #f0fdf4;          /* Very light green */
            --success-color: #059669;        /* Success green */
            --danger-color: #dc2626;         /* Keep red for errors */
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding-top: 76px;
            font-family: 'Inter', sans-serif;
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

        /* Enhanced Card Styling */
        .login{
            margin-top:100px;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            overflow: hidden;
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            border: none;
        }

        .card-header h5 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .card-body {
            padding: 2.5rem;
        }

        /* Form Controls */
        .form-floating > .form-control {
            padding: 1rem 1rem;
            height: calc(3.5rem + 2px);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-floating > label {
            padding: 1rem;
            color: #64748b;
        }

        /* Button Styling */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px -1px rgba(37, 99, 235, 0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
        }

        /* Modern Footer */
        .footer {
            background: linear-gradient(135deg, var(--dark-color), #2d3748);
            color: white;
            padding: 4rem 0;
            margin-top: 6rem;
        }

        .footer h3 {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .social-icons a {
            color: white;
            margin: 0 15px;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            opacity: 0.8;
        }

        .social-icons a:hover {
            color: var(--accent-color);
            opacity: 1;
            transform: translateY(-3px);
        }

        /* Alert Styling */
        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border: none;
            color: var(--danger-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .footer {
                padding: 2rem 0;
                text-align: center;
            }
            
            .social-icons {
                margin-top: 1rem;
            }
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
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-secondary ms-2" href="admin_index.php">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="patients_index.php">Patient Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>  

    <!-- Main Content -->
    <div class="container login">
        <div class="row">
            <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Admin Login
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username" required>
                                <label for="username">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-login" type="submit">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Hospital Management System</h3>
                    <p>Making healthcare accessible and efficient</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Hospital Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>