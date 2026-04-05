<?php
// bdtsc-ietms/includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .sidebar {
            min-height: 100vh;
            background: #0f172a;
            color: white;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.25);
            position: fixed;
            width: 280px;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .brand {
            padding: 24px 20px 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
        }
        .sidebar .brand h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.02em;
        }
        .sidebar .profile-box {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar .profile-box img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.25);
        }
        .sidebar .profile-box .name {
            margin-top: 12px;
            font-size: 1rem;
            font-weight: 600;
            color: #f8fafc;
        }
        .sidebar .profile-box .role {
            margin-top: 4px;
            font-size: 0.85rem;
            color: rgba(241, 245, 249, 0.7);
        }
        .sidebar a {
            color: rgba(241,245,249,0.85);
            text-decoration: none;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            transition: all 0.25s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.06);
            color: #ffffff;
            border-left-color: #2563eb;
            transform: translateX(2px);
        }
        .sidebar a i {
            margin-right: 14px;
            width: 22px;
            text-align: center;
            font-size: 1.05rem;
        }
        .sidebar hr {
            border-color: rgba(255,255,255,0.08);
            margin: 10px 20px;
        }
        .sidebar .logout-link {
            margin-top: auto;
            padding-bottom: 22px;
        }
        .main-content {
            margin-left: 280px;
            background-color: #f8fafc;
            min-height: 100vh;
            padding: 28px;
        }
        .main-content h1, .main-content h2, .main-content h3, .main-content h4, .main-content h5 {
            color: #0f172a;
        }
        @media (max-width: 992px) {
            .sidebar {
                width: 260px;
            }
            .main-content {
                margin-left: 260px;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                height: 100%;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding-top: 72px;
            }
            .mobile-menu-toggle {
                display: block !important;
                position: fixed;
                top: 18px;
                left: 18px;
                z-index: 1100;
                background: #0f172a;
                color: white;
                border: none;
                border-radius: 12px;
                padding: 10px 14px;
                box-shadow: 0 10px 30px rgba(15,23,42,0.25);
            }
        }
        .mobile-menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-auto p-0 sidebar" id="sidebar">
            <div class="brand">
                <h4><i class="bi bi-gear-fill me-2"></i>BDTSC IETMS</h4>
                <small class="text-light opacity-75">Admin Panel</small>
            </div>
            <?php
            $profile_image_path = __DIR__ . '/../assets/images/Bahr dar Textile.png';
            $profile_image_src = file_exists($profile_image_path) ? '../assets/images/Bahr dar Textile.png' : 'https://via.placeholder.com/100/0f172a/ffffff?text=GM';
            ?>
            <div class="profile-box">
                <img src="<?php echo $profile_image_src; ?>" alt="Profile Photo" onerror="this.onerror=null;this.src='https://via.placeholder.com/100/0f172a/ffffff?text=GM';">
                <div class="name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'General Manager'); ?></div>
                <div class="role">General Manager</div>
            </div>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="manage_users.php"><i class="bi bi-people-fill"></i> Manage Users</a>
            <a href="manage_departments.php"><i class="bi bi-building"></i> Departments</a>
            <a href="audit_trail.php"><i class="bi bi-shield-check"></i> Audit Trail</a>
            <a href="reports.php"><i class="bi bi-graph-up"></i> Reports</a>
            <hr>
            <a href="../auth/logout.php" class="logout-link text-warning"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
        <div class="col main-content">
            <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>