<?php
require_once 'doctor_check.php';
require_once 'db_connect.php';

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id']) || $_SESSION['user_type'] !== 'doctor') {
    $_SESSION['error_message'] = "Please log in to access the medical certificates.";
    header("Location: index.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Add security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Handle certificate approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['certificate_id']) && isset($_POST['action']) && isset($_POST['remarks'])) {
        $certificate_id = filter_var($_POST['certificate_id'], FILTER_SANITIZE_NUMBER_INT);
        $action = in_array($_POST['action'], ['approve', 'reject']) ? $_POST['action'] : null;
        $remarks = trim(htmlspecialchars($_POST['remarks']));

        if (!$action) {
            $_SESSION['error_message'] = "Invalid action specified";
            header("Location: medical_certificates.php");
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Validate the certificate belongs to this doctor
            $check_query = "SELECT id FROM medical_certificates 
                          WHERE id = ? AND doctor_id = ? AND status = 'pending'";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$certificate_id, $doctor_id]);

            if ($check_stmt->fetch()) {
                // Update certificate status
                $update_query = "UPDATE medical_certificates 
                               SET status = ?, 
                                   doctor_remarks = ?,
                                   approved_by = ?,
                                   approved_at = CURRENT_TIMESTAMP
                               WHERE id = ?";
                
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                
                $update_stmt = $pdo->prepare($update_query);
                if ($update_stmt->execute([$status, $remarks, $doctor_id, $certificate_id])) {
                    $pdo->commit();
                    $_SESSION['success_message'] = "Medical certificate has been " . $status;
                } else {
                    throw new Exception("Failed to update certificate status");
                }
            } else {
                throw new Exception("Invalid certificate request");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error processing certificate: " . $e->getMessage();
        }
        
        header("Location: medical_certificates.php");
        exit();
    }
}

// Simplified query to fetch certificates
$certificates_query = "SELECT mc.id, 
                             mc.patient_id,
                             mc.doctor_id,
                             mc.start_date,
                             mc.end_date,
                             mc.reason,
                             mc.request_date,
                             mc.status,
                             p.first_name as patient_first_name,
                             p.last_name as patient_last_name,
                             d.first_name as doctor_first_name,
                             d.last_name as doctor_last_name
                      FROM medical_certificates mc
                      JOIN patients p ON mc.patient_id = p.id
                      JOIN doctors d ON mc.doctor_id = d.id
                      WHERE mc.doctor_id = ? 
                      ORDER BY mc.request_date DESC";

$stmt = $pdo->prepare($certificates_query);
$stmt->execute([$doctor_id]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Certificates - Doctor Dashboard</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --success-color: #34d399;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #064e3b;
            --light-color: #f0fdf4;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            background-color: var(--light-color);
            padding-top: 76px;
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

        .main-content {
            margin-left: 250px;
            padding: 1rem 2rem;
            min-height: calc(100vh - 76px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.125);
        }

        .section-title {
            color: var(--dark-color);
            font-weight: 600;
        }

        .section-subtitle {
            color: #666;
        }

        .table th {
            font-weight: 600;
            color: var(--dark-color);
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }

        .modal-content {
            border: none;
            border-radius: 0.5rem;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
        }

        .table-responsive {
            margin: 0;
        }

        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
        }

        .dataTables_wrapper .row {
            margin: 0;
            align-items: center;
        }

        .dataTables_paginate {
            margin-top: 1rem !important;
            display: flex;
            justify-content: flex-end;
        }

        .dataTables_filter input {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }

        .modal-dialog {
            max-width: 500px;
            margin: 1.75rem auto;
        }

        /* DataTables specific styling */
        .dataTables_wrapper .row:first-child {
            margin-bottom: 1rem;
        }

        .dataTables_filter {
            margin-bottom: 0.5rem;
        }

        /* Adjust table header and cell padding */
        .table thead th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            font-weight: 600;
        }

        .table td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        /* Make the table more compact if needed */
        .table-sm td, .table-sm th {
            padding: 0.5rem;
        }

        /* Update DataTables controls spacing */
        .dataTables_wrapper .dataTables_length {
            margin-right: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="doctors_profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="doctors_logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="position-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="doctor_dashboard.php">
                        Dashboard
                    </a>
                </li>  
                <li class="nav-item">
                    <a class="nav-link" href="doctors_appointments.php">
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_patients.php">
                        My Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="doctors_schedule.php">
                        My Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="medical_certificates.php">
                        Medical Certificates
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Move table section first -->
            <div class="row">
                <div class="col-12">
                    <div class="card mt-3"> <!-- Added mt-3 for small top margin -->
                        <div class="card-header bg-white py-3"> <!-- New card header -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Medical Certificates</h5>
                                    <small class="text-muted">Review and approve medical certificate requests</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alert Messages inside card -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <table id="certificatesTable" class="table table-striped dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient Name</th>
                                        <th>Period</th>
                                        <th>Request Date</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificates as $cert): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($cert['id']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($cert['patient_first_name'] . ' ' . $cert['patient_last_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-range me-2 text-muted"></i>
                                                <?php 
                                                $start = new DateTime($cert['start_date']);
                                                $end = new DateTime($cert['end_date']);
                                                $days = $start->diff($end)->days + 1;
                                                echo $start->format('M d') . ' - ' . $end->format('M d, Y') . ' (' . $days . ' days)';
                                                ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                                <?php echo date('M d, Y', strtotime($cert['request_date'])); ?>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      data-bs-toggle="tooltip" 
                                                      title="<?php echo htmlspecialchars($cert['reason']); ?>">
                                                    <?php echo htmlspecialchars($cert['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = match($cert['status']) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    default => 'warning'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($cert['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($cert['status'] === 'pending'): ?>
                                                    <button type="button" 
                                                            class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#certificateModal<?php echo $cert['id']; ?>">
                                                        <i class="fas fa-edit me-1"></i>Review
                                                    </button>
                                                    
                                                    <!-- Certificate Review Modal -->
                                                    <div class="modal fade" id="certificateModal<?php echo $cert['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Review Medical Certificate Request</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form action="medical_certificates.php" method="POST">
                                                                        <input type="hidden" name="certificate_id" value="<?php echo $cert['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Patient</label>
                                                                            <p class="form-control-static">
                                                                                <?php echo htmlspecialchars($cert['patient_first_name'] . ' ' . $cert['patient_last_name']); ?>
                                                                            </p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Period</label>
                                                                            <p class="form-control-static">
                                                                                <?php 
                                                                                echo date('M d', strtotime($cert['start_date'])) . ' - ' . 
                                                                                     date('M d, Y', strtotime($cert['end_date'])); 
                                                                                ?>
                                                                            </p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Reason</label>
                                                                            <p class="form-control-static">
                                                                                <?php echo htmlspecialchars($cert['reason']); ?>
                                                                            </p>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="remarks<?php echo $cert['id']; ?>" class="form-label">Doctor's Remarks</label>
                                                                            <textarea class="form-control" 
                                                                                    id="remarks<?php echo $cert['id']; ?>" 
                                                                                    name="remarks" 
                                                                                    required></textarea>
                                                                        </div>
                                                                        
                                                                        <div class="d-flex gap-2">
                                                                            <button type="submit" name="action" value="approve" class="btn btn-success flex-grow-1">
                                                                                <i class="fas fa-check-circle me-2"></i>Approve
                                                                            </button>
                                                                            <button type="submit" name="action" value="reject" class="btn btn-danger flex-grow-1">
                                                                                <i class="fas fa-times-circle me-2"></i>Reject
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="btn btn-info btn-sm view-certificate" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewModal<?php echo $cert['id']; ?>"
                                                            data-certificate-id="<?php echo $cert['id']; ?>">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </button>
                                                    
                                                    <!-- Update the view modal content -->
                                                    <div class="modal fade" id="viewModal<?php echo $cert['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title">Medical Certificate</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div id="certificateContainer<?php echo $cert['id']; ?>" class="certificate-container">
                                                                        <div class="text-center py-4">
                                                                            <div class="spinner-border text-primary" role="status">
                                                                                <span class="visually-hidden">Loading...</span>
                                                                            </div>
                                                                            <p class="mt-2">Loading certificate...</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="button" class="btn btn-primary print-certificate">
                                                                        <i class="fas fa-print me-1"></i>Print
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/jQuery3.7.1.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/datatables.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#certificatesTable').DataTable({
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: "",
                searchPlaceholder: "Search certificates...",
                lengthMenu: "_MENU_ per page",
            },
            order: [[0, 'desc']],
            columnDefs: [
                {
                    targets: -1,
                    orderable: false,
                    searchable: false
                }
            ],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle certificate viewing
        $('.view-certificate').click(function() {
            const modal = $($(this).data('bs-target'));
            const certificateId = $(this).data('certificate-id');
            const container = modal.find('.certificate-container');
            
            // Show loading indicator
            container.html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading certificate...</p></div>');
            
            // Load certificate content
            $.post('get_certificate.php', { id: certificateId })
                .done(function(response) {
                    container.html(response);
                })
                .fail(function(xhr) {
                    container.html(`<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${xhr.responseText || 'Error loading certificate'}
                    </div>`);
                });
        });

        // Handle certificate printing
        $('.print-certificate').click(function() {
            const content = $(this).closest('.modal').find('.certificate-container').html();
            const printWindow = window.open('', '', 'height=800,width=800');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Medical Certificate</title>
                    <link href="css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        .certificate-container { max-width: 800px; margin: 0 auto; }
                        @media print {
                            body { padding: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                setTimeout(function() { printWindow.close(); }, 250);
            };
        });

        // Clear modal content when hidden
        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('.certificate-container').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading certificate...</p>
                </div>
            `);
        });
    });
    </script>
</body>
</html>