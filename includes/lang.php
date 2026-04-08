<?php
// includes/lang.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Toggle language
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = in_array($_GET['lang'], ['en', 'am']) ? $_GET['lang'] : 'en';
    
    // Redirect back to the referrer or a default page to avoid keeping ?lang in URL
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header("Location: $referer");
    exit();
}

$current_lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'dashboard' => 'Dashboard',
        'tasks' => 'Tasks',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'welcome' => 'Welcome',
        'my_profile' => 'My Profile',
        'departments' => 'Departments',
        'users' => 'Users',
        'audit_logs' => 'Audit Logs',
        'maintenance' => 'Maintenance',
        'total_employees' => 'Total Employees',
        'active_tasks' => 'Active Tasks',
        'completed_tasks' => 'Completed Tasks',
        'language' => 'Language',
        'amharic' => 'Amharic (አማርኛ)',
        'english' => 'English',
        'status_in_progress' => 'In Progress',
        'status_blocked' => 'Blocked',
        'status_completed' => 'Completed',
        'assign_task' => 'Assign Task',
        'force_reset_password' => 'Force Reset Password'
    ],
    'am' => [
        'dashboard' => 'ዳሽቦርድ',
        'tasks' => 'ስራዎች',
        'reports' => 'ሪፖርቶች',
        'settings' => 'ማስተካከያዎች',
        'logout' => 'ውጣ',
        'welcome' => 'እንኳን በደህና መጡ',
        'my_profile' => 'የግል ማህደር',
        'departments' => 'ክፍሎች',
        'users' => 'ተጠቃሚዎች',
        'audit_logs' => 'የስርዓት ክትትል',
        'maintenance' => 'ጥገና',
        'total_employees' => 'ጠቅላላ ሰራተኞች',
        'active_tasks' => 'በሂደት ላይ ያሉ ስራዎች',
        'completed_tasks' => 'የተጠናቀቁ ስራዎች',
        'language' => 'ቋንቋ',
        'amharic' => 'አማርኛ',
        'english' => 'እንግሊዝኛ (English)',
        'status_in_progress' => 'በሂደት ላይ',
        'status_blocked' => 'ታግዷል',
        'status_completed' => 'ተጠናቋል',
        'assign_task' => 'ስራ ስጥ',
        'force_reset_password' => 'የይለፍ ቃል አስተካክል'
    ]
];

function __($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $key;
}
?>
