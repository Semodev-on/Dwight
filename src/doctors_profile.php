<?php
session_start();
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: doctor_dashboard.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];
$success_message = '';
$error_message = '';

// Fetch doctor's current information
$query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

// Fetch all specializations
$spec_query = "SELECT * FROM specializations WHERE status = 'active' ORDER BY name";
$spec_stmt = $pdo->prepare($spec_query);
$spec_stmt->execute();
$specializations = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specializations']);
    $status = trim($_POST['status']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Check if email already exists for other doctors
        if ($email !== $doctor['email']) {
            $check_email = "SELECT id FROM doctors WHERE email = ? AND id != ?";
            $check_stmt = $pdo->prepare($check_email);
            $check_stmt->execute([$email, $doctor_id]);
            if ($check_stmt->rowCount() > 0) {
                throw new Exception("Email already exists!");
            }
        }

        // Update basic information
        $update_query = "UPDATE doctors SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        specializations = ?,
                        statuses = ? 
                        WHERE id = ?";
        
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone,
            $specialization,
            $status,
            $doctor_id
        ]);

        // Update password if provided
        if (!empty($current_password)) {
            if (password_verify($current_password, $doctor['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_query = "UPDATE doctors SET password = ? WHERE id = ?";
                        $password_stmt = $pdo->prepare($password_query);
                        $password_stmt->execute([$hashed_password, $doctor_id]);
                    } else {
                        throw new Exception("New password must be at least 6 characters long!");
                    }
                } else {
                    throw new Exception("New passwords do not match!");
                }
            } else {
                throw new Exception("Current password is incorrect!");
            }
        }

        // Commit transaction
        $pdo->commit();
        
        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;

        $success_message = "Profile updated successfully!";
        
        // Refresh doctor data
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <script src="js/form-validation.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 56px;
        }
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #fff;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #198754;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .form-select {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
        }
        
        .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        /* Optional: Style different status options with colors */
        #status option[value="active"] {
            color: #198754;
        }
        
        #status option[value="busy"] {
            color: #ffc107;
        }
        
        #status option[value="off"] {
            color: #6c757d;
        }
        
        #status option[value="leave"] {
            color: #dc3545;
        }
        
        #status option[value="on break"] {
            color: #0dcaf0;
        }

        /* Style for specialization dropdown */
        #specializations {
            border-radius: 10px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
        }
        
        #specializations:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        
        #specializations option {
            padding: 10px;
        }
        
        #specializations option:checked {
            background-color: #198754;
            color: white;
        }
                        /* Navbar styling */
                        .navbar {
            background: linear-gradient(135deg, var(--dark-color), var(--secondary-color));
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Sidebar styling */
        .sidebar {
            min-height: calc(100vh - 56px);
            width: 250px;
            background: white;
            box-shadow: 2px 0 5px rgba(16, 185, 129, 0.1);
            position: fixed;
            left: 0;
            padding: 1rem 0;
        }

        .nav-link {
            color: var(--dark-color);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-hospital"></i>  Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-md"></i> 
                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="doctor_profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="doctors_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 d-md-block sidebar">
        <div class="position-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="doctor_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_appointments.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_patients.php">
                        <i class="fas fa-users"></i> My Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_schedule.php">
                        <i class="fas fa-clock"></i> My Schedule
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">My Profile</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="doctorProfileForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($doctor['first_name']); ?>"
                                               required 
                                               pattern="[A-Za-z\s]+" 
                                               oninput="validateLettersOnly(this)" 
                                               title="Please enter letters only">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($doctor['last_name']); ?>"
                                               required 
                                               pattern="[A-Za-z\s]+" 
                                               oninput="validateLettersOnly(this)" 
                                               title="Please enter letters only">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($doctor['email']) ? htmlspecialchars($doctor['email']) : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($doctor['phone']) ? htmlspecialchars($doctor['phone']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="specializations" class="form-label">Specialization</label>
                                    <select class="form-select" id="specializations" name="specializations" required>
                                        <option value="">Select Specialization</option>
                                        <?php foreach ($specializations as $spec): ?>
                                            <option value="<?php echo htmlspecialchars($spec['name']); ?>" 
                                                <?php echo ($doctor['specializations'] === $spec['name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($spec['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted">
                                        Select your medical specialization from the list.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Account Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($doctor['statuses'] === 'active') ? 'selected' : ''; ?>>
                                            Active (Available)
                                        </option>
                                        <option value="busy" <?php echo ($doctor['statuses'] === 'busy') ? 'selected' : ''; ?>>
                                            Busy
                                        </option>
                                        <option value="off" <?php echo ($doctor['statuses'] === 'off') ? 'selected' : ''; ?>>
                                            Off Duty
                                        </option>
                                        <option value="leave" <?php echo ($doctor['statuses'] === 'leave') ? 'selected' : ''; ?>>
                                            On Leave
                                        </option>
                                        <option value="on break" <?php echo ($doctor['statuses'] === 'on break') ? 'selected' : ''; ?>>
                                            On Break
                                        </option>
                                    </select>
                                    <div class="form-text text-muted">
                                        Select your current availability status. This will be visible to administrators and affect appointment scheduling.
                                    </div>
                                </div>

                                <hr>
                                <h5>Change Password</h5>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                        <small class="text-muted">Leave blank to keep current password</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        validateForm('doctorProfileForm');
    });
    </script>
</body>
</html>