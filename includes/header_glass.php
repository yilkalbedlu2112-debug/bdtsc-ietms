<?php
// includes/header_glass.php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_role = $_SESSION['user_role'] ?? 'Employee';
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$base_url = '/bdtsc-ietms'; // Adjust if deploying in root
$current_page = basename($_SERVER['PHP_SELF']);

// --- አዲስ የተጨመረ የፎቶ ዳታ ---
$profile_pic = $_SESSION['profile_pic'] ?? 'default_user.jpg';
$image_path = $base_url . "/assets/images/" . $profile_pic;
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BDTSC IETMS - <?php echo htmlspecialchars($user_role); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/glassmorphism.css">
    <style>
    body {
        font-family: 'Inter', 'Noto Sans Ethiopic', sans-serif;
        background: #f8fafc;
    }

    .sidebar {
        position: fixed;
        width: 280px;
        z-index: 1050;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        transition: transform 0.3s ease, width 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        flex-shrink: 0;
        padding-bottom: 10px;
    }

    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 20px;
    }

    /* Custom scrollbar for sidebar */
    .sidebar-nav::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-nav::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .sidebar.collapsed {
        transform: translateX(-100%);
    }

    .sidebar .brand {
        padding: 24px 20px 16px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .sidebar .profile-box {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .sidebar .profile-box img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.5);
        transition: transform 0.2s ease;
    }

    .sidebar .profile-box img:hover {
        transform: scale(1.05);
    }

    .sidebar a {
        color: rgba(241,245,249,0.85);
        text-decoration: none;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        transition: all 0.25s ease;
        border-left: 4px solid transparent;
    }

    .sidebar a:hover, .sidebar a.active {
        background: rgba(255,255,255,0.1);
        color: #ffffff;
        border-left-color: #667eea;
        text-decoration: none;
    }

    .sidebar a i {
        margin-right: 12px;
        width: 20px;
        text-align: center;
    }

    .main-content {
        margin-left: 280px;
        padding: 28px;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }

    /* Mobile Styles */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0 !important;
            padding: 20px 15px;
        }

        .mobile-menu-toggle {
            display: block !important;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1051;
            background: rgba(15, 23, 42, 0.9);
            border: none;
            border-radius: 8px;
            padding: 10px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .mobile-menu-toggle:hover {
            background: rgba(15, 23, 42, 1);
            color: white;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1049;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }

    .mobile-menu-toggle {
        display: none;
    }

    /* Language Switcher */
    .language-switcher {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 5px 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .language-switcher a {
        color: #374151;
        text-decoration: none;
        font-weight: 600;
        margin: 0 5px;
        padding: 2px 8px;
        border-radius: 12px;
        transition: all 0.2s ease;
    }

    .language-switcher a:hover {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .language-switcher a.active {
        background: #3b82f6;
        color: white;
    }

    @media print {
        .sidebar, .language-switcher, .mobile-menu-toggle, .sidebar-overlay {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
    }
    </style>

</head>
<body>

<div class="language-switcher">
    <a href="?lang=en" class="<?php echo $current_lang == 'en' ? 'active' : ''; ?>">EN</a> |
    <a href="?lang=am" class="<?php echo $current_lang == 'am' ? 'active' : ''; ?>">አማ</a>
</div>

<div class="container-fluid p-0">
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle btn" onclick="toggleSidebar()" aria-label="Toggle navigation">
        <i class="bi bi-list fs-5"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="row g-0">
        <div class="col-auto p-0 sidebar glass-sidebar" id="sidebar">
            <!-- Fixed Header Section -->
            <div class="sidebar-header">
                <div class="brand">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2"></i>BDTSC IETMS</h5>
                </div>
                <div class="profile-box">
                    <img src="<?php echo $image_path; ?>"
                         alt="Profile"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=random&color=fff';">

                    <div class="mt-3 fw-bold text-white"><?php echo $full_name; ?></div>
                    <div class="small opacity-75"><?php echo htmlspecialchars($user_role); ?></div>
                </div>
            </div>

            <!-- Scrollable Navigation Section -->
            <div class="sidebar-nav">
            <?php if ($user_role === 'General Manager' || $user_role === 'Admin'): ?>
                <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_users.php" class="<?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>"><i class="bi bi-people-fill"></i> <?php echo __('users'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_departments.php" class="<?php echo $current_page == 'manage_departments.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> <?php echo __('departments'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/audit_trail.php" class="<?php echo $current_page == 'audit_trail.php' ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> <?php echo __('audit_logs'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> <?php echo __('reports'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/admin_approval.php" class="<?php echo $current_page == 'admin_approval.php' ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> Password Requests
                    <?php
                        $stmt_count = $pdo->query("SELECT COUNT(*) FROM users WHERE reset_token IS NOT NULL AND reset_approved = 0");
                        $pending_count = $stmt_count->fetchColumn();
                        if ($pending_count > 0):
                    ?>
                    <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.65rem;">
                        <?php echo $pending_count > 9 ? '9+' : $pending_count; ?>
                    </span>
                    <?php endif; ?>
                </a>

                <!--department manager and engineering manager share some links but not all, so we check role again for those specific links-->
            <?php elseif ($user_role === 'Department Manager'): ?>
                <a href="<?php echo $base_url; ?>/manager/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?>
                </a>
    <a href="<?php echo $base_url; ?>/manager/create_task.php" class="<?php echo $current_page == 'create_task.php' ? 'active' : ''; ?>">
        <i class="bi bi-plus-square"></i> Create Task
    </a>
    <!-- Fix 3: Corrected icon from bi-shield-lock to bi-tools -->
    <a href="<?php echo $base_url; ?>/manager/maintenance_list.php" class="<?php echo $current_page == 'maintenance_list.php' ? 'active' : ''; ?>">
        <i class="bi bi-tools"></i> Maintenance Log
    </a>
    <!-- Fix 3: Corrected icon from bi-shield-lock to bi-inbox -->
    <a href="<?php echo $base_url; ?>/manager/view_requests.php" class="<?php echo $current_page == 'view_requests.php' ? 'active' : ''; ?> position-relative">
        <i class="bi bi-inbox"></i> Cross-Dept Requests
        <?php
            $notif_dm = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE receiver_dept_id = ? AND is_read_by_receiver = 0");
            $notif_dm->execute([$_SESSION['dept_id'] ?? 0]);
            $notif_dm_count = (int)$notif_dm->fetchColumn();
            if ($notif_dm_count > 0):
        ?>
        <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.65rem;"><?php echo $notif_dm_count > 9 ? '9+' : $notif_dm_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo $base_url; ?>/manager/productivity_analytics.php" class="<?php echo $current_page == 'productivity_analytics.php' ? 'active' : ''; ?>">
        <i class="bi bi-graph-up-arrow"></i> Productivity
    </a>
    <a href="<?php echo $base_url; ?>/manager/audit_logs.php" class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
        <i class="bi bi-shield-check"></i> Audit Logs
    </a>
    <a href="<?php echo $base_url; ?>/manager/generate_report.php" class="<?php echo $current_page == 'generate_report.php' ? 'active' : ''; ?>">
        <i class="bi bi-file-earmark-pdf"></i> <?php echo __('reports'); ?>
    </a>

    <!--Engineering manager has some overlapping links with department manager but not all, so we check role again for those specific links-->
    
            <?php elseif ($user_role === 'Engineering Manager'): ?>
    <a href="<?php echo $base_url; ?>/manager/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?>
    </a>
    <a href="<?php echo $base_url; ?>/manager/maintenance_list.php" class="<?php echo $current_page == 'maintenance_list.php' ? 'active' : ''; ?>">
        <i class="bi bi-tools"></i> Maintenance Log
    </a>
    <a href="<?php echo $base_url; ?>/manager/view_requests.php" class="<?php echo $current_page == 'view_requests.php' ? 'active' : ''; ?> position-relative">
        <i class="bi bi-send-check"></i> Dispatch Center
        <?php
            $notif_eng = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE receiver_dept_id = ? AND is_read_by_receiver = 0");
            $notif_eng->execute([$_SESSION['dept_id'] ?? 0]);
            $notif_eng_count = (int)$notif_eng->fetchColumn();
            if ($notif_eng_count > 0):
        ?>
        <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.65rem;"><?php echo $notif_eng_count > 9 ? '9+' : $notif_eng_count; ?></span>
        <?php endif; ?> </a>
    <a href="<?php echo $base_url; ?>/manager/create_task.php" class="<?php echo $current_page == 'create_task.php' ? 'active' : ''; ?>">
        <i class="bi bi-plus-square"></i> Create Task </a>
    <a href="<?php echo $base_url; ?>/manager/productivity_analytics.php" class="<?php echo $current_page == 'productivity_analytics.php' ? 'active' : ''; ?>">
        <i class="bi bi-graph-up-arrow"></i> Productivity </a>
    <a href="<?php echo $base_url; ?>/manager/audit_logs.php" class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
        <i class="bi bi-shield-check"></i> Audit Logs </a>
    <a href="<?php echo $base_url; ?>/manager/generate_report.php" class="<?php echo $current_page == 'generate_report.php' ? 'active' : ''; ?>">
        <i class="bi bi-file-earmark-pdf"></i> <?php echo __('reports'); ?> </a>


        <!--Deputy General Manager has some overlapping links with department manager but not all, so we check role again for those specific links-->
            <?php elseif ($user_role === 'Deputy General Manager'): ?>
                <a href="<?php echo $base_url; ?>/deputy_gm/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_departments.php" class="<?php echo $current_page == 'manage_departments.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> <?php echo __('departments'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/audit_trail.php" class="<?php echo $current_page == 'audit_trail.php' ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> <?php echo __('audit_logs'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> <?php echo __('reports'); ?></a>

                <!-- Shift leader has some overlapping links with employee but not all, so we check role again for those specific links-->
            <?php elseif ($user_role === 'Shift Leader'): ?>
                <a href="<?php echo $base_url; ?>/shift_leader/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/shift_leader/submit_report.php" class="<?php echo $current_page == 'submit_report.php' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-plus"></i> <?php echo __('Submit Report'); ?></a>


                <!--Supervisor has some overlapping links with employee but not all, so we check role again for those specific links-->
            <?php elseif ($user_role === 'Supervisor'): ?>
                <a href="<?php echo $base_url; ?>/supervisor/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">  <i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?> </a>
                <a href="<?php echo $base_url; ?>/manager/create_task.php" class="<?php echo $current_page == 'create_task.php' ? 'active' : ''; ?>"> <i class="bi bi-send-check"></i> Create New Task </a>
            

                <!--Employee is the default role, so we show basic links if no other role matches-->
                <?php else: ?>
                <a href="<?php echo $base_url; ?>/employee/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/employee/report_production.php" class="<?php echo $current_page == 'report_production.php' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-plus"></i> <?php echo __('Report Production'); ?></a>
            <?php endif; ?>
                
            <hr class="border-secondary mx-3">
            <a href="<?php echo $base_url; ?>/auth/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="<?php echo $base_url; ?>/auth/logout.php" class="text-warning"><i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?></a>
            </div>
        </div>
    
        <div class="col main-content">
