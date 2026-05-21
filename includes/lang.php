<?php
// includes/lang.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supported_languages = [
    'en' => [
        'name' => 'English',
        'native' => 'English',
        'locale' => 'en_US.UTF-8'
    ],
    'am' => [
        'name' => 'Amharic',
        'native' => 'አማርኛ',
        'locale' => 'am_ET.UTF-8'
    ],
];

$default_lang = 'en';

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (!array_key_exists($lang, $supported_languages)) {
        $lang = $default_lang;
    }
    $_SESSION['lang'] = $lang;

    $referer = $_SERVER['HTTP_REFERER'] ?? $_SERVER['REQUEST_URI'] ?? '../index.php';
    header("Location: $referer");
    exit();
}

$current_lang = $_SESSION['lang'] ?? $default_lang;
$current_locale = $supported_languages[$current_lang]['locale'] ?? $supported_languages[$default_lang]['locale'];
setlocale(LC_ALL, $current_locale);

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
        'status_under_review' => 'Under Review',
        'assign_task' => 'Assign Task',
        'force_reset_password' => 'Force Reset Password',
        'my_tasks' => 'My Tasks',
        'view_details' => 'View Details',
        'update_status' => 'Update Status',
        'mark_completed' => 'Mark as Completed',
        'resume' => 'Resume',
        'submit_feedback' => 'Submit Feedback',
        'category' => 'Category',
        'description' => 'Description',
        'task_details' => 'Task Details',
        'priority' => 'Priority',
        'status' => 'Status',
        'created' => 'Created',
        'deadline' => 'Deadline',
        'machine_workstation' => 'Machine/Workstation',
        'no_deadline' => 'No deadline',
        'no_tasks' => 'No tasks assigned to you at the moment.',
        'technical' => 'Technical',
        'material' => 'Material',
        'administrative' => 'Administrative',
        'password_requests' => 'Password Requests',
        'authority_delegation' => 'Authority Delegation',
        'create_task' => 'Create Task',
        'maintenance_log' => 'Maintenance Log',
        'cross_dept_requests' => 'Cross-Dept Requests',
        'productivity' => 'Productivity',
        'feedback' => 'Feedback',
        'dispatch_center' => 'Dispatch Center',
        'assign_tasks' => 'Assign Tasks',
        'review_tasks' => 'Review Tasks',
        'submit_report' => 'Submit Report',
        'shift_leader_reports' => 'Shift Leader Reports',
        'back_to_home' => 'Back to Home',
        'delegation_active' => 'Delegation Active:',
        'you_are_acting_on_behalf' => 'You are currently acting on behalf of',
        'reason' => 'Reason:',
        'login_title' => 'BDTSC-IETMS | Secure Login',
        'email' => 'Email / ኢሜይል',
        'password' => 'Password / የይለፍ ቃል',
        'forgot' => 'Forgot? / ረስተዋል?',
        'login_button' => 'Login / ግባ',
        'industrial_task_management_system' => 'Industrial Task Management System'
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
        'status_under_review' => 'በምርመራ ላይ',
        'assign_task' => 'ስራ ስጥ',
        'force_reset_password' => 'የይለፍ ቃል አስተካክል',
        'my_tasks' => 'የተመደቡልኝ ስራዎች',
        'view_details' => 'ዝርዝሮችን ይመልከቱ',
        'update_status' => 'ሁኔታውን ቀይር',
        'mark_completed' => 'እንደ ተጠናቀ ምልክት ያድርጉ',
        'resume' => 'እንደገና ጀምር',
        'submit_feedback' => 'አስተያየት ያስገቡ',
        'category' => 'አይነት',
        'description' => 'መግለጫ',
        'task_details' => 'የስራ ዝርዝሮች',
        'priority' => 'ቅድሚያ',
        'status' => 'ሁኔታ',
        'created' => 'የተፈጠረበት ቀን',
        'deadline' => 'የማብቂያ ቀን',
        'machine_workstation' => 'ሜሽን/ስራ ቦታ',
        'no_deadline' => 'የማብቂያ ቀን የለም',
        'no_tasks' => 'ለዛሬ የተመደበ ስራ የለም።',
        'technical' => 'የቴክኒክ',
        'material' => 'የጥሬ እቃ',
        'administrative' => 'አስተዳደራዊ',
        'password_requests' => 'የይለፍ ቃል ጥያቄዎች',
        'authority_delegation' => 'የሥራ ሃላፊነት ውድቀት',
        'create_task' => 'ስራ ፍጠር',
        'maintenance_log' => 'የጥገና ማህደር',
        'cross_dept_requests' => 'የክፍል ስር ላይ ጥያቄዎች',
        'productivity' => 'የውጤት ተንቀሳቃሽ',
        'feedback' => 'ማስተያየት',
        'dispatch_center' => 'የመላኪያ ማዕከል',
        'assign_tasks' => 'ስራዎችን አስከፍል',
        'review_tasks' => 'ስራዎችን አርቴይ',
        'submit_report' => 'ሪፖርት አቅርብ',
        'shift_leader_reports' => 'የሸፍት መሪ ሪፖርቶች',
        'back_to_home' => 'ወደ ዋናው ገጽ',
        'delegation_active' => 'ውድቀት ንቁ ነው:',
        'you_are_acting_on_behalf' => 'እርስዎ በተኩል ላይ እየሰሩ ነው',
        'reason' => 'ምክንያት:',
        'login_title' => 'BDTSC-IETMS | የሚሠራ ግባ',
        'email' => 'ኢሜይል / Email',
        'password' => 'የይለፍ ቃል / Password',
        'forgot' => 'ረስተዋል? / Forgot?',
        'login_button' => 'ግባ / Login',
        'industrial_task_management_system' => 'የኢንዱስትሪ የስራ አስተዳደር ስርዓት'
    ]
];

function __($key, array $replacements = []): string {
    global $translations, $current_lang;

    $text = $translations[$current_lang][$key] ?? $translations['en'][$key] ?? $key;

    foreach ($replacements as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }

    return $text;
}

function lang_url(string $target_lang): string {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $parts = parse_url($request_uri);
    $path = $parts['path'] ?? '';
    parse_str($parts['query'] ?? '', $query);
    $query['lang'] = $target_lang;
    return $path . '?' . http_build_query($query);
}

function supported_language_list(): array {
    global $supported_languages;
    return $supported_languages;
}

function current_language_native(): string {
    global $supported_languages, $current_lang;
    return $supported_languages[$current_lang]['native'] ?? $supported_languages['en']['native'];
}
?>
