
<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'];
    $diagnosis = $_POST['diagnosis'];
    $recommendation = $_POST['recommendation'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    try {
        // Save to database
        $insert_query = "INSERT INTO medical_certificates 
                        (patient_id, doctor_id, diagnosis, recommendation, start_date, end_date) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute([$patient_id, $_SESSION['user_id'], $diagnosis, $recommendation, $start_date, $end_date]);
        
        // Fetch patient details for preview
        $patient_query = "SELECT * FROM patients WHERE id = ?";
        $stmt = $pdo->prepare($patient_query);
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generate preview HTML
        $preview = '
        <div class="certificate-preview">
            <div class="text-center mb-4">
                <h2>MEDICAL CERTIFICATE</h2>
                <p class="mb-4">This is to certify that</p>
            </div>
            
            <div class="patient-info mb-4">
                <p><strong>Name:</strong> ' . htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) . '</p>
                <p><strong>Age:</strong> ' . calculateAge($patient['date_of_birth']) . ' years old</p>
                <p><strong>Gender:</strong> ' . htmlspecialchars($patient['gender']) . '</p>
                <p><strong>Address:</strong> ' . htmlspecialchars($patient['address']) . '</p>
            </div>
            
            <div class="diagnosis-info mb-4">
                <p><strong>Diagnosis:</strong></p>
                <p>' . nl2br(htmlspecialchars($diagnosis)) . '</p>
                
                <p><strong>Recommendation:</strong></p>
                <p>' . nl2br(htmlspecialchars($recommendation)) . '</p>
                
                <p><strong>Duration:</strong> ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)) . '</p>
            </div>
            
            <div class="signature-section mt-5">
                <div class="row">
                    <div class="col-md-6 offset-md-6 text-center">
                        <div class="signature-line"></div>
                        <p class="mb-0"><strong>Licensed Physician</strong></p>
                        <p>License No: _______________</p>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-primary mt-3 no-print" onclick="printCertificate(' . $patient_id . ')">
                Print Certificate
            </button>
        </div>';
        
        echo json_encode([
            'success' => true,
            'message' => 'Certificate saved successfully',
            'preview' => $preview
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error saving certificate: ' . $e->getMessage()
        ]);
    }
    exit();
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $diff = $today->diff(new DateTime($birthdate));
    return $diff->y;
}
?>
```
