<?php
// includes/admin_header.php
require_once __DIR__ . '/lang.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard'); ?> - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <style>
        body { font-family: 'Inter', 'Noto Sans Ethiopic', sans-serif; }
        .sidebar {
            min-height: 100vh;
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
        .sidebar .profile-box {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar .profile-box img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .sidebar a {
            color: rgba(241,245,249,0.85);
            text-decoration: none;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            transition: all 0.25s ease;
            font-weight: 500;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
        }
        .sidebar a i { margin-right: 14px; font-size: 1.1rem; }
        .main-content { margin-left: 280px; padding: 28px; padding-top: 60px; min-height: 100vh; }
    </style>
</head>
<body>

<div class="language-switcher">
    <a href="?lang=en" class="<?php echo $current_lang == 'en' ? 'active' : ''; ?>">EN</a> |
    <a href="?lang=am" class="<?php echo $current_lang == 'am' ? 'active' : ''; ?>">አማ</a>
</div>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-auto p-0 sidebar glass-sidebar" id="sidebar">
            <div class="brand">
                <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2"></i>BDTSC IETMS</h5>
            </div>
            <div class="profile-box">
                <img src="../assets/images/Yenesew Mulu.jpg" alt="Profile" onerror="this.src='https://via.placeholder.com/90/0f172a/ffffff?text=GM';">
                <div class="mt-3 fw-bold text-white"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'General Manager'); ?></div>
                <div class="small opacity-75">General Manager</div>
            </div>
            
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
            <a href="manage_users.php"><i class="bi bi-people-fill"></i> <?php echo __('users'); ?></a>
            <a href="manage_departments.php"><i class="bi bi-building"></i> <?php echo __('departments'); ?></a>
            <a href="audit_trail.php"><i class="bi bi-shield-check"></i> <?php echo __('audit_logs'); ?></a>
            <a href="reports.php"><i class="bi bi-graph-up"></i> <?php echo __('reports'); ?></a>
            <hr class="border-secondary mx-3">
            <a href="../auth/change_password.php"><i class="bi bi-key"></i> <?php echo __('settings'); ?></a>
            <a href="../auth/logout.php" class="text-warning"><i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?></a>
        </div>
    
        <div class="col main-content">