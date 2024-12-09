<?php
session_start();

// Check if user is logged in and is doctor
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    // Log the session data for debugging
    error_log('Doctor Check Failed - Session Data: ' . print_r($_SESSION, true));
    header("Location: login.php");
    exit();
}

// Get doctor information
$doctor_id = $_SESSION['doctor_id'];

try {
    require_once 'db_connect.php';
    
    // Updated query to check both id and account status
    $query = "SELECT * FROM doctors WHERE id = ? AND account_statuses = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    // If doctor not found or not active
    if (!$doctor) {
        // Log the error
        error_log("Doctor check failed - Doctor ID: $doctor_id not found or not active");
        // Clear session and redirect
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Store additional doctor information in session if needed
    $_SESSION['doctor_name'] = $doctor['first_name'] . ' ' . $doctor['last_name'];
    $_SESSION['doctor_email'] = $doctor['email'];
    $_SESSION['doctor_specialization'] = $doctor['specializations'];

} catch (PDOException $e) {
    error_log("Doctor check error: " . $e->getMessage());
    header("Location: login.php");
    exit();
}
?> 