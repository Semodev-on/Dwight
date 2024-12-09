<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #10b981;
            --secondary-color: #059669;
            --accent-color: #34d399;
            --text-dark: #064e3b;
            --text-light: #047857;
            --bg-light: #f0fdf4;
            --bg-soft: #ecfdf5;
            --border-color: #d1fae5;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Modern Hero Section */
        .hero {
            background: linear-gradient(120deg, 
                rgba(16, 185, 129, 0.85), 
                rgba(5, 150, 105, 0.9)),
                url('images/hospital-bg.jpg') center/cover;
            min-height: 100vh;
            padding: 160px 0;
            position: relative;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 1.5rem;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2.5rem;
        }

        /* Enhanced Navigation */
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

        /* Modern Feature Boxes */
        .feature-box {
            padding: 2.5rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px rgba(16, 185, 129, 0.15);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        /* Enhanced Buttons */
        .cta-button {
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--secondary-color);
            border: none;
            box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2);
        }

        .btn-success:hover {
            background: #047857;
            transform: translateY(-2px);
        }

        /* Modern Contact Form */
        .form-control {
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            background-color: var(--bg-soft);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        /* Enhanced Footer */
        .footer {
            background: var(--bg-soft);
            padding: 5rem 0 2rem;
            color: var(--text-dark);
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

        /* About Section Enhancement */
        #about {
            background: linear-gradient(to bottom, 
                var(--bg-light), 
                var(--bg-soft));
            padding: 6rem 0;
        }

        #about img {
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero {
                padding: 100px 0;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .cta-button {
                padding: 0.75rem 1.5rem;
                font-size: 0.875rem;
            }

            .feature-box {
                padding: 2rem;
                margin-bottom: 1.5rem;
            }
        }

        /* Additional Soft Elements */
        .card {
            border-color: var(--border-color);
            background: rgba(255, 255, 255, 0.9);
        }

        .alert {
            border: none;
            background: rgba(16, 185, 129, 0.1);
        }

        /* Preloader Update */
        .preloader {
            background: rgba(240, 253, 244, 0.9);
            backdrop-filter: blur(10px);
        }

        .spinner-border {
            color: var(--primary-color) !important;
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
    </style>
</head>
<body>
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
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#about">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#contact">Contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-secondary ms-2" href="login.php">Admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="patients_index.php">Patient Login</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <!-- Hero Section -->
    <section class="hero d-flex align-items-center">
        <div class="container text-center">
            <h1 class="display-3 fw-bold mb-4">Welcome to Hospital Management System</h1>
            <p class="lead mb-5 fs-4">Streamlining healthcare services with modern technology</p>
            <div>
                <a href="patients_index.php" class="btn btn-primary cta-button">Patient Portal</a>
                <a href="#features" class="btn btn-outline-light cta-button">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Features</h2>
            <div class="row">
                <div class="col-md-4">
                    <a href="patients_index.php">
                        <div class="feature-box">
                            <h3>Medical Certificates</h3>
                            <p>Easy request and management of medical certificates online</p>
                        </div>
                    </a>  
                </div>
                <div class="col-md-4">
                    <a href="patients_index.php">
                        <div class="feature-box">
                            <h3>Doctor Consultation</h3>
                            <p>Connect with healthcare professionals seamlessly</p>
                        </div>
                    </a> 
                </div>
                <div class="col-md-4">
                    <a href="patients_index.php">
                        <div class="feature-box">
                            <h3>Medical History</h3>
                            <p>Access your medical records anytime, anywhere</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2>About the System</h2>
                    <p class="lead">Our barangay healthcare system is designed to make medical services more accessible and efficient for the community.</p>
                    <p>This system is meant for making the management of patient information simple and effective. The information made available include personal details and medical histories, 
                        prescriptions, and much more. You can also schedule appointments with the health center directly through this system, further enhancing the easiness with 
                        which the patient schedules and tracks his or her visits.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <h2 class="text-center mb-3">Contact Us @ </h2>
            <h5 class="text-center">09602020493</h5>
            <h5 class="text-center mb-5">barangayvictoriareyesdasma@gmail.com</h5>
            <div class="row">
                <div class="col-md-6 mx-auto">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#contact">
                        <?php
                        if (isset($_SESSION['contact_success'])) {
                            echo '<div class="alert alert-success">' . $_SESSION['contact_success'] . '</div>';
                            unset($_SESSION['contact_success']);
                        }
                        ?>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="message" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Barangay Health Care System</h3>
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
    <div class="preloader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <script>
        // Remove preloader when page loads
        window.addEventListener('load', function() {
            document.querySelector('.preloader').style.display = 'none';
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>