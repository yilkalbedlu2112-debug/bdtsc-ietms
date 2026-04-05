<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role'])) {
    header('Location: auth/login_process.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC IETMS | Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            transition: color 0.25s ease, transform 0.25s ease;
        }
        .nav-link:hover {
            color: #dbeafe !important;
            transform: translateY(-1px);
        }
        .navbar-custom {
            background: #0f172a;
        }
        .btn-login {
            background: #38bdf8;
            color: white;
        }
        .btn-login:hover {
            background: #22d3ee;
            color: white;
        }
        .hero {
            position: relative;
            background: linear-gradient(180deg, rgba(15,23,42,0.76) 0%, rgba(15,23,42,0.82) 100%),
                        url('assets/images/factory_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            min-height: calc(100vh - 72px);
            display: flex;
            align-items: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            pointer-events: none;
        }
        .hero .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-size: clamp(2.5rem, 4vw, 4rem);
            font-weight: 700;
            line-height: 1.05;
        }
        .hero p {
            color: rgba(255,255,255,0.9);
            max-width: 640px;
        }
        .btn-login,
        .btn-info {
            box-shadow: 0 16px 35px rgba(56, 189, 248, 0.24);
        }
        .btn-login:hover,
        .btn-info:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px rgba(56, 189, 248, 0.28);
        }
        @media (max-width: 991px) {
            .hero {
                min-height: auto;
                padding: 4rem 0;
            }
        }
        @media (max-width: 767px) {
            .hero {
                padding: 4.5rem 0;
            }
            .hero h1 {
                font-size: 2.5rem;
            }
            .hero p {
                max-width: 100%;
            }
        }
        .feature-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 32px 100px rgba(15, 23, 42, 0.14);
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            font-size: 1.5rem;
        }
        .feature-icon.bg-blue { background: #0ea5e9; color: white; }
        .feature-icon.bg-green { background: #22c55e; color: white; }
        .feature-icon.bg-purple { background: #a855f7; color: white; }
        .footer {
            padding: 3rem 0;
            background: #0f172a;
            color: rgba(255,255,255,0.72);
        }
        .footer a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
        }
        .footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/images/Bahr dar Textile0.png" alt="BDTSC Logo" width="54" height="54" class="me-3 rounded-circle border border-2 border-white" onerror="this.onerror=null;this.src='https://via.placeholder.com/54/0f172a/ffffff?text=BD';">
                <div>
                    <span class="d-block text-white fw-bold">BDTSC IETMS</span>
                    <small class="text-white-50">Industry Maintenance System</small>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item px-2">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item px-2">
                        <a class="nav-link" href="#about">About Factory</a>
                    </li>
                    <li class="nav-item px-2">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item px-2">
                        <a class="btn btn-login rounded-pill px-4 py-2" href="auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase text-info mb-3 fw-semibold">Bahir Dar Textile Share Company</p>
                    <h1>Industrial Machine Maintenance & Task Management System</h1>
                    <p class="lead mt-4">Optimizing production through real-time maintenance tracking and performance analytics for Bahir Dar Textile Share Company.</p>
                    <a href="auth/login.php" class="btn btn-lg btn-info rounded-pill mt-4 px-5">Get Started</a>
                </div>
                <div class="col-lg-5 mt-5 mt-lg-0 text-center">
                    <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px);">
                        <img src="assets/images/Bahr dar Textile0.png" alt="Hero Image" class="img-fluid rounded-4 shadow" style="max-height: 360px;" onerror="this.onerror=null;this.src='https://via.placeholder.com/420x320/0f172a/ffffff?text=BDTSC';">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="about">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="badge bg-info bg-opacity-15 text-info rounded-pill px-3 py-2 mb-3">Why BDTSC IETMS</span>
                    <h2 class="fw-bold">A smarter operations platform for maintenance, production, and workers.</h2>
                    <p class="text-muted mt-3">Our system brings clarity to industrial maintenance with automatic task distribution, analytics, and secure audit tracking.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-xl-4">
                    <div class="card feature-card p-4 h-100">
                        <div class="feature-icon bg-blue">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h5 class="fw-semibold">Automated Tasking</h5>
                        <p class="text-muted">Assign work by role automatically so every worker receives the right job at the right time.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card feature-card p-4 h-100">
                        <div class="feature-icon bg-green">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h5 class="fw-semibold">Real-time Analytics</h5>
                        <p class="text-muted">Monitor machine health and performance with live charts and meaningful KPIs.</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card feature-card p-4 h-100">
                        <div class="feature-icon bg-purple">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h5 class="fw-semibold">Secure Audit Logs</h5>
                        <p class="text-muted">Track every action in the system with secure, tamper-resistant logs.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white" id="contact">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h3 class="fw-bold">Contact Us</h3>
                    <p class="text-muted">For setup, support, or a demo, reach out and we'll help Bahir Dar Textile connect operations with intelligence.</p>
                    <ul class="list-unstyled text-muted mb-0">
                        <li class="mb-2"><strong>Address:</strong> Bahir Dar Textile Share Company</li>
                        <li class="mb-2"><strong>Email:</strong> support@bdtsc.com</li>
                        <li><strong>Phone:</strong> +251 58 123 4567</li>
                    </ul>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="card shadow-sm rounded-4 p-4 border-0">
                        <h5 class="fw-semibold">Get started now</h5>
                        <p class="text-muted">Login to begin managing maintenance tasks and workforce operations.</p>
                        <a href="auth/login.php" class="btn btn-dark rounded-pill px-4">Login to System</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer text-center text-white">
        <div class="container">
            <p class="mb-2">© 2026 Bahir Dar Textile Share Company. All rights reserved.</p>
            <div>
                <a href="#home" class="text-white text-decoration-none me-3">Home</a>
                <a href="#about" class="text-white text-decoration-none me-3">About</a>
                <a href="#contact" class="text-white text-decoration-none">Contact</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
