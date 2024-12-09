<?php
require_once 'admin_check.php';
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

// Handle admin status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $admin_id = $_POST['admin_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE admin_users SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $admin_id])) {
            $success_message = "Admin status updated successfully!";
        } else {
            $error_message = "Failed to update admin status.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error occurred.";
        error_log($e->getMessage());
    }
}

// Fetch all admin users except the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id != ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Failed to fetch admin users.";
    error_log($e->getMessage());
    $admin_users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - Hospital Management System</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --light-green: #d1fae5;
            --dark-green: #064e3b;
        }

        .navbar {
            background: linear-gradient(135deg, var(--dark-green), var(--secondary-color)) !important;
        }

        .navbar-brand, .nav-link {
            color: white !important;
        }

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

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--light-green);
            color: var(--dark-green);
            font-weight: 600;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge-active {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-inactive {
            background-color: #ef4444;
            color: white;
        }

        .btn-toggle-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Hospital Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_patients.php">Patients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_Doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_Appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="admin_users.php">Admins</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Admin Users Management</h5>
                            </div>
                            <div class="col text-end">
                                <a href="admin_signup.php" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Add New Admin
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_users as $admin): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $admin['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                    <?php echo ucfirst($admin['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                echo $admin['last_login'] 
                                                    ? date('M d, Y H:i', strtotime($admin['last_login']))
                                                    : 'Never';
                                                ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                    <input type="hidden" name="new_status" 
                                                           value="<?php echo $admin['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" name="toggle_status" 
                                                            class="btn btn-sm <?php echo $admin['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?> btn-toggle-status">
                                                        <?php echo $admin['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($admin_users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No other admin users found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html> 