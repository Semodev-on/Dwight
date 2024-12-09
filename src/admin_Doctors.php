
<?php
require_once 'admin_check.php';
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Define doctor statuses
$doctor_statuses = [
    'active' => 'Available',
    'busy' => 'In Consultation',
    'off' => 'Off Duty',
    'leave' => 'On Leave',
    'on break' => 'On Break'
];

// Define doctor account statuses
$account_statuses = [
    'pending' => 'Pending Approval',
    'active' => 'Account Active'
];

// At the top of the file, add the status update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor_status'])) {
    $doctor_id = $_POST['doctor_id'];
    $new_status = $_POST['doctor_status'];
    
    // Validate the status
    if (!array_key_exists($new_status, $doctor_statuses)) {
        $_SESSION['error_message'] = "Invalid status selected.";
    } else {
        try {
            $update_query = "UPDATE doctors SET statuses = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$new_status, $doctor_id]);
            $_SESSION['success_message'] = "Doctor status updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating doctor status: " . $e->getMessage();
        }
    }
    header("Location: admin_Doctors.php");
    exit();
}

// Fetch all doctors
$doctors_query = "SELECT 
    d.*,
    COALESCE(NULLIF(d.statuses, ''), 'active') as status,
    COALESCE(NULLIF(d.account_statuses, ''), 'pending') as account_status,
    COALESCE(NULLIF(d.specializations, ''), 'Not Specified') as specialization
    FROM doctors d
    ORDER BY d.first_name ASC, d.last_name ASC";
$doctors_stmt = $pdo->query($doctors_query);
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle doctor deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doctor'])) {
    $doctor_id = $_POST['doctor_id'];
    try {
        $delete_query = "DELETE FROM doctors WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_query);
        $delete_stmt->execute([$doctor_id]);
        $_SESSION['success_message'] = "Doctor deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error deleting doctor: " . $e->getMessage();
    }
    header("Location: admin_Doctors.php");
    exit();
}

// Handle doctor activation/status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $doctor_id = $_POST['doctor_id'];
    $new_status = $_POST['account_status'];
    
    // Validate the status
    if (!array_key_exists($new_status, $account_statuses)) {
        $_SESSION['error_message'] = "Invalid status selected.";
        header("Location: admin_Doctors.php");
        exit();
    }
    
    try {
        $update_query = "UPDATE doctors SET account_statuses = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$new_status, $doctor_id]);
        
        $status_messages = [
            'active' => 'Doctor account activated successfully.',
            'pending' => 'Doctor status set to pending.'
        ];
        
        $_SESSION['success_message'] = $status_messages[$new_status] ?? "Doctor status updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating doctor status: " . $e->getMessage();
    }
    header("Location: admin_Doctors.php");
    exit();
}

// Check for success or error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - HMS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #059669;      /* Main green */
            --secondary-color: #34d399;    /* Lighter green */
            --success-color: #10b981;      /* Success green */
            --warning-color: #f59e0b;      /* Warning orange */
            --danger-color: #ef4444;       /* Danger red */
            --dark-color: #1f2937;         /* Dark text */
            --light-color: #f3f4f6;        /* Light background */
            --hover-color: #047857;        /* Darker green for hover */
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            height: calc(100vh - 70px);
            padding: 2rem 1rem;
            position: fixed;
            width: 250px;
        }

        .nav-link {
            color: var(--dark-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 500;
        }

        .table td {
            background: white;
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            transform: translateY(-2px);
        }

        /* Button Styles */
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-add {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-add:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .btn-edit {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
            border: none;
        }

        /* Doctor Info */
        .doctor-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .doctor-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        /* Status Styles */
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--primary-color) !important;
            opacity: 0.8;
        }

        .badge.bg-danger {
            background-color: var(--danger-color) !important;
            opacity: 1;
        }

        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        /* Form Elements */
        .form-select {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 0.875rem;
            background-color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn-activate {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
        }

        .btn-activate:hover {
            background: var(--hover-color);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.1);
            border-left: 4px solid var(--primary-color);
            color: var(--primary-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* Add these to your existing styles */
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .gap-2 {
            gap: 0.5rem;
        }

        /* Update status dropdown */
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(5, 150, 105, 0.25);
        }

        /* Add hover effect for the status dropdown */
        .form-select option {
            padding: 8px;
        }
        img{
            width: 95px;
            height: 95px;
            margin-right:45px;
            margin-left:50px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <img src="barangay_victoria.png" alt="">
                HMS Doctors
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="admin_logout.php" class="btn btn-outline-danger btn-sm">
                   Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    Dashboard
                </a>
                <a class="nav-link active" href="admin_Doctors.php">
                    Doctors
                </a>
                <a class="nav-link" href="admin_patients.php">
                    Patients
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Doctor Management</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td>
                                        <div class="doctor-info">
                                            <div class="doctor-avatar">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                                    <?php 
                                                        $current_status = $doctor['account_status'] ?? 'pending';
                                                        $badge_class = match($current_status) {
                                                            'active' => 'bg-success',
                                                            default => 'bg-warning'
                                                        };
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?> ms-2">
                                                        <?php echo $account_statuses[$current_status]; ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($doctor['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($doctor['specialization'] ?? 'Not Specified'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                    <td>
                                        <?php 
                                            $current_status = $doctor['statuses']; // Get the current status from database
                                            $status_badge_class = match($current_status) {
                                                'active' => 'bg-success',
                                                'busy' => 'bg-warning',
                                                'off' => 'bg-secondary',
                                                'leave' => 'bg-danger',
                                                'on break' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <form action="admin_Doctors.php" method="POST" class="d-inline">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                            <select name="doctor_status" class="form-select form-select-sm" 
                                                    onchange="this.form.submit()" 
                                                    style="min-width: 150px;">
                                                <?php foreach ($doctor_statuses as $status_key => $status_label): ?>
                                                    <option value="<?php echo $status_key; ?>" 
                                                            <?php echo ($current_status === $status_key) ? 'selected' : ''; ?>>
                                                        <?php echo $status_label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="update_doctor_status" value="1">
                                        </form>
                                        <span class="badge <?php echo $status_badge_class; ?> ms-2">
                                            <?php echo $doctor_statuses[$current_status] ?? 'Unknown'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            
                                            <!-- Status Dropdown -->
                                            <form action="admin_Doctors.php" method="POST" class="d-inline">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                                <div class="input-group input-group-sm">
                                                    <select name="account_status" class="form-select form-select-sm" 
                                                            onchange="this.form.submit()" 
                                                            style="min-width: 150px;">
                                                        <?php foreach ($account_statuses as $status_key => $status_label): ?>
                                                            <option value="<?php echo $status_key; ?>" 
                                                                    <?php echo ($doctor['account_status'] === $status_key) ? 'selected' : ''; ?>>
                                                                <?php echo $status_label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </div>
                                            </form>

                                            <form action="admin_Doctors.php" method="POST" class="d-inline">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                                <button type="submit" name="delete_doctor" class="btn btn-delete btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this doctor?')">
                                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> 
            </div>
        </div>
    </div>

    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>