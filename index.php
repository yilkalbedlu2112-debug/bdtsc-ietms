<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Include database configuration
    require_once 'includes/db.php';

    // Session check: If user is logged in, redirect to appropriate dashboard
    if (isset($_SESSION['user_role'])) {
        switch ($_SESSION['user_role']) {
            case 'General Manager':
                header('Location: admin/dashboard.php');
                exit();
            case 'Department Manager':
                header('Location: manager/dashboard.php');
                exit();
            case 'Shift Leader':
                header('Location: shift_leader/dashboard.php');
                exit();
            case 'Supervisor':
                header('Location: supervisor/dashboard.php');
                exit();
            case 'Technician':
                header('Location: technician/dashboard.php');
                exit();
            case 'Employee':
                header('Location: employee/dashboard.php');
                exit();
            case 'Production and Technique Deputy General Manager':
                header('Location: deputy_gm/dashboard.php');
                exit();
            default:
                // Unknown user_role, redirect to login
                header('Location: auth/login.php');
                exit();
        }
    }

} catch (Exception $e) {
    // Error handling - show error instead of blank page
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
    echo "</div>";
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
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0d8e95ff;
            color: #0f172a;
        }
        .amharic-text {
            font-family: 'Noto Sans Ethiopic', 'Nyala', serif;
            color: rgba(255, 255, 255, 0.7);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        .english-text {
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
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
            background: #052a81ff;
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
            background: linear-gradient(0deg, rgba(7, 53, 159, 0.76) 0%, rgba(33, 67, 147, 0.82) 100%),
                        url('assets/images/bkg.jpg');
            background-size: cover;
            background-position: bottom;
            background-repeat: no-repeat;
            color: white;
            min-height: calc(100vh - 150px);
            display: flex;
            align-items: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(46, 74, 140, 0.45);
            pointer-events: none;
        }
        .hero .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 3.5vw, 3.5rem);
            font-weight: 200;
            line-height: 1.05;
            margin-bottom: 1rem;
        }
        .hero h2 {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 500;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }
        .hero p {
            color: rgba(29, 232, 239, 0.9);
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
                font-size: 2.2rem;
            }
            .hero h2 {
                font-size: 1.4rem;
            }
            .hero p {
                max-width: 100%;
            }
            .navbar-brand div span {
                font-size: 0.75rem !important;
            }
            .navbar-brand div .english-text {
                font-size: 0.9rem !important;
            }
        }
        .feature-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 32px 100px rgba(72, 123, 241, 0.14);
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
            background: #123fa7;
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
                <img src="assets/images/bdtsc_logo.png" alt="BDTSC Logo" width="54" height="54" class="me-2 me-sm-3 rounded-circle border border-2 border-white" onerror="this.onerror=null;this.src='https://via.placeholder.com/54/0f172a/ffffff?text=BD';">
                <div>
                    <span class="d-block text-white fw-bold amharic-text" style="font-size: 0.9rem;">የባ/ጨ/አ/ማ የኢ/ሠ/ሥራ አመራር/ሥርዓት</span>
                    <span class="d-block text-white fw-bold english-text" style="font-size: 1.1rem;">BDTSC-IETMS</span>
                    <small class="text-white-50 english-text d-none d-sm-block">Industrial Employee Task Management</small>
                </div>
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
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
                    <li class="nav-item px-2 mt-3 mt-lg-0">
                        <a class="btn btn-login rounded-pill px-4 py-2 w-100 w-lg-auto" href="auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="container hero-content">
            <div class="row align-items-center g-4">
                <div class="col-lg-7 text-center text-lg-start">
                    <h2 class="amharic-text mb-2">የባህር ዳር ጨርቃጨርቅ አክሲዮን ማህበር የኢንዱስትሪ ሰራተኞች የስራ አመራር ስርዓት</h2>
                    <h4 class="english-text mb-3">Industrial Employee Task Management System of Bahir Dar Textile Share Company (BDTSC-IETMS)</h4>
                    <p class="lead mt-4 english-text mx-auto mx-lg-0" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);">Optimizing production through real-time Task Management System and performance analytics for Bahir Dar Textile Share Company.</p>
                    <div class="mt-4">
                        <a href="auth/login.php" class="btn btn-lg btn-info rounded-pill px-5">Get Started</a>
                    </div>
                </div>
                <div class="col-lg-5 mt-5 mt-lg-0 text-center">
                    <div class="p-3 p-md-4 rounded-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px);">
                        <img src="assets/images/Bahr dar Textile0.png" alt="Hero Image" class="img-fluid rounded-4 shadow" style="max-height: 360px; width: 100%; object-fit: cover;" onerror="this.onerror=null;this.src='https://via.placeholder.com/420x320/0f172a/ffffff?text=BDTSC';">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div id="react-features-root"></div>
    <div id="react-vision-root"></div>

    <footer class="footer" id="contact">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <h5 class="text-white mb-3">BDTSC IETMS</h5>
                    <p>Industrial Employee Task Management System for Bahir Dar Textile Share Company</p>
                    <p><i class="bi bi-geo-alt me-2"></i>Bahir Dar, Ethiopia</p>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <h5 class="text-white mb-3">Contact</h5>
                    <p><i class="bi bi-envelope me-2"></i>info@bdtsc.et</p>
                    <p><i class="bi bi-telephone me-2"></i>+251-920-297-671</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2026 Bahir Dar Textile Share Company. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/react.development.js"></script>
    <script src="js/react-dom.development.js"></script>

    <script>
        (function() {
            const originalWarn = console.warn;
            console.warn = function(...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('in-browser Babel transformer')) {
                    return; 
                }
                if (args[0] && typeof args[0] === 'string' && args[0].includes('React DevTools')) {
                    return; 
                }
                originalWarn.apply(console, args);
            };
        })();
    </script>

    <script src="js/babel.min.js"></script>

    <script type="text/babel">
        // 1. Component for Features Section
        function FeaturesComponent() {
            const features = [
                {
                    title: "Maintenance Tracking",
                    desc: "Real-time monitoring of equipment maintenance requests and technician assignments.",
                    iconClass: "bi bi-tools",
                    colorClass: "bg-blue"
                },
                {
                    title: "Performance Analytics",
                    desc: "Comprehensive reports and analytics for production efficiency and maintenance performance.",
                    iconClass: "bi bi-graph-up",
                    colorClass: "bg-green"
                },
                {
                    title: "Secure Audit Trail",
                    desc: "Complete logging of all system activities with user_role-based access control.",
                    iconClass: "bi bi-shield-check",
                    colorClass: "bg-purple"
                }
            ];

            return (
                <section className="py-5" id="about">
                    <div className="container">
                        <div className="row justify-content-center text-center mb-5">
                            <div className="col-lg-8">
                                <span className="badge bg-info bg-opacity-15 text-info rounded-pill px-3 py-2 mb-3">Why BDTSC IETMS</span>
                                <h2 className="fw-bold">A smarter operations platform for maintenance, production, and workers.</h2>
                                <p className="text-muted mt-3">Our system brings clarity to industrial maintenance with automatic task distribution, analytics, and secure audit tracking.</p>
                            </div>
                        </div>
                        <div className="row g-4">
                            {features.map((feat, index) => (
                                <div className="col-12 col-md-6 col-xl-4" key={index}>
                                    <div className="feature-card p-4 h-100">
                                        <div className={`feature-icon ${feat.colorClass}`}>
                                            <i className={feat.iconClass}></i>
                                        </div>
                                        <h5 className="fw-bold mb-3">{feat.title}</h5>
                                        <p className="text-muted mb-0">{feat.desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            );
        }

        // 2. Component for Vision & Mission Section
        function VisionMissionComponent() {
            return (
                <section className="py-5 bg-white">
                    <div className="container">
                        <div className="row g-4 g-lg-5 align-items-stretch">
                            <div className="col-12 col-lg-6">
                                <div className="p-4 h-100 rounded-4 shadow-sm border-start border-4 border-info bg-light">
                                    <h3 className="fw-bold mb-3" style={{color: '#123fa7', fontFamily: 'Segoe UI'}}>ራዕይ (Vision)</h3>
                                    <p className="text-muted fs-5 italic">"በ2030 በምስራቅ አፍሪካ ተመራጭና ተወዳዳሪ የጨርቃጨርቅ ምርት አቅራቢ መሆን።"</p>
                                    <p className="text-secondary small">"To be a preferred and competitive textile product provider in East Africa by 2030."</p>
                                </div>
                            </div>
                            <div className="col-12 col-lg-6">
                                <div className="p-4 h-100 rounded-4 shadow-sm border-start border-4 border-primary bg-light">
                                    <h3 className="fw-bold mb-3" style={{color: '#123fa7', fontFamily: 'Segoe UI'}}>ተልዕኮ (Mission)</h3>
                                    <p className="text-muted fs-5">"ጥራቱን የጠበቀ ምርት በማቅረብ፣ የቴክኖሎጂ አጠቃቀማችንን በማሳደግ እና የሰራተኞቻችንን ብቃት በማጎልበት የደንበኞቻችንን ፍላጎት ማርካት።"</p>
                                    <p className="text-secondary small">"Satisfying customer needs by providing quality products, enhancing technology usage, and developing employee competence."</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            );
        }

        // 🛠️ Render Components safely without multiple createRoot warnings
        const featuresContainer = document.getElementById('react-features-root');
        if (featuresContainer && !window.featuresRootInstance) {
            window.featuresRootInstance = window.ReactDOM.createRoot(featuresContainer);
            window.featuresRootInstance.render(<FeaturesComponent />);
        } else if (window.featuresRootInstance) {
            window.featuresRootInstance.render(<FeaturesComponent />);
        }

        const visionContainer = document.getElementById('react-vision-root');
        if (visionContainer && !window.visionRootInstance) {
            window.visionRootInstance = window.ReactDOM.createRoot(visionContainer);
            window.visionRootInstance.render(<VisionMissionComponent />);
        } else if (window.visionRootInstance) {
            window.visionRootInstance.render(<VisionMissionComponent />);
        }
    </script>
</body>
</html>