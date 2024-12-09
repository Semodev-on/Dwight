<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --light-green: #d1fae5;
            --dark-green: #064e3b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            min-height: 100vh;
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--dark-green), var(--secondary-color)) !important;
            padding: 1rem 0;
        }

        .navbar-brand, .nav-link {
            color: white !important;
        }

        .nav-link {
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }

        .nav-item.active .nav-link {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2);
        }

        /* Table styling */
        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--light-green);
            color: var(--dark-green);
            font-weight: 600;
        }

        /* Button styling */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        /* Container spacing */
        .main-container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        /* Alert styling */
        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-success {
            background-color: var(--light-green);
            color: var(--dark-green);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-hospital-user me-2"></i>
                Hospital Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_patients.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="admin_patients.php">Patients</a>
                    </li>
                    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_Doctors.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="admin_Doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_Appointments.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="admin_Appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
                        <a class="nav-link" href="admin_users.php">Admins</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="admin_profile.php">
                                <i class="fas fa-user-cog me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="admin_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container"> 