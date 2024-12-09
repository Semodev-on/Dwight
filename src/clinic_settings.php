<?php
// Clinic operation settings
define('CLINIC_START_HOUR', 8);  // 8 AM
define('CLINIC_END_HOUR', 17);   // 5 PM
define('LUNCH_START_HOUR', 12);  // 12 PM
define('LUNCH_END_HOUR', 13);    // 1 PM
define('APPOINTMENT_DURATION', 30); // 30 minutes

// Appointment status constants
define('STATUS_PENDING', 'pending');
define('STATUS_CONFIRMED', 'confirmed');
define('STATUS_CANCELLED', 'cancelled');
define('STATUS_COMPLETED', 'completed');

// Doctor status constants
define('DOCTOR_STATUS_ACTIVE', 'active');
define('DOCTOR_STATUS_BUSY', 'busy');
define('DOCTOR_STATUS_OFF', 'off');
define('DOCTOR_STATUS_LEAVE', 'leave');