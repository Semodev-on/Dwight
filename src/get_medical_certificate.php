<?php
session_start();
require_once 'db_connect.php';

// Validate request and permissions
if (!isset($_SESSION['patient_id'])) {
    error_log("Unauthorized access attempt - No patient_id in session");
    http_response_code(403);
    exit('Unauthorized access');
}

// Validate certificate ID
$certificate_id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT) : null;
if (!$certificate_id) {
    error_log("Invalid certificate ID provided");
    http_response_code(400);
    exit('Invalid certificate ID');
}

try {
    // Fetch certificate with all necessary details
    $query = "
        SELECT 
            mc.*,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            d.specializations,
            d.license_number,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            p.date_of_birth,
            p.gender,
            COALESCE(mc.approved_at, mc.request_date) as issue_date
        FROM medical_certificates mc
        INNER JOIN doctors d ON mc.doctor_id = d.id
        INNER JOIN patients p ON mc.patient_id = p.id
        WHERE mc.id = :certificate_id 
        AND mc.patient_id = :patient_id
        AND mc.status = 'approved'
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':certificate_id' => $certificate_id,
        ':patient_id' => $_SESSION['patient_id']
    ]);
    
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cert) {
        error_log("Certificate not found or not approved: ID {$certificate_id}");
        http_response_code(404);
        exit('Certificate not found or not yet approved');
    }

    // Format dates
    $start_date = new DateTime($cert['start_date']);
    $end_date = new DateTime($cert['end_date']);
    $issue_date = new DateTime($cert['issue_date']);
    
    // Generate certificate HTML
    ?>
    <div class="certificate-content">
        <div class="certificate-header text-center mb-4">
            <h2 class="certificate-title mb-3">MEDICAL CERTIFICATE</h2>
            <p class="clinic-info mb-0">Medical Clinic Name</p>
            <p class="clinic-info mb-0">123 Medical Center Street</p>
            <p class="clinic-info">Contact: (123) 456-7890</p>
        </div>

        <div class="patient-info mb-4">
            <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($cert['patient_first_name'] . ' ' . $cert['patient_last_name']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo date('F d, Y', strtotime($cert['date_of_birth'])); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($cert['gender'])); ?></p>
        </div>

        <div class="medical-info mb-4">
            <p>This is to certify that the above-named patient has been examined and is:</p>
            <p class="reason mb-3"><?php echo nl2br(htmlspecialchars($cert['reason'])); ?></p>
            <p>Period: <?php echo $start_date->format('F d, Y') . ' to ' . $end_date->format('F d, Y'); ?></p>
        </div>

        <div class="signature-section mt-5">
            <div class="text-center">
                <div class="signature-line"></div>
                <p class="doctor-name mb-1">
                    Dr. <?php echo htmlspecialchars($cert['doctor_first_name'] . ' ' . $cert['doctor_last_name']); ?>
                </p>
                <p class="doctor-specialization mb-1">
                    <?php echo htmlspecialchars($cert['specializations']); ?>
                </p>
                <p class="license-number mb-1">
                    License No: <?php echo htmlspecialchars($cert['license_number']); ?>
                </p>
                <p class="issue-date">
                    Issued on: <?php echo $issue_date->format('F d, Y'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    error_log("Error in get_medical_certificate.php: " . $e->getMessage());
    http_response_code(500);
    exit('An error occurred while retrieving the certificate');
}
?>