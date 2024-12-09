<?php
// doctors.php
include 'db_connect.php';
include 'header.php';

// Add new doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $name = $_POST['name'];
    $specialization = $_POST['specialization'];
    $phone = $_POST['phone'];

    $stmt = $pdo->prepare("INSERT INTO doctors (name, specialization, phone) VALUES (?, ?, ?)");
    $stmt->execute([$name, $specialization, $phone]);
}

// Delete doctor
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->execute([$id]);
}

// Fetch doctors
$stmt = $pdo->query("SELECT * FROM doctors");
$doctors = $stmt->fetchAll();
?>

<h2>Manage Doctors</h2>

<!-- Add Doctor Form -->
<form method="POST" class="mb-4">
    <div class="form-row">
        <div class="col">
            <input type="text" name="name" class="form-control" placeholder="Name" required>
        </div>
        <div class="col">
            <input type="text" name="specialization" class="form-control" placeholder="Specialization" required>
        </div>
        <div class="col">
            <input type="tel" name="phone" class="form-control" placeholder="Phone" required>
        </div>
        <div class="col">
            <button type="submit" name="add_doctor" class="btn btn-primary">Add Doctor</button>
        </div>
    </div>
</form>

<!-- Doctors Table -->
<table id="doctorsTable" class="display">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Specialization</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($doctors as $doctor): ?>
        <tr>
            <td><?= $doctor['id'] ?></td>
            <td><?= htmlspecialchars($doctor['name']) ?></td>
            <td><?= htmlspecialchars($doctor['specialization']) ?></td>
            <td><?= htmlspecialchars($doctor['phone']) ?></td>
            <td>
                <a href="?delete=<?= $doctor['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
$(document).ready(function() {
    $('#doctorsTable').DataTable();
});
</script>

<?php
include 'footer.php';
?>