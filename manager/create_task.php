<?php
session_start();
require_once '../includes/db.php';

// 1. Access Control – Managers, Engineering Manager, and Supervisors
$allowed_roles = ['Department Manager', 'Engineering Manager', 'Supervisor'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. አስፈላጊ መረጃዎችን ከሴሽን መውሰድ
$dept_id   = $_SESSION['dept_id'];
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$full_name = $_SESSION['full_name'];
$dept_name = trim($_SESSION['dept_name'] ?? 'Department');

$is_production = false; 

$card_1 = "Total Assigned Tasks";
$card_2 = "Pending Approvals";
$card_3 = "Dept. Performance";
$table_h = "Task / Project Name";
$btn_extra_name = "New Task Request";
$btn_extra_icon = "bi-plus-circle";
$btn_extra_class = "btn-outline-primary";
$target_modal = "#createTaskModal";
$task_label = "SUBJECT / CASE TITLE";
$task_type_options = [
    'Report Preparation',
    'Strategic Planning',
    'Legal Review',
    'Audit Inspection',
    'Staffing/HR'
];
$roles_filter = "'Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'";

// Groups use EXACT dept_name values from the `departments` table
$dept_key = $dept_name; // no alias — compare directly

$production_group = [
    'Spinning Department',       // id 8
    'Weaving Department',        // id 9
    'Processing Department',     // id 10
    'Garment Department',        // id 3
];
$technical_quality_group = [
    'Engineering',               // id 16
    'Quality Assurance',         // id 13
];
$finance_resource_group = [
    'Finance Department',        // id 7
    'Procurement / Property',    // id 14 — exact name
];
$admin_strategy_group = [
    'General Management',              // id 1
    'Human Resource (HR)',             // id 12 — exact name
    'Planning',                        // id 5
    'Strategy & Innovation',           // id 4 — exact name
    'System Research & Development',   // id 6
    'Legal Service',                   // id 15
    'Audit & Inspection',              // id 11
];

if (in_array($dept_key, $production_group, true)) {
    $is_production = true;
    $task_label = 'MACHINE / STATION';
    $task_type_options = ['Daily Production', 'Quality Check', 'Maintenance', 'Breakdown'];
    $roles_filter = "'Shift Leader', 'Supervisor', 'Employee'";
} elseif (in_array($dept_key, $technical_quality_group, true)) {
    $is_production = true;
    $task_label = 'ASSET / EQUIPMENT';
    $task_type_options = ['Emergency Repair', 'Preventive Maintenance', 'Lab Analysis', 'Calibration'];
    $roles_filter = "'Technician', 'Electrician', 'Lab Analyst', 'Employee'";
} elseif (in_array($dept_key, $finance_resource_group, true)) {
    $is_production = false;
    $task_label = 'TRANSACTION / ITEM REF';
    $task_type_options = ['Budget Approval', 'Payment Processing', 'Purchase Order', 'Inventory Audit'];
    $roles_filter = "'Accountant', 'Purchaser', 'Store Keeper', 'Officer'";
} elseif (in_array($dept_key, $admin_strategy_group, true)) {
    $is_production = false;
    $task_label = 'SUBJECT / CASE TITLE';
    $task_type_options = ['Report Preparation', 'Strategic Planning', 'Legal Review', 'Audit Inspection', 'Staffing/HR'];
    $roles_filter = "'Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'";
} else {
    // Fallback for any unlisted department
    $is_production = false;
    $task_label = 'SUBJECT / CASE TITLE';
    $task_type_options = ['Administrative Task', 'Report Preparation', 'Other'];
    $roles_filter = "'Officer', 'Clerk', 'Employee'";
}
// 5. ፎርሙ ሲላክ (Form Submission Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority    = $_POST['priority'];
    $task_type   = $_POST['task_type'];
    $due_date    = $_POST['due_date'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;

    // Server-side Validation
    if (empty($title) || empty($description) || empty($due_date)) {
        $error = "እባክዎ ሁሉንም አስፈላጊ ቦታዎች ይሙሉ!";
    } elseif ($due_date < date('Y-m-d\TH:i')) {
        $error = "የመጨረሻ ቀን (Deadline) ካለፈ ሰዓት መሆን አይችልም!";
    } else {
        try {
            // Logic: ሱፐርቫይዘር ከፈጠረ ማኔጀር ማጽደቅ አለበት
            if ($user_role === 'Department Manager') {
                $status = $assigned_to ? 'Assigned' : 'Approved';
            } else {
                $status = 'Pending Approval';
            }

            $sql = "INSERT INTO maintenance_requests 
                    (user_id, dept_id, assigned_to, machine_name, issue_description, priority, status, task_type, due_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $dept_id, $assigned_to, $title, $description, $priority, $status, $task_type, $due_date]);
            
            $new_task_id = $pdo->lastInsertId();

            // 6. ኦዲት ሎግ መመዝገብ (UC-16)
            $log_details = "$user_role ($full_name) created task #$new_task_id for $dept_name.";
            log_action($pdo, $user_id, 'Task Created', $log_details);

            // Insert notification for recipient department manager if cross-department task
            $receiver_dept_id = $_POST['receiver_dept_id'] ?? $dept_id;
            if ($receiver_dept_id != $dept_id) {
                $notif_msg = "$full_name created a cross-department task #$new_task_id for your department.";
                $notif_sql = "INSERT INTO notifications (user_id, dept_id, role_target, message, type, created_at) SELECT u.id, ?, 'Department Manager', ?, 'task_assignment', NOW() FROM users u WHERE u.dept_id = ? AND u.user_role = 'Department Manager'";
                $notif_stmt = $pdo->prepare($notif_sql);
                $notif_stmt->execute([$receiver_dept_id, $notif_msg, $receiver_dept_id]);

                // Also notify Shift Leaders in the receiving department
                $notif_msg_sl = "$full_name created a cross-department task #$new_task_id for your department.";
                $notif_sql_sl = "INSERT INTO notifications (user_id, dept_id, role_target, message, type, created_at) SELECT u.id, ?, 'Shift Leader', ?, 'task_assignment', NOW() FROM users u WHERE u.dept_id = ? AND u.user_role = 'Shift Leader'";
                $notif_stmt_sl = $pdo->prepare($notif_sql_sl);
                $notif_stmt_sl->execute([$receiver_dept_id, $notif_msg_sl, $receiver_dept_id]);
            }
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
                $error = "ስህተት አጋጥሟል፡ " . $e->getMessage();
            }
        }
    }

// 7. ለተመዳቢ ሰራተኞች ዝርዝር (እንደየ ሚናው የሚወጣ)
$users_stmt = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND status = 'Active' AND user_role IN ($roles_filter) ORDER BY full_name");
$users_stmt->execute([$dept_id]);
$dept_staff = $users_stmt->fetchAll();

// 8. All departments for the Target Dept dropdown
$all_depts_stmt = $pdo->prepare("SELECT id, dept_name FROM departments ORDER BY dept_name");
$all_depts_stmt->execute();
$all_depts = $all_depts_stmt->fetchAll();


require_once '../includes/header_glass.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-2">
                        <i class="bi bi-plus-circle text-primary me-2"></i><?php echo $dept_name; ?> | Task Creation
                    </h2>
                    <p class="text-muted mb-0">
                        Logged in as: <strong><?php echo htmlspecialchars($full_name); ?> (<?php echo $user_role; ?>)</strong>
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success border-0 shadow-sm">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger border-0 shadow-sm">
                <?php echo $error; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Task Creation Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-2">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-clipboard-plus text-primary me-2"></i>Create New Task
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="save_task.php">

                        <!-- Task Type and Priority -->
                        <div class="row g-3 mb-4">
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-tag-fill text-primary me-1"></i>TASK TYPE / CATEGORY
                                </label>
                                <select name="task_type" class="form-select" required>
                                    <option value="">Select Task Type</option>
                                    <?php foreach ($task_type_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>PRIORITY LEVEL
                                </label>
                                <select name="priority" class="form-select" required>
                                    <option value="Normal">Normal</option>
                                    <option value="High">High</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                            </div>
                        </div>

                        <!-- Request Type and Target Department -->
                        <div class="row g-3 mb-4">
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-arrow-left-right text-info me-1"></i>REQUEST TYPE
                                </label>
                                <select name="request_type" class="form-select" required>
                                    <option value="Administrative">Administrative</option>
                                    <option value="Repair">Repair (Engineering)</option>
                                    <option value="Manpower">Manpower (HR)</option>
                                    <option value="Resource">Resource / Spare Parts (Procurement)</option>
                                    <option value="Legal">Legal / Contract</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-building text-secondary me-1"></i>TARGET DEPARTMENT
                                </label>
                                <select name="receiver_dept_id" class="form-select" required>
                                    <option value="<?php echo $dept_id; ?>">-- Own Department (Internal) --</option>
                                    <?php foreach ($all_depts as $d): ?>
                                        <?php if ($d['id'] == $dept_id) continue; ?>
                                        <option value="<?php echo (int)$d['id']; ?>">
                                            <?php echo htmlspecialchars($d['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Subject/Title -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">
                                <?php echo $task_label; ?>
                            </label>
                            <input type="text" name="title" class="form-control" placeholder="e.g., Daily Spinning Report" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-file-text text-success me-1"></i>TECHNICAL INSTRUCTIONS / DESCRIPTION
                            </label>
                            <textarea name="description" class="form-control" rows="4"
                                      required placeholder="ዝርዝር የስራ መመሪያ እዚህ ይጥቀሱ..."></textarea>
                        </div>

                        <!-- Assign To and Deadline -->
                        <div class="row g-3 mb-4">
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-person-fill text-info me-1"></i>ASSIGN TO STAFF (OPTIONAL)
                                </label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($dept_staff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo htmlspecialchars($staff['full_name'])
                                                  . ' (' . $staff['user_role'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label text-muted small fw-bold">
                                    <i class="bi bi-calendar-event text-danger me-1"></i>DEADLINE
                                </label>
                                <input type="datetime-local" name="due_date" class="form-control" id="due_date" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-check2-circle me-2"></i>Create & Log Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // የመጨረሻ ቀን ከአሁኑ ሰዓት በፊት እንዳይሆን መቆለፊያ
    const now = new Date().toISOString().slice(0, 16);
    document.getElementById('due_date').min = now;
</script>

<?php include '../includes/footer_glass.php'; ?>