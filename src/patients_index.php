<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

// Redirect if already logged in
if (isset($_SESSION['patient_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'patient') {
        header("Location: patient_dashboard.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signin'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Fetch patient by email
        $query = "SELECT pl.*, p.id as patient_id 
                  FROM patients_login pl 
                  JOIN patients p ON p.patients_login_id = pl.id 
                  WHERE pl.email = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($patient && password_verify($password, $patient['password'])) {
            // Set all necessary session variables
            $_SESSION['patient_id'] = $patient['patient_id'];
            $_SESSION['user_type'] = 'patient';
            $_SESSION['first_name'] = $patient['first_name'];
            $_SESSION['last_name'] = $patient['last_name'];
            $_SESSION['email'] = $patient['email'];
            
            // Redirect to patient dashboard
            header("Location: patient_dashboard.php");
            exit();
        } else {
            $error_message = "Invalid email or password. Please try again.";
        }
    } elseif (isset($_POST['signup'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $date_of_birth = $_POST['date_of_birth'];
        $age = $_POST['age'];

        // Validation
        $errors = [];
        
        // Validate first name
        if (empty($first_name)) {
            $errors[] = "First name is required";
        }

        // Validate last name
        if (empty($last_name)) {
            $errors[] = "Last name is required";
        }

        // Validate date of birth
        if (empty($date_of_birth)) {
            $errors[] = "Date of birth is required";
        } else {
            $birthDate = new DateTime($date_of_birth);
            $today = new DateTime();
            if ($birthDate > $today) {
                $errors[] = "Date of birth cannot be in the future";
            }
        }

        // Validate email
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Validate password
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }

        // Validate password confirmation
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM patients_login WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error_message = "Email already exists. Please use a different email.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into patients_login table first
                    $insert_login = $pdo->prepare("
                        INSERT INTO patients_login (
                            first_name, 
                            last_name, 
                            email, 
                            password,
                            date_of_birth,
                            age,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $insert_login->execute([
                        $first_name,
                        $last_name,
                        $email,
                        $hashed_password,
                        $date_of_birth,
                        $age
                    ]);
                    
                    // Get the new login_id
                    $login_id = $pdo->lastInsertId();
                    
                    // Insert into patients table
                    $insert_patient = $pdo->prepare("
                        INSERT INTO patients (
                            patients_login_id,
                            first_name,
                            last_name,
                            email,
                            date_of_birth,
                            age,
                            status,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    
                    $insert_patient->execute([
                        $login_id,
                        $first_name,
                        $last_name,
                        $email,
                        $date_of_birth,
                        $age
                    ]);

                    $pdo->commit();
                    $success_message = "Account created successfully! Please sign in.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Database error: " . $e->getMessage();
                error_log("Sign-up error: " . $e->getMessage());
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Update root variables with green colors */
        :root {
            --primary-color: #10b981;        /* Green primary */
            --primary-hover: #059669;        /* Darker green for hover */
            --secondary-color: #34d399;      /* Light green */
            --bg-color: #ecfdf5;             /* Very light green background */
            --card-bg: #ffffff;
            --text-dark: #064e3b;            /* Dark green text */
            --text-light: #047857;           /* Medium green text */
            --border-color: #d1fae5;         /* Light green border */
            --accent-color: #059669;         /* Medium green accent */
            --input-bg: #f0fdf4;             /* Very light green input */
            --input-border: #6ee7b7;         /* Light green input border */
            --input-focus-border: #10b981;   /* Green focus border */
            --input-shadow: rgba(16, 185, 129, 0.05);
            --label-color: #047857;          /* Medium green label */
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding-top: 80px;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
        }

        .navbar {
            padding: 1rem 0;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--primary-color);
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .navbar a > img{
            margin-left:-250px;
            margin-right:40px;
        }
        .navnar a{
            font-size:10px;
        }
        a > img{
            width:100px;
            height:100px;
        }
        a{
            text-decoration:none;
            color:black;
        }
        .background{
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-height: 100vh;
            background-image: url(barangay_health_center.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            z-index: -1;
            filter: blur(16px);
        }

        .card-header {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .card-header h5 {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-floating > .form-control {
            padding: 1.25rem;
            height: 3.75rem;
            border: 2px solid var(--border-color);
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background: linear-gradient(45deg, #10b981, #34d399);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: var(--accent-color);
            border: none;
            color: white;
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #0891b2;
            color: white;
        }

        .toggle-form {
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
        }

        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .footer {
            background: var(--card-bg);
            color: var(--text-dark);
            padding: 4rem 0 2rem;
            margin-top: 6rem;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
        }

        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .card {
                margin: 1rem;
            }
            
            .card-body {
                padding: 2rem;
            }
        }

        /* Enhanced Form Controls */
        .form-floating {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px var(--input-shadow);
            margin-bottom: 1.5rem;
        }

        .form-floating > .form-control {
            height: 4rem;
            padding: 1.5rem;
            font-size: 1.1rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            background-color: var(--input-bg);
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:hover {
            border-color: var(--primary-color);
        }

        .form-floating > .form-control:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
            background-color: white;
        }

        .form-floating > label {
            padding: 1rem 1.5rem;
            color: var(--label-color);
            font-weight: 500;
            font-size: 1rem;
        }

        .form-floating > .form-control:focus ~ label {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Input Icons */
        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--label-color);
            font-size: 1.2rem;
            z-index: 2;
        }

        .input-icon .form-control {
            padding-left: 3rem !important;
        }

        /* Form Container */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Password Toggle */
        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--label-color);
            z-index: 2;
        }

        .password-toggle i:hover {
            color: var(--primary-color);
        }

        /* Alert Styles */
        .alert-success {
            background-color: #ecfdf5;
            border-color: #10b981;
            color: #059669;
        }

        /* Form Section Enhancement */
        .form-section {
            border: 1px solid #d1fae5;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
        }

        .form-section:hover {
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.15);
        }

        .form-section-title {
            color: #059669;
            border-bottom: 2px solid #34d399;
        }
        .background{
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-height: 100vh;
            background-image: url(barangay_health_center.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            z-index: -1;
            filter: blur(16px);
        }
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .fb {
            width: 75px;
            height: 75px;
        }

        .social-icons a:hover {
            transform:scale(1.1);
            transition:0.2s;
        }

        .social-icons  img{
            width: 75px;
            height: 75px;
        }

        input[type="date"] {
            padding: 0.5rem;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 0.2rem;
        }

        input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

    </style>
</head>
<body>
    <div class="background"></div>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top"><img src="" alt="">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="barangay_victoria.png" alt="image not found">
                    Barangay Victoria Reyes Dasmariñas Health Center
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto admin">
                        <li class="nav-item">
                            <a href="login.php">
                                <button type="button" class="btn btn-primary ">Admin Login</button>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>


    <!-- Main Content -->
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-6 col-md-8">
                <div class="card my-5">
                    <div class="card-header">
                        <h5 class="mb-0">Patient Portal</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        if (!empty($error_message)) {
                            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>$error_message</div>";
                        }
                        if (!empty($success_message)) {
                            echo "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>$success_message</div>";
                        }
                        ?>
                        <!-- Sign In Form -->
                        <div id="signin-form">
                            <h4 class="text-center mb-4">Welcome Back</h4>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-container">
                                <div class="form-floating input-icon mb-4">
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="signin-email" 
                                        name="email" 
                                        placeholder="name@example.com" 
                                        required
                                        autocomplete="email"
                                    >
                                    <label for="signin-email">Email address</label>
                                </div>
                                
                                <div class="form-floating password-toggle mb-4">
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="signin-password" 
                                        name="password" 
                                        placeholder="Password" 
                                        required
                                    >
                                    <label for="signin-password">Password</label>
                                    <i class="far fa-eye" id="togglePassword"></i>
                                </div>

                                <div class="d-grid gap-2 mb-4 mt-2">
                                    <button class="btn btn-primary btn-lg btn-submit" type="submit" name="signin">
                                        Sign In 
                                    </button>
                                </div>
                            </form>
                            <hr class="my-4">
                            <div class="text-center">
                                <span>Don't have an account? </span>
                                <span class="toggle-form" onclick="toggleForm('signup')">Sign Up</span>
                            </div>
                            <div class="text-center mt-3">
                                <a href="reset_password_patients.php" class="btn btn-secondary btn-submit text-uppercase fw-bold">Forgot Password</a>
                            </div>
                        </div>

                        <!-- Sign Up Form -->
                        <div id="signup-form" style="display: none;">
                            <h4 class="text-center mb-4">Create Your Patient Account</h4>
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" 
                                  class="form-container needs-validation" novalidate>
                                <div class="form-floating input-icon mb-3">
                                    <input type="date" 
                                           class="form-control" 
                                           id="date-of-birth" 
                                           name="date_of_birth" 
                                           required
                                           max="<?php echo date('Y-m-d'); ?>"
                                           onchange="calculateAge(this.value)">
                                    <label for="date-of-birth">Date of Birth</label>
                                    <div class="invalid-feedback">Please enter your date of birth.</div>
                                </div>

                                <div class="form-floating input-icon mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="age" 
                                           name="age" 
                                           readonly
                                           placeholder="Age">
                                    <label for="age">Age</label>
                                </div>

                                <div class="form-floating input-icon mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="first-name" 
                                           name="first_name" 
                                           placeholder="First Name"
                                           value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                                           required>
                                    <label for="first-name">First Name</label>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>

                                <div class="form-floating input-icon mb-3">
                                    <input type="text" 
                                           class="form-control" 
                                           id="last-name" 
                                           name="last_name" 
                                           placeholder="Last Name"
                                           value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                                           required>
                                    <label for="last-name">Last Name</label>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>

                                <div class="form-floating input-icon mb-3">
                                    <input type="email" 
                                        class="form-control" 
                                        id="signup-email" 
                                        name="email" 
                                        placeholder="name@example.com"
                                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                        required>
                                    <label for="signup-email">Email address</label>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <div class="form-floating password-toggle mb-3">
                                    <input type="password" 
                                           class="form-control" 
                                           id="signup-password" 
                                           name="password" 
                                           placeholder="Password"
                                           required 
                                           minlength="6">
                                    <label for="signup-password">Password</label>
                                    <i class="far fa-eye" id="toggleSignupPassword"></i>
                                    <div class="invalid-feedback">Password must be at least 6 characters.</div>
                                </div>

                                <div class="form-floating password-toggle mb-4">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm-password" 
                                           name="confirm_password" 
                                           placeholder="Confirm Password"
                                           required>
                                    <label for="confirm-password">Confirm Password</label>
                                    <i class="far fa-eye" id="toggleConfirmPassword"></i>
                                    <div class="invalid-feedback">Passwords must match.</div>
                                </div>

                                <div class="d-grid gap-2 mb-4">
                                    <button class="btn btn-primary btn-lg btn-submit" type="submit" name="signup">
                                        Create Account 
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-2">
                                <span>Already have an account? </span>
                                <span class="toggle-form" onclick="toggleForm('signin')">Sign In</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Barangay Health Care Management System</h3>
                    <p>Making healthcare accessible and efficient</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="social-icons">
                        <a href="https://www.facebook.com/pages/Victoria-Reyes-Dasmarinas-Cavite/154851691357450" target="blank" class="fb">
                            <img src="fb_icon2.png" alt="">
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Barangay Victoria Reyes Dasmariñas Cavite. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm(formType) {
            const signinForm = document.getElementById('signin-form');
            const signupForm = document.getElementById('signup-form');
            
            if (formType === 'signup') {
                signinForm.style.display = 'none';
                signupForm.style.display = 'block';
            } else {
                signupForm.style.display = 'none';
                signinForm.style.display = 'block';
            }
        }

        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            
            toggle.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        togglePasswordVisibility('signin-password', 'togglePassword');
        togglePasswordVisibility('signup-password', 'toggleSignupPassword');
        togglePasswordVisibility('confirm-password', 'toggleConfirmPassword');

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })

            // Password match validation
            const password = document.getElementById('signup-password')
            const confirm = document.getElementById('confirm-password')
            
            function validatePassword() {
                if (password.value !== confirm.value) {
                    confirm.setCustomValidity("Passwords do not match")
                } else {
                    confirm.setCustomValidity('')
                }
            }

            password.addEventListener('change', validatePassword)
            confirm.addEventListener('keyup', validatePassword)
        })()

        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            document.getElementById('age').value = age;
        }
    </script>
</body>
</html>