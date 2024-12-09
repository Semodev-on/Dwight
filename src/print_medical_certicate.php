<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['patient_id']) || !isset($_GET['id'])) {
    die("Unauthorized access");
}

// Fetch certificate details
$cert_query = "SELECT mc.*, p.first_name, p.last_name, p.date_of_birth, p.gender, 
               p.address, d.name AS doctor_name
               FROM medical_certificates mc
               JOIN patients_login p ON mc.patient_id = p.id
               JOIN doctors d ON mc.doctor_id = d.id
               WHERE mc.id = ? AND mc.patient_id = ? AND mc.status = 'approved'";
$stmt = $pdo->prepare($cert_query);
$stmt->execute([$_GET['id'], $_SESSION['patient_id']]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cert) {
    die("Certificate not found or not approved");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Medical Certificate</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .certificate { max-width: 800px; margin: auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 50px auto 10px; }
        @media print {
            .no-print { display: none; }
            body { font-size: 12pt; }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <h2>MEDICAL CERTIFICATE</h2>
            <p>This is to certify that</p>
        </div>

        <div class="content">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></p>
            <p><strong>Age:</strong> <?php echo calculateAge($cert['date_of_birth']); ?> years old</p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($cert['gender']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($cert['address']); ?></p>
            
            <p class="mt-4"><strong>Diagnosis:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($cert['diagnosis'])); ?></p>
            
            <p><strong>Duration:</strong> <?php 
                echo date('F d, Y', strtotime($cert['start_date'])) . ' to ' . 
                     date('F d, Y', strtotime($cert['end_date'])); 
            ?></p>

            <div class="text-end mt-5">
                <div class="signature-line"></div>
                <p class="mb-0">Dr. <?php echo htmlspecialchars($cert['doctor_name']); ?></p>
                <p>Licensed Physician</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>