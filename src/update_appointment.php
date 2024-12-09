<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $appointment_id = $_POST['appointment_id'];
        $status = $_POST['status'];
        $appointment_date = $_POST['appointment_date'];

        // Validate inputs
        if (empty($appointment_id) || empty($status) || empty($appointment_date)) {
            throw new Exception("All fields are required");
        }

        // Validate date format
        $date = new DateTime($appointment_date);
        if (!$date) {
            throw new Exception("Invalid date format");
        }

        // Validate status
        $allowed_statuses = ['scheduled', 'completed', 'cancelled'];
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("Invalid status");
        }

        // Update the appointment
        $update_query = "UPDATE appointments 
                        SET status = ?, 
                            appointment_date = ?,
                            updated_at = NOW()
                        WHERE id = ?";
        
        $stmt = $pdo->prepare($update_query);
        $result = $stmt->execute([$status, $appointment_date, $appointment_id]);

        if ($result) {
            $_SESSION['success_message'] = "Appointment updated successfully!";
        } else {
            throw new Exception("Failed to update appointment");
        }

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        error_log("Update appointment error: " . $e->getMessage());
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Redirect back to appointments page
header("Location: admin_Appointments.php");
exit();
?>
