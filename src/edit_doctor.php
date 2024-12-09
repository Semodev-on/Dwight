<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

// Check if doctor ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_Doctors.php");
    exit();
}

$doctor_id = $_GET['id'];

// Fetch doctor details
$doctor_query = "SELECT * FROM doctors WHERE id = ?";
$doctor_stmt = $pdo->prepare($doctor_query);
$doctor_stmt->execute([$doctor_id]);
$doctor = $doctor_stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    header("Location: admin_Doctors.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $specializations = $_POST['specializations'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($specializations)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            // Update doctor with specializations
            $update_query = "UPDATE doctors SET first_name = ?, last_name = ?, email = ?, specializations = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_query);
            
            if ($update_stmt->execute([$first_name, $last_name, $email, $specializations, $doctor_id])) {
                $_SESSION['success_message'] = "Doctor updated successfully!";
                header("Location: admin_Doctors.php");
                exit();
            } else {
                $error_message = "Error updating doctor. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Edit Doctor</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="edit_doctor.php?id=<?php echo $doctor_id; ?>" method="POST">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($doctor['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($doctor['last_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="specializations">Specializations</label>
                <input type="text" class="form-control" id="specializations" name="specializations" value="<?php echo htmlspecialchars($doctor['specializations'] ?? ''); ?>" required>
                <small class="form-text text-muted">Enter the doctor's specializations (e.g., Cardiology, Pediatrics)</small>
            </div>
            <button type="submit" class="btn btn-primary">Update Doctor</button>
        </form>
    </div>
</body>
</html>
