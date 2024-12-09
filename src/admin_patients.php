<?php
require_once 'admin_check.php';
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle patient deletion
if (isset($_POST['delete_patient'])) {
    $patient_id = $_POST['patient_id'];
    try {
        $pdo->beginTransaction();
        
        // Get the patients_login_id first
        $get_login_id_query = "SELECT patients_login_id FROM patients WHERE id = ?";
        $get_login_stmt = $pdo->prepare($get_login_id_query);
        $get_login_stmt->execute([$patient_id]);
        $patients_login_id = $get_login_stmt->fetchColumn();
       
        // Delete related records first
        $delete_complaints_query = "DELETE FROM complaints WHERE patient_id = ?";
        $delete_complaints_stmt = $pdo->prepare($delete_complaints_query);
        $delete_complaints_stmt->execute([$patient_id]);
        
        $delete_prescriptions_query = "DELETE FROM prescriptions WHERE patient_id = ?";
        $delete_prescriptions_stmt = $pdo->prepare($delete_prescriptions_query);
        $delete_prescriptions_stmt->execute([$patient_id]);
        
        $delete_medical_status_query = "DELETE FROM medical_status WHERE patient_id = ?";
        $delete_medical_status_stmt = $pdo->prepare($delete_medical_status_query);
        $delete_medical_status_stmt->execute([$patient_id]);
        
        // Delete from patients table
        $delete_patient_query = "DELETE FROM patients WHERE id = ?";
        $delete_patient_stmt = $pdo->prepare($delete_patient_query);
        $delete_patient_stmt->execute([$patient_id]);

        // Finally delete from patients_login
        if ($patients_login_id) {
            $delete_login_query = "DELETE FROM patients_login WHERE id = ?";
            $delete_login_stmt = $pdo->prepare($delete_login_query);
            $delete_login_stmt->execute([$patients_login_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Patient deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error deleting patient: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch prescriptions for each patient
$prescriptions = [];
$prescriptions_query = "SELECT patient_id, COUNT(*) as prescription_count FROM prescriptions GROUP BY patient_id";
$prescriptions_stmt = $pdo->query($prescriptions_query);
while ($row = $prescriptions_stmt->fetch()) {
    $prescriptions[$row['patient_id']] = $row['prescription_count'];
}

// Fetch medical status count for each patient
$medical_status = [];
$medical_status_query = "SELECT patient_id, COUNT(*) as status_count FROM medical_status GROUP BY patient_id";
$medical_status_stmt = $pdo->query($medical_status_query);
while ($row = $medical_status_stmt->fetch()) {
    $medical_status[$row['patient_id']] = $row['status_count'];
}

// Fetch illness/symptom count for each patient
$illness_symptoms = [];
$illness_symptoms_query = "SELECT patient_id, COUNT(*) as illness_count FROM complaints GROUP BY patient_id";
$illness_symptoms_stmt = $pdo->query($illness_symptoms_query);
while ($row = $illness_symptoms_stmt->fetch()) {
    $illness_symptoms[$row['patient_id']] = $row['illness_count'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_patient'])) {
    $patient_id = $_POST['patient_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $civil_status = $_POST['civil_status'];

    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($phone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }

    try {
        $update_query = "UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, phone = ?, email = ?, gender = ?, civil_status = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$first_name, $last_name, $date_of_birth, $phone, $email, $gender, $civil_status, $patient_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient updated successfully!'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating patient: ' . $e->getMessage()
        ]);
    }
    exit();
}

if (isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $prescription_text = $_POST['prescription_text'];
    
    try {
        // Check if patient exists
        $check_patient = "SELECT id FROM patients WHERE id = ?";
        $check_stmt = $pdo->prepare($check_patient);
        $check_stmt->execute([$patient_id]);
        
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
            exit();
        }

        $add_prescription_query = "INSERT INTO prescriptions (patient_id, prescription_text, created_at) 
                                 VALUES (?, ?, CURRENT_TIMESTAMP)";
        $add_prescription_stmt = $pdo->prepare($add_prescription_query);
        $add_prescription_stmt->execute([$patient_id, $prescription_text]);
        
        // Fetch updated prescription count
        $count_query = "SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = ?";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute([$patient_id]);
        $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Fetch all prescriptions for this patient
        $prescriptions_query = "SELECT prescription_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date 
                              FROM prescriptions 
                              WHERE patient_id = ? 
                              ORDER BY created_at DESC";
        $prescriptions_stmt = $pdo->prepare($prescriptions_query);
        $prescriptions_stmt->execute([$patient_id]);
        $prescriptions_list = $prescriptions_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Prescription added successfully',
            'new_count' => $new_count,
            'prescriptions' => $prescriptions_list
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding prescription: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_POST['add_complaint'])) {
    $patient_id = $_POST['patient_id'];
    $complaint_text = $_POST['complaint_text'];
    
    try {
        // Check if patient exists
        $check_patient = "SELECT id FROM patients WHERE id = ?";
        $check_stmt = $pdo->prepare($check_patient);
        $check_stmt->execute([$patient_id]);
        
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
            exit();
        }

        $add_complaint_query = "INSERT INTO complaints (patient_id, complaint_text, created_at) 
                              VALUES (?, ?, CURRENT_TIMESTAMP)";
        $add_complaint_stmt = $pdo->prepare($add_complaint_query);
        $add_complaint_stmt->execute([$patient_id, $complaint_text]);
        
        // Fetch updated complaint count
        $count_query = "SELECT COUNT(*) as count FROM complaints WHERE patient_id = ?";
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute([$patient_id]);
        $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Illness/Symptom added successfully',
            'new_count' => $new_count
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding illness/symptom: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_POST['add_medical_status'])) {
    $patient_id = $_POST['patient_id'];
    $medical_status_text = $_POST['medical_status'];
    $add_medical_status_query = "INSERT INTO medical_status (patient_id, status) VALUES (?, ?)";
    $add_medical_status_stmt = $pdo->prepare($add_medical_status_query);
    $add_medical_status_stmt->execute([$patient_id, $medical_status_text]);
    echo json_encode(['success' => true, 'message' => 'Medical status added successfully']);
    exit();
}

$patients_query = "SELECT * FROM patients";
$patients_stmt = $pdo->query($patients_query);
$patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

$complaints = [];
$complaints_query = "SELECT patient_id, COUNT(*) as complaint_count FROM complaints GROUP BY patient_id";
$complaints_stmt = $pdo->query($complaints_query);
while ($row = $complaints_stmt->fetch()) {
    $complaints[$row['patient_id']] = $row['complaint_count'];
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}

$edit_patient = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM patients WHERE id = ?";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->execute([$edit_id]);
    $edit_patient = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

function getMedicalStatusCount($patient_id) {
    global $medical_status;
    return isset($medical_status[$patient_id]) ? $medical_status[$patient_id] : 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - HMS</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* Navbar styling */
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

        /* Sidebar styling */
        .list-group-item:hover, .list-group-item.active {
            background: var(--primary-color);
            color: white;
            transition:0.3s;
        }

        /* Card styling */
    
        .card-header {
            background: var(--primary-color);
            color: white;
        }

        /* Button styling */
        .btn-add-patient {
            background: var(--primary-color);
            color: white;
        }

        .btn-add-patient:hover {
            background: var(--secondary-color);
            color: white;
        }

        /* Form Controls */
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        /* Modal styling */
        .modal-header {
            background: var(--primary-color);
            color: white;
        }

     
        #patientsTable th {
            background: var(--light-color);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        #patientsTable td {
            background: white;
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        #patientsTable tbody tr {
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
        }

        #patientsTable tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        /* Action Buttons in Table */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Status Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Add Patient Button */
        .btn-add-patient {
            background: var(--primary-color);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-add-patient:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            color: white;
        }

        /* Responsive Table Styling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #patientsTable {
            border-collapse: separate;
            border-spacing: 0 0.8rem;
            width: 100%;
            min-width: 1000px; /* Ensure minimum width for content */
        }

        #patientsTable th {
            background: var(--light-color);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        #patientsTable td {
            background: white;
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        /* Responsive breakpoints */
        @media (max-width: 992px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .btn-add-patient {
                width: 100%;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 768px) {
            #patientsTable {
                font-size: 0.9rem;
            }

            #patientsTable th,
            #patientsTable td {
                padding: 0.75rem 0.5rem;
            }

            .badge {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        /* Action buttons container */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                margin-bottom: 0.25rem;
            }
        }

        /* Table Styling */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
        }

        .table td, .table th {
            padding: 0.75rem;
            vertical-align: middle;
        }

        /* Button Styling */
        .btn-group {
            display: flex;
            gap: 0.25rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Badge Styling */
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }

       
        /* DataTables Custom Styling */
        .dataTables_wrapper {
            padding: 1rem 0;
        }

        /* Table Base Styling */
        #patientsTable {
            border-collapse: separate;
            border-spacing: 0 0.8rem;
            width: 100%;
            margin-bottom: 1rem;
        }

        /* Header Styling */
        #patientsTable thead th {
            background: var(--light-color);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        /* Row Styling */
        #patientsTable tbody tr {
            background: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 0.8rem;
        }

        #patientsTable tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        #patientsTable td {
            background: white;
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        /* DataTables Controls */
        .dataTables_length select {
            padding: 0.375rem 2rem 0.375rem 0.75rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 0.5rem;
            background-color: white;
            cursor: pointer;
            min-width: 100px;
        }

        .dataTables_filter input {
            padding: 0.375rem 0.75rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 0.5rem;
            width: 200px;
            background-color: white;
        }

        .dataTables_filter input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.1);
            outline: none;
        }

        /* Pagination */
        .dataTables_paginate {
            margin-top: 1rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.25rem;
        }

        .paginate_button {
            padding: 0.375rem 0.75rem !important;
            margin: 0 2px !important;
            border-radius: 0.5rem !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            background: white !important;
            color: var(--dark-color) !important;
            cursor: pointer !important;
        }

        .paginate_button:hover:not(.disabled) {
            background: rgba(16, 185, 129, 0.1) !important;
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
        }

        .paginate_button.current {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
        }

        .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dataTables_filter input {
                width: 150px;
            }

            .dataTables_length select {
                min-width: 80px;
            }

            #patientsTable thead th,
            #patientsTable tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
        }
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
        .spinner-border {
            color: var(--primary-color) !important;
        }

        img{
            width: 95px;
            height: 95px;
            margin-right:45px;
            margin-left:50px;
        }
        a{
            text-decoration:none;
            color:black;
        }
        .logoutnav{
            margin-left:100px;
        }
        .main-content{
            margin-top:55px;
        }
        .sidebar {
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            height: calc(100vh - 70px);
            padding: 2rem 1rem;
            width: 250px;
        }

        .btn{
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
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

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="sidebar">
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    Dashboard
                </a>
                <a class="nav-link" href="admin_Doctors.php">
                    Doctors
                </a>
                <a class="nav-link active" href="admin_patients.php">
                    Patients
                </a>
            </nav>
        </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content ">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Patient Records</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // if (isset($success_message)) {
                        //     echo "<div class='alert alert-success'>$success_message</div>";
                        // }
                        ?>
                        <a href="admin_add_patient.php" class="btn btn-add-patient">
                            ADD PATIENT
                        </a>
                        <a href="add_prescription.php" class="btn btn-add-patient">
                            ADD PRESCRIPTION
                        </a>
                        <a href="add_illness_symptoms.php" class="btn btn-add-patient">
                            ILLNESS/SYMPTOMS
                        </a>

                        <!-- Table Container -->
                        <div class="card mt-3">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Patient Records</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="patientsTable" class="table table-bordered table-striped">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Guardian's Tel no.</th>
                                                <th>Name</th>
                                                <th>Birthdate</th>
                                                <th>Age</th>
                                                <th>Email</th>
                                                <th>Gender</th>
                                                <th>Civil Status</th>
                                                <th>Prescriptions</th>
                                                <th>Illness/Symptoms</th>
                                                <th>Medical Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                                <td>
                                                    <span data-bs-toggle="popover" 
                                                          data-bs-placement="right"
                                                          data-bs-trigger="hover"
                                                          data-patient-id="<?php echo $patient['id']; ?>"
                                                          style="cursor: pointer">
                                                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                                                <td><?php echo calculateAge($patient['date_of_birth']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['civil_status']); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-success btn-sm prescriptions-badge" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#prescriptionsModal<?php echo $patient['id']; ?>">
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo isset($prescriptions[$patient['id']]) ? $prescriptions[$patient['id']] : '0'; ?>
                                                        </span>
                                                    </button>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-success btn-sm illness-symptoms-badge" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#illnessSymptomsModal<?php echo $patient['id']; ?>">
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo isset($illness_symptoms[$patient['id']]) ? $illness_symptoms[$patient['id']] : '0'; ?>
                                                        </span>
                                                    </button>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-success btn-sm medical-status-badge" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#medicalStatusModal<?php echo $patient['id']; ?>">
                                                        <span class="badge bg-light text-dark">
                                                            <?php echo getMedicalStatusCount($patient['id']); ?>
                                                        </span>
                                                    </button>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" 
                                                            class="btn btn-warning btn-sm edit-patient" 
                                                            data-id="<?php echo $patient['id']; ?>"
                                                            data-first-name="<?php echo htmlspecialchars($patient['first_name']); ?>"
                                                            data-last-name="<?php echo htmlspecialchars($patient['last_name']); ?>"
                                                            data-dob="<?php echo htmlspecialchars($patient['date_of_birth']); ?>"
                                                            data-phone="<?php echo htmlspecialchars($patient['phone']); ?>"
                                                            data-email="<?php echo htmlspecialchars($patient['email']); ?>"
                                                            data-gender="<?php echo htmlspecialchars($patient['gender']); ?>"
                                                            data-civil-status="<?php echo htmlspecialchars($patient['civil_status']); ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editPatientModal">
                                                            Edit
                                                        </button>
                                                        
                                                        <button type="button" 
                                                            class="btn btn-danger btn-sm delete-patient" 
                                                            data-id="<?php echo $patient['id']; ?>">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($patients as $patient): ?>
                        <!-- Prescriptions Modal -->
                        <div class="modal fade" id="prescriptionsModal<?php echo $patient['id']; ?>" tabindex="-1" aria-labelledby="prescriptionsModalLabel<?php echo $patient['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="prescriptionsModalLabel<?php echo $patient['id']; ?>">Prescriptions for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="prescriptions-list">
                                            <!-- Prescriptions will be loaded here dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Illness/Symptoms Modal -->
                        <div class="modal fade" id="illnessSymptomsModal<?php echo $patient['id']; ?>" tabindex="-1" aria-labelledby="illnessSymptomsModalLabel<?php echo $patient['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="illnessSymptomsModalLabel<?php echo $patient['id']; ?>">
                                            Illness/Symptoms for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="illness-symptoms-list">
                                            <!-- Illness/Symptoms will be loaded here dynamically -->
                                        </div>
                                        <!-- Add form for new illness/symptoms -->
                                        <form id="illnessForm<?php echo $patient['id']; ?>" data-patient-id="<?php echo $patient['id']; ?>" class="mt-3">
                                            <div class="mb-3">
                                                <label class="form-label">Add New Illness/Symptom</label>
                                                <textarea class="form-control" name="complaint_text" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Add Illness/Symptom</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Status Modal -->
                        <div class="modal fade" id="medicalStatusModal<?php echo $patient['id']; ?>" tabindex="-1" aria-labelledby="medicalStatusModalLabel<?php echo $patient['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="medicalStatusModalLabel<?php echo $patient['id']; ?>">Medical Status for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="medical-status-list">
                                            <!-- Medical status will be loaded here dynamically -->
                                        </div>
                                        <form id="medicalStatusForm<?php echo $patient['id']; ?>" data-patient-id="<?php echo $patient['id']; ?>">
                                            <div class="mb-3">
                                                <label for="medical_status<?php echo $patient['id']; ?>" class="form-label">Add New Medical Status</label>
                                                <textarea class="form-control" id="medical_status<?php echo $patient['id']; ?>" name="medical_status" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Add Status</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <script src="js/bootstrap.bundle.min.js"></script>
                        <script src="js/jQuery3.7.1.js"></script>
                        <script src="js/datatables.min.js"></script>
                        <script>
                        $(document).ready(function() {
                            // Destroy existing instance if it exists
                            if ($.fn.DataTable.isDataTable('#patientsTable')) {
                                $('#patientsTable').DataTable().destroy();
                            }
                            
                            // Initialize DataTable with new configuration
                            var table = $('#patientsTable').DataTable({
                                responsive: true,
                                scrollX: false,
                                autoWidth: false,
                                pageLength: 10,
                                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                                order: [[1, 'asc']], // Sort by name by default
                                columnDefs: [
                                    { orderable: false, targets: [7, 8, 9, 10] },
                                    { responsivePriority: 1, targets: [1, 10] },
                                    { responsivePriority: 2, targets: [7, 8, 9] },
                                    { className: 'text-center', targets: [7, 8, 9, 10] }
                                ],
                                language: {
                                    search: "",
                                    searchPlaceholder: "Search records...",
                                    lengthMenu: "Show _MENU_ entries",
                                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                    paginate: {
                                        first: '<i class="fas fa-angle-double-left"></i>',
                                        last: '<i class="fas fa-angle-double-right"></i>',
                                        next: '<i class="fas fa-angle-right"></i>',
                                        previous: '<i class="fas fa-angle-left"></i>'
                                    }
                                }
                            });

                            // Event handlers
                            $(".delete-patient").click(function() {
                                if (confirm("Are you sure you want to delete this patient? This will also delete all associated data.")) {
                                    var patientId = $(this).data("id");
                                    $.post("admin_patients.php", { 
                                        delete_patient: true, 
                                        patient_id: patientId 
                                    }, function(response) {
                                        var result = JSON.parse(response);
                                        if (result.success) {
                                            alert(result.message);
                                            location.reload();
                                        } else {
                                            alert(result.message);
                                        }
                                    });
                                }
                            });

                            // Single event binding for modal triggers
                            $(document).on('click', '.prescriptions-badge', function() {
                                var patientId = $(this).closest('tr').find('.delete-patient').data('id');
                                loadPrescriptions(patientId);
                            });

                            $(document).on('click', '.illness-symptoms-badge', function() {
                                var patientId = $(this).closest('tr').find('.delete-patient').data('id');
                                loadIllnessSymptoms(patientId);
                            });

                            $(document).on('click', '.medical-status-badge', function() {
                                var patientId = $(this).closest('tr').find('.delete-patient').data('id');
                                loadMedicalStatus(patientId);
                            });

                            // Helper functions
                            function loadPrescriptions(patientId) {
                                var prescriptionsList = $('#prescriptionsModal' + patientId + ' .prescriptions-list');
                                $.get('get_prescriptions.php', { patient_id: patientId }, function(data) {
                                    prescriptionsList.html(data);
                                    
                                    // Add the prescription form if it doesn't exist
                                    if ($('#prescriptionForm').length === 0) {
                                        prescriptionsList.after(`   
                                        `);
                                    }
                                });
                            }

                            function loadIllnessSymptoms(patientId) {
                                var illnessSymptomsList = $('#illnessSymptomsModal' + patientId + ' .illness-symptoms-list');
                                $.get('get_illness_symptoms.php', { patient_id: patientId }, function(data) {
                                    illnessSymptomsList.html(data);
                                });
                            }

                            function loadMedicalStatus(patientId) {
                                var medicalStatusList = $('#medicalStatusModal' + patientId + ' .medical-status-list');
                                $.get('get_medical_status.php', { patient_id: patientId }, function(data) {
                                    medicalStatusList.html(data);
                                });
                            }

                            // Form submission handling
                            $(document).on('submit', "[id^=medicalStatusForm]", function(e) {
                                e.preventDefault();
                                var form = $(this);
                                var patientId = form.data("patient-id");
                                var medicalStatus = form.find("textarea[name='medical_status']").val();
                                
                                $.post("add_medical_status.php", {
                                    patient_id: patientId,
                                    medical_status: medicalStatus
                                }, function(data) {
                                    var result = JSON.parse(data);
                                    if (result.success) {
                                        var badge = $(".medical-status-badge[data-bs-target='#medicalStatusModal" + patientId + "'] .badge");
                                        var count = parseInt(badge.text()) + 1;
                                        badge.text(count);
                                        form.find("textarea").val("");
                                        loadMedicalStatus(patientId);
                                    } else {
                                        alert("Error adding medical status: " + result.message);
                                    }
                                });
                            });

                            // Initialize popovers
                            var popoverList = [];
                            document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(popoverTriggerEl) {
                                popoverList.push(new bootstrap.Popover(popoverTriggerEl, {
                                    html: true,
                                    content: function() {
                                        var patientId = $(this).data('patient-id');
                                        var patient = <?php echo json_encode($patients); ?>.find(p => p.id == patientId);
                                        return '<strong>Civil Status:</strong> ' + patient.civil_status + '<br>' +
                                               '<strong>Birthdate:</strong> ' + patient.date_of_birth + '<br>' +
                                               '<strong>Gender:</strong> ' + patient.gender + '<br>' +
                                               '<strong>Email:</strong> ' + patient.email;
                                    }
                                }));
                            });

                            // Add this function to format the prescriptions list
                            function formatPrescriptionsList(prescriptions) {
                                if (prescriptions.length === 0) {
                                    return '<p class="text-muted">No prescriptions found.</p>';
                                }
                                
                                let html = '<div class="list-group">';
                                prescriptions.forEach(prescription => {
                                    html += `
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <div class="prescription-content">
                                                    <h6 class="mb-1">Medication: ${prescription.medication}</h6>
                                                    <p class="mb-1"><strong>Dosage:</strong> ${prescription.dosage}</p>
                                                    <p class="mb-1"><strong>Frequency:</strong> ${prescription.frequency}</p>
                                                    <p class="mb-1"><strong>Duration:</strong> ${prescription.duration}</p>
                                                    <p class="mb-1"><strong>Instructions:</strong> ${prescription.instructions}</p>
                                                    ${prescription.notes ? `<p class="mb-1"><strong>Notes:</strong> ${prescription.notes}</p>` : ''}
                                                </div>
                                                <div class="prescription-meta">
                                                    <small class="text-muted">Prescribed: ${prescription.formatted_date}</small>
                                                    <br>
                                                    <div class="btn-group mt-2">
                                                        <button class="btn btn-warning btn-sm edit-prescription" 
                                                                data-id="${prescription.id}" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editPrescriptionModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm delete-prescription" 
                                                                data-id="${prescription.id}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                                });
                                html += '</div>';
                                return html;
                            }

                            // Update the prescription form submission handler
                            $(document).on('submit', '#prescriptionForm', function(e) {
                                e.preventDefault();
                                const patientId = $(this).data('patient-id');
                                const prescriptionText = $(this).find('textarea[name="prescription_text"]').val();
                                
                                $.post('admin_patients.php', {
                                    add_prescription: true,
                                    patient_id: patientId,
                                    prescription_text: prescriptionText
                                }, function(response) {
                                    const result = JSON.parse(response);
                                    if (result.success) {
                                        // Update the prescription count badge
                                        $(`.prescriptions-badge[data-bs-target="#prescriptionsModal${patientId}"] .badge`)
                                            .text(result.new_count);
                                        
                                        // Update the prescriptions list in the modal
                                        $(`#prescriptionsModal${patientId} .prescriptions-list`)
                                            .html(formatPrescriptionsList(result.prescriptions));
                                        
                                        // Clear the form
                                        $('#prescriptionForm textarea').val('');
                                        
                                        // Optional: Show success message
                                        alert(result.message);
                                    } else {
                                        alert(result.message);
                                    }
                                });
                            });

                            // Delete prescription
                            $(document).on('click', '.delete-prescription', function() {
                                if (confirm('Are you sure you want to delete this prescription?')) {
                                    const prescriptionId = $(this).data('id');
                                    const listItem = $(this).closest('.list-group-item');
                                    
                                    $.post('delete_prescription.php', {
                                        prescription_id: prescriptionId
                                    }, function(response) {
                                        try {
                                            const result = JSON.parse(response);
                                            if (result.success) {
                                                listItem.fadeOut(300, function() { 
                                                    $(this).remove();
                                                    // Update the prescription count badge
                                                    const patientId = result.patient_id;
                                                    const badge = $(`.prescriptions-badge[data-bs-target="#prescriptionsModal${patientId}"] .badge`);
                                                    const currentCount = parseInt(badge.text());
                                                    badge.text(currentCount - 1);
                                                });
                                            } else {
                                                alert('Error deleting prescription: ' + result.message);
                                            }
                                        } catch (e) {
                                            alert('Error processing response');
                                        }
                                    });
                                }
                            });

                            // Edit prescription
                            $(document).on('click', '.edit-prescription', function() {
                                const prescriptionId = $(this).data('id');
                                
                                // Fetch prescription details
                                $.get('get_prescription_details.php', {
                                    prescription_id: prescriptionId
                                }, function(response) {
                                    try {
                                        const prescription = JSON.parse(response);
                                        if (prescription.success) {
                                            // Populate the edit modal with prescription details
                                            $('#editPrescriptionModal input[name="medication"]').val(prescription.data.medication);
                                            $('#editPrescriptionModal input[name="dosage"]').val(prescription.data.dosage);
                                            $('#editPrescriptionModal input[name="frequency"]').val(prescription.data.frequency);
                                            $('#editPrescriptionModal input[name="duration"]').val(prescription.data.duration);
                                            $('#editPrescriptionModal textarea[name="instructions"]').val(prescription.data.instructions);
                                            $('#editPrescriptionModal textarea[name="notes"]').val(prescription.data.notes);
                                            $('#editPrescriptionModal input[name="prescription_id"]').val(prescription.data.id);
                                            
                                            // Show the modal
                                            $('#editPrescriptionModal').modal('show');
                                        } else {
                                            alert('Error fetching prescription details: ' + prescription.message);
                                        }
                                    } catch (e) {
                                        alert('Error processing response');
                                    }
                                });
                            });

                            // Add this inside your existing JavaScript code
                            $(document).on('submit', '[id^=illnessForm]', function(e) {
                                e.preventDefault();
                                const form = $(this);
                                const patientId = form.data('patient-id');
                                const complaintText = form.find('textarea[name="complaint_text"]').val();
                                
                                $.post('admin_patients.php', {
                                    add_complaint: true,
                                    patient_id: patientId,
                                    complaint_text: complaintText
                                }, function(response) {
                                    const result = JSON.parse(response);
                                    if (result.success) {
                                        // Update the illness/symptoms count badge
                                        const badge = $(`.illness-symptoms-badge[data-bs-target="#illnessSymptomsModal${patientId}"] .badge`);
                                        badge.text(result.new_count);
                                        
                                        // Clear the form
                                        form.find('textarea').val('');
                                        
                                        // Reload the illness/symptoms list
                                        loadIllnessSymptoms(patientId);
                                        
                                        // Show success message
                                        alert(result.message);
                                    } else {
                                        alert(result.message);
                                    }
                                });
                            });
                        });
                        </script>

                        <!-- Add this modal for editing prescriptions -->
                        <div class="modal fade" id="editPrescriptionModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Prescription</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="editPrescriptionForm">
                                            <input type="hidden" name="prescription_id">
                                            <div class="mb-3">
                                                <label class="form-label">Medication</label>
                                                <input type="text" class="form-control" name="medication" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Dosage</label>
                                                <input type="text" class="form-control" name="dosage" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Frequency</label>
                                                <input type="text" class="form-control" name="frequency" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Duration</label>
                                                <input type="text" class="form-control" name="duration" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Instructions</label>
                                                <textarea class="form-control" name="instructions" rows="3"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="notes" rows="2"></textarea>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="savePrescriptionChanges">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add this modal for editing patients -->
                        <div class="modal fade" id="editPatientModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Patient</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="editPatientForm">
                                            <input type="hidden" name="patient_id">
                                            <div class="mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date of Birth</label>
                                                <input type="date" class="form-control" name="date_of_birth" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="tel" class="form-control" name="phone" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Gender</label>
                                                <select class="form-control" name="gender" required>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Civil Status</label>
                                                <select class="form-control" name="civil_status" required>
                                                    <option value="Single">Single</option>
                                                    <option value="Married">Married</option>
                                                    <option value="Divorced">Divorced</option>
                                                    <option value="Widowed">Widowed</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" id="savePatientChanges">Save changes</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add this JavaScript code -->
                        <script>
                        $(document).ready(function() {
                            // Handle Edit Patient Button Click
                            $('.edit-patient').click(function() {
                                const patient = {
                                    id: $(this).data('id'),
                                    firstName: $(this).data('first-name'),
                                    lastName: $(this).data('last-name'),
                                    dob: $(this).data('dob'),
                                    phone: $(this).data('phone'),
                                    email: $(this).data('email'),
                                    gender: $(this).data('gender'),
                                    civilStatus: $(this).data('civil-status')
                                };

                                // Populate the edit form
                                const form = $('#editPatientForm');
                                form.find('input[name="patient_id"]').val(patient.id);
                                form.find('input[name="first_name"]').val(patient.firstName);
                                form.find('input[name="last_name"]').val(patient.lastName);
                                form.find('input[name="date_of_birth"]').val(patient.dob);
                                form.find('input[name="phone"]').val(patient.phone);
                                form.find('input[name="email"]').val(patient.email);
                                form.find('select[name="gender"]').val(patient.gender);
                                form.find('select[name="civil_status"]').val(patient.civilStatus);
                            });

                            // Handle Save Patient Changes
                            $('#savePatientChanges').click(function() {
                                const form = $('#editPatientForm');
                                const formData = new FormData(form[0]);
                                formData.append('update_patient', true);

                                $.ajax({
                                    url: 'admin_patients.php',
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        try {
                                            const result = JSON.parse(response);
                                            if (result.success) {
                                                alert('Patient updated successfully');
                                                location.reload();
                                            } else {
                                                alert('Error updating patient: ' + result.message);
                                            }
                                        } catch (e) {
                                            alert('Error processing response');
                                        }
                                    },
                                    error: function() {
                                        alert('Error updating patient');
                                    }
                                });
                            });

                            // Handle Delete Patient
                            $('.delete-patient').click(function() {
                                if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                                    const patientId = $(this).data('id');
                                    
                                    $.ajax({
                                        url: 'admin_patients.php',
                                        type: 'POST',
                                        data: {
                                            delete_patient: true,
                                            patient_id: patientId
                                        },
                                        success: function(response) {
                                            try {
                                                const result = JSON.parse(response);
                                                if (result.success) {
                                                    alert('Patient deleted successfully');
                                                    location.reload();
                                                } else {
                                                    alert('Error deleting patient: ' + result.message);
                                                }
                                            } catch (e) {
                                                alert('Error processing response');
                                            }
                                        },
                                        error: function() {
                                            alert('Error deleting patient');
                                        }
                                    });
                                }
                            });
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>