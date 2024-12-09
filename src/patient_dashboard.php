<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as a patient
if (!isset($_SESSION['patient_id'])) {
    error_log("Patient ID is not set in the session.");
    header("Location: patients_index.php");
    exit();
}

// Add this near the top of the file after session_start()
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type'])) {
        try {
            $pdo->beginTransaction();
            
            // First, ensure the patient exists in the patients table
            $check_patient = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
            $check_patient->execute([$_SESSION['patient_id']]);
            $patient = $check_patient->fetch();
            
            if (!$patient) {
                throw new Exception("Patient record not found");
            }
            
            if ($_POST['form_type'] === 'medical_certificate') {
                // First get the doctor's specialization ID by joining with specializations table
                $get_specialization = $pdo->prepare("
                    SELECT s.id as specialization_id
                    FROM doctors d
                    JOIN specializations s ON d.specializations = s.name
                    WHERE d.id = ?
                ");
                $get_specialization->execute([$_POST['doctor_id']]);
                $doctor_spec = $get_specialization->fetch(PDO::FETCH_ASSOC);

                // Handle medical certificate request
                $stmt = $pdo->prepare("INSERT INTO medical_certificates 
                    (patient_id, doctor_id, reason, start_date, end_date, request_date, status, approved_by, appointment_id, specializations) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'pending', NULL, NULL, ?)");
                
                $stmt->execute([
                    $_SESSION['patient_id'],
                    $_POST['doctor_id'],
                    $_POST['reason'],
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $doctor_spec['specialization_id']  // Use the specialization ID instead of name
                ]);
                
            } elseif ($_POST['form_type'] === 'appointment') {
                // Handle appointment booking
                $appointment_datetime = $_POST['appointment_date'] . ' ' . $_POST['appointment_time'];
                
                $stmt = $pdo->prepare("INSERT INTO appointments 
                    (patient_id, doctor_id, appointment_date, reason, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())");
                
                $stmt->execute([
                    $patient['id'], // Use the patient.id instead of login_id
                    $_POST['doctor_id'],
                    $appointment_datetime,
                    $_POST['reason']
                ]);
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = "Your request has been submitted successfully!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error submitting request: " . $e->getMessage();
            error_log("Error in patient request: " . $e->getMessage());
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

try {
    // First, check if patient exists in patients table
    $check_patient = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
    $check_patient->execute([$_SESSION['patient_id']]);
    
    if (!$check_patient->fetch()) {
        // Patient doesn't exist in patients table, get data from patients_login
        $get_login_data = $pdo->prepare("SELECT * FROM patients_login WHERE id = ?");
        $get_login_data->execute([$_SESSION['patient_id']]);
        $login_data = $get_login_data->fetch(PDO::FETCH_ASSOC);
        
        if ($login_data) {
            // Create patient record
            $create_patient = $pdo->prepare("
                INSERT INTO patients (
                    id,
                    first_name,
                    last_name,
                    email,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            
            $create_patient->execute([
                $login_data['id'],
                $login_data['first_name'],
                $login_data['last_name'],
                $login_data['email']
            ]);
            
            error_log("Created patient record for ID: " . $login_data['id']);
        } else {
            throw new Exception("Patient login data not found");
        }
    }

    // Fetch appointments with doctor details
    $appointments_query = "SELECT a.*, 
                          d.first_name as doctor_first_name, 
                          d.last_name as doctor_last_name,
                          d.specializations,
                          d.statuses as doctor_status,
                          a.diagnosis,
                          a.prescription
                          FROM appointments a
                          JOIN doctors d ON a.doctor_id = d.id
                          WHERE a.patient_id = ?
                          ORDER BY a.appointment_date DESC";
    $appointments_stmt = $pdo->prepare($appointments_query);
    $appointments_stmt->execute([$_SESSION['patient_id']]);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update the doctors query to fetch all active doctors with their status
    $doctors_query = "SELECT d.id, 
                            d.first_name, 
                            d.last_name, 
                            d.specializations, 
                            d.statuses, 
                            d.account_statuses 
                     FROM doctors d 
                     WHERE d.account_statuses = 'active' 
                     ORDER BY 
                        CASE d.statuses 
                            WHEN 'active' THEN 1 
                            WHEN 'busy' THEN 2
                            WHEN 'off' THEN 3 
                            WHEN 'leave' THEN 4 
                        END,
                        d.first_name ASC, 
                        d.last_name ASC";
    $doctors_stmt = $pdo->prepare($doctors_query);
    $doctors_stmt->execute();
    $doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($doctors)) {
        error_log("No active doctors found");
    }

    // Fetch medical certificates and requests
    $requests_query = "SELECT mc.id, 
                         mc.patient_id,
                         mc.doctor_id,
                         mc.reason,
                         mc.start_date,
                         mc.end_date,
                         mc.request_date,
                         mc.status,
                         d.first_name as doctor_first_name, 
                         d.last_name as doctor_last_name
                  FROM medical_certificates mc
                  LEFT JOIN doctors d ON mc.doctor_id = d.id
                  WHERE mc.patient_id = ? 
                  ORDER BY mc.request_date DESC";
    $requests_stmt = $pdo->prepare($requests_query);
    $requests_stmt->execute([$_SESSION['patient_id']]);
    $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Use the same data for both tables
    $medical_certificates = $requests;

} catch (Exception $e) {
    error_log("Error in patient_dashboard.php: " . $e->getMessage());
    $appointments = [];
    $doctors = [];
    $requests = [];
    $medical_certificates = [];
    $error_message = "An error occurred while fetching your records.";
}

// Initialize arrays if they're not set
$appointments = $appointments ?? [];
$doctors = $doctors ?? [];
$requests = $requests ?? [];
$medical_certificates = $medical_certificates ?? [];

// Helper function to get status badge class and text
function getDoctorStatusBadge($status) {
    $badges = [
        'active' => ['class' => 'bg-success', 'text' => 'Available'],
        'busy' => ['class' => 'bg-warning', 'text' => 'In Consultation'],
        'off' => ['class' => 'bg-secondary', 'text' => 'Off Duty'],
        'leave' => ['class' => 'bg-danger', 'text' => 'On Leave']
    ];
    return $badges[$status] ?? ['class' => 'bg-secondary', 'text' => 'Unknown'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="barangay_victoria.png" alt="img not found">
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patients_logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestModal">
                        Request Medical Certificate
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#appointmentModal">
                        Book Appointment
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordsModal">
                        View Records
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">My Medical Certificate Requests and Appointments</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests) && empty($appointments)): ?>
                            <div class="alert alert-info">
                                No medical certificate requests or appointments found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="requestsTable" class="table table-striped table-bordered my-3">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Period/Time</th>
                                            <th>Doctor</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Diagnosis</th>    <!-- New column -->
                                            <th>Prescription</th>  <!-- New column -->
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td>Medical Certificate</td>
                                            <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                            <td>
                                                <?php 
                                                if (isset($request['start_date']) && isset($request['end_date'])) {
                                                    echo date('M d', strtotime($request['start_date'])) . ' - ' . 
                                                         date('M d, Y', strtotime($request['end_date']));
                                                }
                                                ?>
                                            </td>
                                            <td>Dr. <?php echo htmlspecialchars($request['doctor_first_name'] . ' ' . $request['doctor_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($request['status']) {
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'warning'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted">N/A</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">N/A</span>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] === 'approved'): ?>
                                                    <button type="button" 
                                                        class="btn btn-primary btn-sm"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#certificateModal"
                                                        data-certificate-id="<?php echo $request['id']; ?>">
                                                        View Certificate
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td>Appointment</td>
                                            <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($appointment['status']) {
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        'pending' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($appointment['diagnosis'])): ?>
                                                    <?php echo htmlspecialchars($appointment['diagnosis']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($appointment['prescription'])): ?>
                                                    <?php echo htmlspecialchars($appointment['prescription']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not available</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Add any actions for appointments if needed -->
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Medical Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="requestForm" method="POST">
                        <input type="hidden" name="form_type" value="medical_certificate">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">Choose a doctor...</option>
                                <?php if (empty($doctors)): ?>
                                    <option value="" disabled>No doctors available at this time</option>
                                <?php else: ?>
                                    <?php foreach ($doctors as $doctor): 
                                        $status = getDoctorStatusBadge($doctor['statuses']);
                                    ?>
                                        <option value="<?php echo $doctor['id']; ?>" 
                                                <?php echo ($doctor['statuses'] !== 'active') ? 'disabled' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            (<?php echo htmlspecialchars($doctor['specializations']); ?>) - 
                                            <span class="badge <?php echo $status['class']; ?>"><?php echo $status['text']; ?></span>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text mt-2">
                                <span class="badge bg-success">Available</span> - Doctor is available for appointments
                                <br>
                                <span class="badge bg-warning">In Consultation</span> - Doctor is currently with a patient
                                <br>
                                <span class="badge bg-secondary">Off Duty</span> - Doctor is not on duty
                                <br>
                                <span class="badge bg-danger">On Leave</span> - Doctor is on leave
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="request_reason" class="form-label">Reason for Request</label>
                            <textarea class="form-control" id="request_reason" name="reason" rows="3" required></textarea>
                            <div class="invalid-feedback">Please provide a reason.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                <div class="invalid-feedback">Please select a start date.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                <div class="invalid-feedback">Please select an end date.</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="appointmentForm" method="POST">
                        <input type="hidden" name="form_type" value="appointment">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">Choose a doctor...</option>
                                <?php if (empty($doctors)): ?>
                                    <option value="" disabled>No doctors available at this time</option>
                                <?php else: ?>
                                    <?php foreach ($doctors as $doctor): 
                                        $status = getDoctorStatusBadge($doctor['statuses']);
                                    ?>
                                        <option value="<?php echo $doctor['id']; ?>" 
                                                <?php echo ($doctor['statuses'] !== 'active') ? 'disabled' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                                            (<?php echo htmlspecialchars($doctor['specializations']); ?>) - 
                                            <span class="badge <?php echo $status['class']; ?>"><?php echo $status['text']; ?></span>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text mt-2">
                                <span class="badge bg-success">Available</span> - Doctor is available for appointments
                                <br>
                                <span class="badge bg-warning">In Consultation</span> - Doctor is currently with a patient
                                <br>
                                <span class="badge bg-secondary">Off Duty</span> - Doctor is not on duty
                                <br>
                                <span class="badge bg-danger">On Leave</span> - Doctor is on leave
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date</label>
                            <input type="date" 
                            class="form-control" 
                            id="appointment_date" 
                            name="appointment_date" 
                            min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                            required>
                        </div>

                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Preferred Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                <option value="">Select time...</option>
                                <?php
                                // Generate time slots from 9 AM to 5 PM
                                $start = strtotime('09:00');
                                $end = strtotime('17:00');
                                $interval = 30 * 60; // 30 minutes interval

                                for ($time = $start; $time <= $end; $time += $interval) {
                                    echo '<option value="' . date('H:i', $time) . '">' . date('h:i A', $time) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Visit</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Book Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this new modal for viewing records -->
    <div class="modal fade" id="recordsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Medical Records History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Tabs for different types of records -->
                    <ul class="nav nav-tabs mb-3" id="recordsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="appointments-tab" data-bs-toggle="tab" 
                                data-bs-target="#appointments" type="button" role="tab">
                                Appointments
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="certificates-tab" data-bs-toggle="tab" 
                                data-bs-target="#certificates" type="button" role="tab">
                                Medical Certificates
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="recordsTabContent">
                        <!-- Appointments Tab -->
                        <div class="tab-pane fade show active" id="appointments" role="tabpanel">
                            <div class="table-responsive">
                                <?php if (empty($appointments)): ?>
                                    <div class="alert alert-info">
                                        No appointments found.
                                    </div>
                                <?php else: ?>
                                    <table id="appointmentsTable" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Diagnosis</th>
                                                <th>Prescription</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($appointment['status']) {
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger',
                                                            'pending' => 'warning',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($appointment['diagnosis'])): ?>
                                                        <?php echo htmlspecialchars($appointment['diagnosis']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not available</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($appointment['prescription'])): ?>
                                                        <?php echo htmlspecialchars($appointment['prescription']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not available</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Medical Certificates Tab -->
                        <div class="tab-pane fade" id="certificates" role="tabpanel">
                            <div class="table-responsive">
                                <?php if (empty($medical_certificates)): ?>
                                    <div class="alert alert-info">
                                        No medical certificates found.
                                    </div>
                                <?php else: ?>
                                    <table id="certificatesTable" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request Date</th>
                                                <th>Period</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($medical_certificates as $cert): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($cert['request_date'])); ?></td>
                                                <td><?php echo date('M d', strtotime($cert['start_date'])) . ' - ' . 
                                                              date('M d, Y', strtotime($cert['end_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($cert['reason']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($cert['status']) {
                                                            'approved' => 'success',
                                                            'rejected' => 'danger',
                                                            default => 'warning'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($cert['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Single Medical Certificate Modal -->
    <div class="modal fade" id="certificateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Medical Certificate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="certificate-content">
                        <!-- Certificate content will be loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printCertificate">
                        Print Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTables with improved configuration
        const commonConfig = {
            "order": [[0, "desc"]],
            "pageLength": 10,
            "responsive": true,
            "language": {
                "emptyTable": "No records found",
                "zeroRecords": "No matching records found",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)",
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries"
            },
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            "autoWidth": false,
            "columnDefs": [
                { "orderable": false, "targets": -1 } // Disable sorting on last column (status)
            ]
        };

        // Initialize tables
        if (document.getElementById('requestsTable')) {
            $('#requestsTable').DataTable({
                ...commonConfig,
                "initComplete": function(settings, json) {
                    $(this).wrap('<div class="dataTables_scroll"></div>');
                }
            });
        }

        // Modal tables initialization
        $('#recordsModal').on('shown.bs.modal', function () {
            ['appointmentsTable', 'certificatesTable'].forEach(tableId => {
                if (document.getElementById(tableId) && !$.fn.DataTable.isDataTable(`#${tableId}`)) {
                    $(`#${tableId}`).DataTable(commonConfig);
                }
            });
        });

        // Cleanup on modal hide
        $('#recordsModal').on('hidden.bs.modal', function () {
            ['appointmentsTable', 'certificatesTable'].forEach(tableId => {
                if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
                    $(`#${tableId}`).DataTable().destroy();
                }
            });
        });

        // Set minimum date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        $('#start_date, #end_date').attr('min', today);

        // Function to validate doctor selection
        function validateDoctorSelection(formId) {
            const doctorSelect = $(formId).find('[name="doctor_id"]');
            if (!doctorSelect.val()) {
                doctorSelect.addClass('is-invalid');
                return false;
            }
            doctorSelect.removeClass('is-invalid');
            return true;
        }

        // Handle medical certificate request form submission
        $('#requestForm').submit(function(e) {
            if (!validateDoctorSelection('#requestForm')) {
                e.preventDefault();
                alert('Please select a doctor');
                return false;
            }
        });

        // Handle appointment form submission
        $('#appointmentForm').submit(function(e) {
            if (!validateDoctorSelection('#appointmentForm')) {
                e.preventDefault();
                alert('Please select a doctor');
                return false;
            }
        });

        // Update validation on doctor selection change
        $('#request_doctor_id, #appointment_doctor_id').change(function() {
            validateDoctorSelection('#' + $(this).closest('form').attr('id'));
        });

        // Clear forms and validation when modals are hidden
        $('#requestModal, #appointmentModal').on('hidden.bs.modal', function() {
            const form = $(this).find('form')[0];
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
        });

        // Handle view certificate button clicks
        $('.view-certificate').click(function() {
            const certificateId = $(this).data('id');
            
            // Load certificate content
            $.ajax({
                url: 'get_medical_certificate.php',
                type: 'GET',
                data: { id: certificateId },
                success: function(response) {
                    $('#certificateContent').html(response);
                    $('#certificateModal').modal('show');
                },
                error: function() {
                    alert('Error loading certificate');
                }
            });
        });

        // Handle certificate modal
        $('#certificateModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const certificateId = button.data('certificate-id');
            const modal = $(this);
            
            // Load certificate content
            $.ajax({
                url: 'get_certificate.php',
                type: 'POST',
                data: { id: certificateId },
                success: function(response) {
                    modal.find('.certificate-content').html(response);
                    // Store certificate ID for printing
                    modal.find('#printCertificate').data('certificate-id', certificateId);
                },
                error: function() {
                    alert('Error loading certificate');
                }
            });
        });

        // Handle print button
        $('#printCertificate').click(function() {
            const certificateId = $(this).data('certificate-id');
            printCertificate(certificateId);
        });

        // Clear modal content when closed
        $('#certificateModal').on('hidden.bs.modal', function () {
            $(this).find('.certificate-content').html('');
        });
    });

    // Print function
    function printCertificate(certificateId) {
        const content = document.querySelector('.certificate-content');
        const printWindow = window.open('', '', 'height=800,width=800');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Medical Certificate</title>
                <link href="css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { 
                        padding: 40px; 
                        font-family: Arial, sans-serif;
                    }
                    .certificate-content {
                        max-width: 800px;
                        margin: 0 auto;
                        padding: 40px;
                    }
                    /* Add other necessary styles */
                </style>
            </head>
            <body>
                ${content.outerHTML}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        printWindow.onload = function() {
            printWindow.focus();
            printWindow.print();
            setTimeout(function() { printWindow.close(); }, 250);
        };
    }
    </script>

    <style>
    /* Update DataTables styling to use green theme */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--bs-success) !important;
        border-color: var(--bs-success) !important;
        color: white !important;
    }

    /* Add custom green theme variables */
    :root {
        --theme-green: #198754;
        --theme-green-light: #209c61;
        --theme-green-lighter: #e8f5e9;
    }

    /* Additional green theme styles */
    .table thead th {
        background-color: var(--theme-green-lighter);
        border-bottom: 2px solid var(--theme-green);
    }

    .nav-tabs .nav-link.active {
        color: var(--theme-green);
        border-bottom-color: var(--theme-green);
    }

    .nav-tabs .nav-link:hover {
        border-bottom-color: var(--theme-green-light);
    }

    /* Update focus states for form elements */
    .form-control:focus,
    .form-select:focus {
        border-color: var(--theme-green);
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }

    /* Add these to your existing styles */
    .certificate-content {
        padding: 20px;
        background: #fff;
    }

    .signature-line {
        border-top: 1px solid #000;
        width: 200px;
        margin: 20px auto 10px;
    }

    @media print {
        .modal-footer,
        .btn-close,
        .no-print {
            display: none !important;
        }
        
        .certificate-content {
            padding: 20px;
        }
    }

    /* Certificate styles */
    .certificate-content {
        padding: 40px;
        background: #fff;
        border: 2px solid #ddd;
        border-radius: 8px;
    }

    .certificate-title {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .certificate-subtitle {
        color: #666;
        font-size: 16px;
    }

    .patient-info, .medical-info {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .signature-line {
        border-top: 2px solid #000;
        width: 200px;
        margin: 20px auto 10px;
    }
    

    @media print {
        .modal-footer,
        .btn-close,
        .no-print {
            display: none !important;
        }
        
        .certificate-content {
            border: none;
        }

        .patient-info, .medical-info {
            background-color: transparent !important;
        }
    }

    /* Certificate Styles */
    .certificate-content {
        padding: 40px;
        background: #fff;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }

    .certificate-title {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 15px;
    }

    .clinic-info {
        color: #666;
        font-size: 16px;
    }

    .patient-info, .medical-info {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .signature-section {
        margin-top: 60px;
    }

    .signature-line {
        border-top: 2px solid #000;
        width: 200px;
        margin: 20px auto 10px;
    }

    .doctor-specialization {
        color: #666;
        font-style: italic;
        margin-bottom: 0;
    }

    .license-number {
        font-size: 0.9em;
        color: #666;
    }

    a{
        text-decoration:none;
        color:black;
    }

    /* Print Styles */
    @media print {
        .modal-footer,
        .btn-close,
        .no-print {
            display: none !important;
        }
        
        .certificate-content {
            border: none;
        }

        .patient-info, .medical-info {
            background-color: transparent !important;
            padding: 15px 0;
        }

        .doctor-specialization,
        .license-number {
            color: #000 !important;
        }
    }
    img{
        width: 75px;
        height: 75px;
        margin-left:50px;
    }
    </style>
</body>
</html>