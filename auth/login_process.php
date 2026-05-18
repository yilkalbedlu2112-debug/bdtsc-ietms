<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
// log_action ፋንክሽን ያለበትን ፋይል መጥራት እንዳትረሳ (ለምሳሌ functions.php)

/** @var PDO $pdo */
if (isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.*, d.dept_name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['user_role']; // እዚህ ጋር 'Officer' ወዘተ ይቀመጣል
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['dept_id'] = $user['dept_id'];
        $_SESSION['dept_name'] = $user['dept_name'] ?? 'General';
$_SESSION['profile_pic'] = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_user.jpg';
        // ድርጊቱን መመዝገብ
        if (function_exists('log_action')) {
            log_action($pdo, $user['id'], "Login", "User logged into the system");
        }

        // ---------------------------------------------------------
        // እንደየ ሮላቸው ወደ ትክክለኛው ፎልደር መምራት (Redirect)
        // ---------------------------------------------------------
        $role = $user['user_role'];

        switch ($role) {
            case 'General Manager':
                header("Location: ../admin/dashboard.php");
                break;
            
            case 'Deputy General Manager':
                header("Location: ../deputy_gm/dashboard.php");
                break;

            case 'Engineering Manager':
            case 'Department Manager':
                header("Location: ../manager/dashboard.php");
                break;

            case 'Shift Leader':
                header("Location: ../shift_leader/dashboard.php");
                break;

            case 'Supervisor':
                header("Location: ../supervisor/dashboard.php");
                break;

            case 'Technician':
            case 'Electrician':
            case 'Lab Analyst':
            case 'Employee':
            case 'Officer':      
            case 'Accountant':   
            case 'Purchaser': 
            case 'Store Keeper':
            case 'Clerk':
            case 'Secretary':
            case 'Auditor':
                header("Location: ../employee/dashboard.php");
                break;

            case 'Admin':
                header("Location: ../admin/dashboard.php");
                break;

            default:
                // ሮሉ በ switch ውስጥ ከሌለ እዚህ ጋር ይያዛል
                header("Location: login.php?error=Unknown Role: " . urlencode($role));
                break;
        }
        exit(); 
    } else {
        header("Location: login.php?error=Invalid Credentials");
        exit();
    }
}
?>