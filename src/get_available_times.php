<?php
session_start();
require_once 'db_connect.php';
require_once 'config/clinic_settings.php';

// Check if user is logged in
if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get parameters
$doctor_id = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null;

// Validate parameters
if (!$doctor_id || !$date) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    // Define clinic hours (8 AM to 5 PM)
    $clinic_start = 8; // 8 AM
    $clinic_end = 17;  // 5 PM
    $appointment_duration = 30; // 30 minutes per appointment
    $available_times = [];

    // Get doctor's existing appointments for the selected date
    $query = "SELECT TIME_FORMAT(appointment_date, '%H:%i') as booked_time 
              FROM appointments 
              WHERE doctor_id = ? 
              AND DATE(appointment_date) = ? 
              AND status NOT IN ('cancelled')";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$doctor_id, $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check if selected date is a weekend
    $weekend = (date('N', strtotime($date)) >= 6);
    
    // Get doctor's status for the selected date
    $status_query = "SELECT status FROM doctors WHERE id = ?";
    $status_stmt = $pdo->prepare($status_query);
    $status_stmt->execute([$doctor_id]);
    $doctor_status = $status_stmt->fetchColumn();

    // If it's not a weekend and doctor is active
    if (!$weekend && $doctor_status === 'active') {
        // Generate time slots
        for ($hour = CLINIC_START_HOUR; $hour < CLINIC_END_HOUR; $hour++) {
            // Skip lunch break
            if ($hour >= LUNCH_START_HOUR && $hour < LUNCH_END_HOUR) {
                continue;
            }
            
            for ($minute = 0; $minute < 60; $minute += APPOINTMENT_DURATION) {
                // Format time slot
                $time = sprintf('%02d:%02d', $hour, $minute);
                
                // Check if slot is available
                if (!in_array($time, $booked_slots)) {
                    // Format for display
                    $display_time = date('h:i A', strtotime($time));
                    
                    // Only add future time slots for today
                    if ($date === date('Y-m-d')) {
                        if (strtotime($date . ' ' . $time) > time()) {
                            $available_times[] = $display_time;
                        }
                    } else {
                        $available_times[] = $display_time;
                    }
                }
            }
        }
    }

    // Return available time slots
    echo json_encode($available_times);

} catch (PDOException $e) {
    error_log("Error in get_available_times.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
