<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['patient_id'])) {
    http_response_code(403);
    exit('Unauthorized access');
}

if (!isset($_POST['id'])) {
    http_response_code(400);
    exit('Certificate ID not provided');
}

try {
    // Fetch certificate details with related information
    $query = "SELECT mc.*,
                     p.first_name as patient_first_name,
                     p.last_name as patient_last_name,
                     d.first_name as doctor_first_name,
                     d.last_name as doctor_last_name,
                     d.specializations,
                     d.license_number,
                     COALESCE(mc.approved_at, mc.request_date) as display_date
              FROM medical_certificates mc
              LEFT JOIN patients p ON mc.patient_id = p.id
              LEFT JOIN doctors d ON mc.doctor_id = d.id
              WHERE mc.id = ?";
    
    // Add security check based on user type
    if (isset($_SESSION['patient_id'])) {
        $query .= " AND mc.patient_id = ?";
        $params = [$_POST['id'], $_SESSION['patient_id']];
    } else {
        $query .= " AND mc.doctor_id = ?";
        $params = [$_POST['id'], $_SESSION['doctor_id']];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cert) {
        throw new Exception('Certificate not found');
    }

    // Debug information (remove in production)
    error_log("Certificate data: " . print_r($cert, true));
    
    // Generate certificate HTML
    ?>
    <div class="certificate-content p-4 bg-white">
        <!-- Header -->
        <div class="text-center mb-4">
            <h4 class="certificate-title text-uppercase fw-bold">MEDICAL CERTIFICATE</h4>
            <p class="clinic-info mb-0 fs-5">Medical Clinic Name</p>
            <p class="clinic-info mb-3">123 Medical Center Drive, City, State 12345</p>
            <hr class="my-4">
        </div>

        <!-- Certificate Body -->
        <div class="certificate-body">
            <!-- Patient Information -->
            <div class="patient-info mb-4">
                <div class="row mb-3">
                    <div class="col-12">
                        <p class="mb-2">
                            <strong>Patient Name:</strong> 
                            <?php echo htmlspecialchars($cert['patient_first_name'] . ' ' . $cert['patient_last_name']); ?>
                        </p>
                        <p class="mb-2">
                            <strong>Period:</strong> 
                            <?php 
                            if (isset($cert['start_date']) && isset($cert['end_date'])) {
                                echo date('F d, Y', strtotime($cert['start_date'])) . ' to ' . 
                                     date('F d, Y', strtotime($cert['end_date']));
                            }
                            ?>
                        </p>
                        <p class="mb-0">
                            <strong>Reason:</strong><br>
                            <?php echo nl2br(htmlspecialchars($cert['reason'] ?? '')); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Status Information -->
            <div class="status-info mb-4">
                <p class="mb-2">
                    <strong>Status:</strong> 
                    <span class="badge bg-<?php echo $cert['status'] === 'approved' ? 'success' : 
                        ($cert['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                        <?php echo ucfirst($cert['status']); ?>
                    </span>
                </p>
                <?php if ($cert['status'] === 'approved'): ?>
                    <p class="mb-0">
                        <strong>Approval Date:</strong> 
                        <?php echo date('F d, Y', strtotime($cert['approved_at'])); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Doctor's Signature Section -->
            <?php if ($cert['status'] === 'approved'): ?>
                <div class="signature-section text-center mt-5">
                    <div class="signature-line"></div>
                    <p class="doctor-name mb-1 fw-bold">
                        Dr. <?php echo htmlspecialchars($cert['doctor_first_name'] . ' ' . $cert['doctor_last_name']); ?>
                    </p>
                    <p class="doctor-specialization mb-1 text-muted">
                        <?php echo htmlspecialchars($cert['specializations'] ?? 'Medical Doctor'); ?>
                    </p>
                    <p class="license-number mb-0 small">
                        License No: <?php echo htmlspecialchars($cert['license_number'] ?? 'N/A'); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This certificate is currently <strong><?php echo $cert['status']; ?></strong>.
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-4 text-muted small">
            <p class="mb-0">This is an official medical certificate issued on <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <style>
        .certificate-content {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
            font-size: 14px;
        }
        .certificate-title {
            font-size: 24px;
            color: #2c3e50;
            letter-spacing: 2px;
        }
        .clinic-info {
            color: #666;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 20px auto 10px;
        }
        .patient-info, .status-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
        }
        @media print {
            .certificate-content {
                border: none;
            }
            .patient-info, .status-info {
                background-color: transparent !important;
            }
        }
    </style>
    <?php
    
} catch (Exception $e) {
    error_log("Certificate Error: " . $e->getMessage());
    http_response_code(500);
    exit('Error loading certificate: ' . $e->getMessage());
}
?>