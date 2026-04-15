<?php
// includes/header_glass.php
require_once __DIR__ . '/lang.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_role = $_SESSION['user_role'] ?? 'Employee';
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'User');
$base_url = '/bdtsc-ietms'; // Adjust if deploying in root
$current_page = basename($_SERVER['PHP_SELF']);
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
    body { font-family: 'Inter', 'Noto Sans Ethiopic', sans-serif; }
    
    .sidebar {
        min-height: 100vh;
        position: fixed;
        width: 280px;
        z-index: 1000;
        overflow-y: auto; /* ይዘቱ ከበዛ ወደ ላይና ታች እንዲንቀሳቀስ */
        background: rgba(9, 39, 96, 0.95); /* ለሳይድባሩ የተሻለ ከለር */
    }

    /* በስልክ ጊዜ (Mobile view) Sidebar-ን ለማስተካከል */
    @media (max-width: 991.98px) {
        .sidebar {
            position: relative; /* ስልክ ላይ ከገጹ ጋር አብሮ እንዲንቀሳቀስ */
            width: 100%;
            min-height: auto;
        }
        .main-content {
            margin-left: 0 !important; /* ስልክ ላይ ሳይድባሩ ቦታ እንዳይዝ */
            padding: 15px !important;
        }
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
    }

    .sidebar a {
        color: rgba(241,245,249,0.85);
        text-decoration: none;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        transition: all 0.25s ease;
    }

    .sidebar a:hover, .sidebar a.active {
        background: rgba(255,255,255,0.1);
        color: #ffffff;
        border-left: 4px solid #667eea;
    }

    .main-content { 
        margin-left: 280px; 
        padding: 28px; 
        min-height: 100vh; 
        transition: all 0.3s ease;
    }

    /* --- ለሪፖርት (Print) ብቻ የሚሆን ማስተካከያ --- */
    @media print {
        .sidebar, .language-switcher, .text-warning {
            display: none !important; /* ፒዲኤፍ ላይ ሳይድባር እንዲጠፋ */
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
    <div class="row g-0">
        <div class="col-auto p-0 sidebar glass-sidebar" id="sidebar">
            <div class="brand">
                <h5 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2"></i>BDTSC IETMS</h5>
            </div>
            <div class="profile-box">
                <img src="<?php echo $base_url; ?>/assets/images/deputy Manager.jpg" alt="Profile" onerror="this.src='https://via.placeholder.com/90/0f172a/ffffff?text=User';">
                <div class="mt-3 fw-bold text-white"><?php echo $full_name; ?></div>
                <div class="small opacity-75"><?php echo htmlspecialchars($user_role); ?></div>
            </div>
            
            <?php if ($user_role === 'General Manager' || $user_role === 'Admin'): ?>
                <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_users.php" class="<?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>"><i class="bi bi-people-fill"></i> <?php echo __('users'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_departments.php" class="<?php echo $current_page == 'manage_departments.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> <?php echo __('departments'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/audit_trail.php" class="<?php echo $current_page == 'audit_trail.php' ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> <?php echo __('audit_logs'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> <?php echo __('reports'); ?></a>
                <li class="nav-item">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_approval.php') ? 'active' : ''; ?>" 
           href="../admin/admin_approval.php" 
           style="color: white; padding: 12px 20px; display: block; text-decoration: none;">
            <i class="bi bi-shield-check me-2"></i> 
            Password Requests 
            <?php
                $stmt_count = $pdo->query("SELECT COUNT(*) FROM users WHERE reset_token IS NOT NULL AND reset_approved = 0");
                $pending_count = $stmt_count->fetchColumn();
                if ($pending_count > 0): 
            ?>
                <span class="badge rounded-pill bg-danger float-end"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
    </li>
            <?php elseif ($user_role === 'Department Manager'): ?>
    <a href="<?php echo $base_url; ?>/manager/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?>
    </a>
    <a href="<?php echo $base_url; ?>/manager/create_task.php" class="<?php echo $current_page == 'create_task.php' ? 'active' : ''; ?>">
        <i class="bi bi-plus-square"></i> Create Task
    </a>
    <a href="<?php echo $base_url; ?>/manager/maintenance_list.php" class="<?php echo $current_page == 'maintenance_list.php' ? 'active' : ''; ?>">
        <i class="bi bi-shield-lock"></i> Maintenance
    </a>
    <a href="<?php echo $base_url; ?>/manager/view_requests.php" class="<?php echo $current_page == 'view_requests.php' ? 'active' : ''; ?>">
        <i class="bi bi-shield-lock"></i> View Requests
    </a>
    <a href="<?php echo $base_url; ?>/manager/productivity_analytics.php" class="<?php echo $current_page == 'productivity_analytics.php' ? 'active' : ''; ?>">
        <i class="bi bi-graph-up-arrow"></i> Productivity
    </a>
    <a href="<?php echo $base_url; ?>/manager/generate_report.php" class="<?php echo $current_page == 'generate_report.php' ? 'active' : ''; ?>">
        <i class="bi bi-file-earmark-pdf"></i> <?php echo __('reports'); ?>
    </a>
    
            <?php elseif ($user_role === 'Deputy General Manager'): ?>
                <a href="<?php echo $base_url; ?>/deputy_gm/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/manage_departments.php" class="<?php echo $current_page == 'manage_departments.php' ? 'active' : ''; ?>"><i class="bi bi-building"></i> <?php echo __('departments'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/audit_trail.php" class="<?php echo $current_page == 'audit_trail.php' ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> <?php echo __('audit_logs'); ?></a>
                <a href="<?php echo $base_url; ?>/admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> <?php echo __('reports'); ?></a>
            
            
            <?php elseif ($user_role === 'Shift Leader'): ?>
                <a href="<?php echo $base_url; ?>/shift_leader/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
            
            <?php elseif ($user_role === 'Supervisor'): ?>
                <a href="<?php echo $base_url; ?>/supervisor/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?>
                </a>
                <a href="<?php echo $base_url; ?>/manager/create_task.php" class="<?php echo $current_page == 'create_task.php' ? 'active' : ''; ?>">
                    <i class="bi bi-plus-circle-dotted"></i> Create New Task
                </a>
                <a href="<?php echo $base_url; ?>/supervisor/alert_shift_leader.php" class="<?php echo $current_page == 'alert_shift_leader.php' ? 'active' : ''; ?> text-warning">
                    <i class="bi bi-megaphone"></i> Alert Shift Leader
                </a>
                <a href="<?php echo $base_url; ?>/supervisor/dashboard.php#tasks" class="nav-link">
                    <i class="bi bi-list-check"></i> Monitor Tasks
                </a>
                <a href="#" onclick="checkStatus()" class="nav-link">
                    <i class="bi bi-arrow-repeat"></i> Sync Updates
                </a>

            <?php elseif ($user_role === 'Technician'): ?>
                <a href="<?php echo $base_url; ?>/technician/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
            
            <?php else: ?>
                <a href="<?php echo $base_url; ?>/employee/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> <?php echo __('dashboard'); ?></a>
            <?php endif; ?>
                
            
            <hr class="border-secondary mx-3">
            <a href="<?php echo $base_url; ?>/auth/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"><i class="bi bi-person-circle"></i> My Profile</a>
            <a href="<?php echo $base_url; ?>/auth/logout.php" class="text-warning"><i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?></a>
        </div>
    
        <div class="col main-content">
