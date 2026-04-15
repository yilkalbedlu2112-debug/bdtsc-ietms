<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}
$dept_name = $_SESSION['dept_name'] ?? 'Department';
$full_name = $_SESSION['full_name'] ?? 'Manager';
$logo_src = file_exists(__DIR__ . '/../assets/images/Bahr dar Textile0.png') ? '../assets/images/Bahr dar Textile0.png' : 'https://via.placeholder.com/52/0f172a/ffffff?text=BD';
?>
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #eef2f7; font-family: 'Inter', sans-serif; }
        .amharic-font { font-family: 'Nyala', 'Impact', sans-serif; }
        .topbar {
            background: #0f172a;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .topbar .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
        }
        .topbar .navbar-brand img {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.16);
        }
        .sidebar {
            min-height: calc(100vh - 72px);
            width: 92px;
            background: #0f172a;
            color: #fff;
            padding-top: 1rem;
            position: fixed;
            top: 72px;
            left: 0;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 18px 0;
            display: flex;
            justify-content: center;
            font-size: 1.5rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #38bdf8;
        }
        .sidebar .nav-link span { display: none; }
        .main-content {
            margin-left: 92px;
            padding: 24px;
        }
        .page-title { font-size: 1.9rem; }
        .card-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: white;
            margin-right: 12px;
        }
        .card-border-blue { border-left: 5px solid #0ea5e9; }
        .card-border-yellow { border-left: 5px solid #facc15; }
        .card-border-green { border-left: 5px solid #22c55e; }
        .table-responsive { box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
        .table thead th { background: #0f172a; color: white; }
        @media (max-width: 991px) {
            .sidebar { position: relative; width: 100%; height: auto; top: 0; }
            .main-content { margin-left: 0; padding-top: 24px; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg topbar px-4 py-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="<?php echo $logo_src; ?>" alt="BDTSC Logo">
            <div class="d-flex flex-column">
                <span>BDTSC IETMS</span>
                <small class="text-white-50">Department Manager</small>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <div class="fw-semibold"><?php echo htmlspecialchars($full_name); ?></div>
                <small class="text-white-50"><?php echo htmlspecialchars($dept_name); ?></small>
            </div>
            <a class="btn btn-outline-light btn-sm" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>
<div class="sidebar d-flex flex-column align-items-center">
    <a class="nav-link active" href="dashboard.php" title="Dashboard"><i class="bi bi-speedometer2"></i></a>
    <a class="nav-link" href="create_task.php" title="Create Task"><i class="bi bi-plus-circle"></i></a>
    <?php if (!isset($showMaintenanceMenu) || $showMaintenanceMenu): ?>
        <a class="nav-link" href="#maintenance" title="Maintenance"><i class="bi bi-tools"></i></a>
    <?php endif; ?>
    <?php if (!isset($showProductionMenu) || $showProductionMenu): ?>
        <a class="nav-link" href="#production" title="Production"><i class="bi bi-bar-chart-line"></i></a>
    <?php endif; ?>
    <a class="nav-link" href="generate_report.php" title="Reports"><i class="bi bi-file-earmark-pdf"></i></a>
    <a class="nav-link" href="#reports" title="Analytics"><i class="bi bi-graph-up"></i></a>
    <a class="nav-link mt-auto" href="../auth/logout.php" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
</div>
<div class="main-content">
