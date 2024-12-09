<?php
// appointments.php
include 'db_connect.php';
 

// Add new appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];

    // Check for existing appointment
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
                                WHERE patient_id = ? 
                                AND doctor_id = ? 
                                AND DATE(appointment_date) = DATE(?)");
    $check_stmt->execute([$patient_id, $doctor_id, $appointment_date]);
    $exists = $check_stmt->fetchColumn();

    if ($exists > 0) {
        $_SESSION['error'] = "An appointment already exists for this patient with this doctor on the selected date.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date) VALUES (?, ?, ?)");
            $stmt->execute([$patient_id, $doctor_id, $appointment_date]);
            $_SESSION['success'] = "Appointment added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding appointment: " . $e->getMessage();
        }
    }
    header("Location: appointments.php");
    exit();
}

// Delete appointment
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
}

// Fetch appointments
$stmt = $pdo->query("SELECT a.id, p.name as patient_name, d.name as doctor_name, a.appointment_date 
                     FROM appointments a 
                     JOIN patients p ON a.patient_id = p.id 
                     JOIN doctors d ON a.doctor_id = d.id");
$appointments = $stmt->fetchAll();

// Fetch patients and doctors for the dropdown
$patients = $pdo->query("SELECT id, name FROM patients")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctors")->fetchAll();
?>

<h2>Manage Appointments</h2>

<!-- Add this right after your <h2> tag to display messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<!-- Add Appointment Form -->
<form method="POST" class="mb-4">
    <div class="form-row">
        <div class="col">
            <select name="patient_id" class="form-control" required>
                <option value="">Select Patient</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <select name="doctor_id" class="form-control" required>
                <option value="">Select Doctor</option>
                <?php foreach ($doctors as $doctor): ?>
                    <option value="<?= $doctor['id'] ?>"><?= htmlspecialchars($doctor['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col">
            <input type="datetime-local" name="appointment_date" class="form-control" required>
        </div>
        <div class="col">
            <button type="submit" name="add_appointment" class="btn btn-primary">Add Appointment</button>
        </div>
    </div>
</form>

<!-- Appointments Table -->
<table id="appointmentsTable" class="display">
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Appointment Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($appointments as $appointment): ?>
        <tr>
            <td><?= $appointment['id'] ?></td>
            <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
            <td><?= htmlspecialchars($appointment['doctor_name']) ?></td>
            <td><?= $appointment['appointment_date'] ?></td>
            <td>
                <a href="?delete=<?= $appointment['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function() {
    $('#appointmentsTable').DataTable();
});
</script>

<?php
include 'footer.php';
?>