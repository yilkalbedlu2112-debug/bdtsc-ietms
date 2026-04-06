<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

if (isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

$stmt = $pdo->prepare("SELECT u.*, d.dept_name FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 1. የይለፍ ቃል ማረጋገጫ
    if ($user && password_verify($password, $user['password'])) {
        
                // 2. ሴሽን መፍጠር
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['dept_id'] = $user['dept_id'];
            $_SESSION['dept_name'] = $user['dept_name'] ?? '';

        // 3. ድርጊቱን በ Audit Log መመዝገብ
        log_action($pdo, $user['id'], "Login", "User logged into the system");

        // 4. እንደየ ስልጣኑ (Role) ወደ ተለያየ ፎልደር መምራት (Redirect)
        switch ($user['role']) {
            case 'General Manager':
                header("Location: ../admin/dashboard.php");
                break;
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
                header("Location: ../technician/dashboard.php");
                break;
            case 'Employee':
                header("Location: ../employee/dashboard.php");
                break;
            case 'Production and Technique Deputy General Manager':
                header("Location: ../deputy_gm/dashboard.php");
                break;
            default:
                header("Location: ../auth/login.php?error=Unknown Role");
                break;
        }
        exit(); 
    } else {
        header("Location: login.php?error=Invalid Credentials");
        exit();
    }
}
?>