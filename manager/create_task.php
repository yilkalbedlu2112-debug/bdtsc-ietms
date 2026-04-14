<?php
session_start();
require_once '../includes/db.php';

// 1. የደህንነት ማጣሪያ (Access Control) - Managers and Supervisors
$allowed_roles = ['Department Manager', 'Supervisor'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. አስፈላጊ መረጃዎችን ከሴሽን መውሰድ
$dept_id   = $_SESSION['dept_id'];
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
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

$production_group = ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'];
$technical_quality_group = ['Engineering', 'Quality Assurance'];
$finance_resource_group = ['Finance Department', 'Procurement / Property'];
$admin_strategy_group = ['Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection'];
$dept_key = $dept_name;

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
    $task_label = 'TRANSACTION / ITEM';
    $task_type_options = ['Budget Approval', 'Payment Processing', 'Purchase Order', 'Inventory Audit'];
    $roles_filter = "'Accountant', 'Purchaser', 'Store Keeper', 'Officer'";
} else {
    $task_label = 'SUBJECT / CASE TITLE';
    $task_type_options = ['Report Preparation', 'Strategic Planning', 'Legal Review', 'Audit Inspection', 'Staffing/HR'];
    $roles_filter = "'Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'";
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

            $_SESSION['success'] = "ተግባሩ በተሳካ ሁኔታ ተመዝግቧል!";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $error = "ስህተት አጋጥሟል፡ " . $e->getMessage();
        }
    }
}

// 7. ለተመዳቢ ሰራተኞች ዝርዝር (እንደየ ሚናው የሚወጣ)
$users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE dept_id = ? AND status = 'Active' AND role IN ($roles_filter) ORDER BY full_name");
$users_stmt->execute([$dept_id]);
$dept_staff = $users_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-primary"><?php echo $dept_name; ?> | Task Creation</h2>
                    <p class="text-muted">Logged in as: <strong><?php echo $full_name; ?> (<?php echo $user_role; ?>)</strong></p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">
                                <i class="bi bi-tag-fill me-1"></i> TASK TYPE / CATEGORY
                            </label>
                            <select name="task_type" class="form-select bg-light border-0 py-2" required>
                                <option value="">-- Select Task Type --</option>
                                <?php foreach ($task_type_options as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-select" required>
                                    <option value="Normal">Normal</option>
                                    <option value="High">High</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold"><?php echo $task_label; ?></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g., Daily Spinning Report" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Technical Instructions / Description</label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="ዝርዝር የስራ መመሪያ እዚህ ይጥቀሱ..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Assign to Staff (Optional)</label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($dept_staff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo $staff['full_name'] . " (" . $staff['role'] . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline</label>
                                <input type="datetime-local" name="due_date" class="form-control" id="due_date" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Create & Log Task</button>
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