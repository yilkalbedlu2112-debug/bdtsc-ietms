<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/ProductionTask.php'; // የ OOP ክላሱ
require_once '../includes/functions.php';

/** @var PDO $pdo */

// 1. የገጽታ ጥበቃ (Access Control)
$allowed_roles = ['Department Manager', 'Engineering Manager', 'Supervisor'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. ተለዋዋጭ መረጃዎችን መሰብሰብ
$dept_id   = $_SESSION['dept_id'];
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$dept_name = trim($_SESSION['dept_name'] ?? 'Department');
$full_name = $_SESSION['full_name']; // ይህንን መስመር ጨምር!

// 3. የክላስ Instance መፍጠር
$taskObj = new ProductionTask($pdo);

// 4. የዲፓርትመንት ምድቦች እና UI Adaptation (ይህ ለ HTML ፎርሙ አስፈላጊ ነው)
$groups = [
    'production' => ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'],
    'technical'  => ['Engineering', 'Quality Assurance'],
    'finance'    => ['Finance Department', 'Procurement / Property'],
    'admin'      => ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection']
];

if (in_array($dept_name, $groups['production'])) {
    $task_label = 'MACHINE / STATION';
    $task_type_options = ['Daily Production', 'Quality Check', 'Maintenance', 'Breakdown'];
    $roles_filter = "'Shift Leader', 'Supervisor', 'Employee'";
} elseif (in_array($dept_name, $groups['technical'])) {
    $task_label = 'ASSET / EQUIPMENT';
    $task_type_options = ['Emergency Repair', 'Preventive Maintenance', 'Lab Analysis', 'Calibration'];
    $roles_filter = "'Technician', 'Electrician', 'Lab Analyst', 'Employee'";
} else {
    $task_label = 'SUBJECT / CASE TITLE';
    $task_type_options = ['Report Preparation', 'Strategic Planning', 'Legal Review', 'Audit Inspection'];
    $roles_filter = "'Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'";
}

// 5. ፎርሙ ሲላክ የሚሰራ የ OOP ሎጂክ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $priority    = $_POST['priority'];
    $task_type   = $_POST['task_type'];
    $due_date    = $_POST['due_date'];
    $target_dept = $_POST['receiver_dept_id'] ?? $dept_id;
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;

    // የሁኔታ (Status) ውሳኔ
    if ($target_dept != $dept_id) {
        $status = 'Pending External';
        $assigned_to = null;
    } else {
        $status = ($user_role === 'Department Manager' || $user_role === 'Engineering Manager') 
                  ? ($assigned_to ? 'Assigned' : 'Approved') 
                  : 'Pending Approval';
    }

    if (empty($title) || empty($description) || empty($due_date)) {
        $error = "እባክዎ ሁሉንም አስፈላጊ መረጃዎች ይሙሉ!";
    } else {
        try {
            $pdo->beginTransaction();

            $task_data = [
                'user_id' => $user_id, 'dept_id' => $dept_id, 'assigned_to' => $assigned_to,
                'target_dept' => $target_dept, 'title' => $title, 'description' => $description,
                'priority' => $priority, 'status' => $status, 'task_type' => $task_type, 'due_date' => $due_date
            ];

            if ($taskObj->createTask($task_data)) {
                $new_id = $pdo->lastInsertId();
                
                // ኦዲት ሎግ (standardized)
                $details = sprintf('task_id=%d; created_by=%d; dept_id=%d; cross_dept=%d; title=%s', $new_id, $user_id, $dept_id, ($target_dept != $dept_id) ? 1 : 0, substr($title,0,200));
                if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
                    Database::log_system_activity($pdo, $user_id, 'TASK_CREATED', $details);
                } else {
                    log_action($pdo, $user_id, 'Task Created', $details);
                }

                // ማሳወቂያ መላክ
                $notif_msg = "New Task Request from $dept_name: $title";
                $taskObj->sendNotification($target_dept, $notif_msg);

                $pdo->commit();
                header("Location: dashboard.php?success=1");
                exit();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "ሲስተም ስህተት፡ " . $e->getMessage();
        }
    }
}

// 6. ለ HTML ፎርሙ የሚያስፈልጉ ዳታዎች
$dept_staff = $pdo->prepare("SELECT id, full_name, user_role FROM users WHERE dept_id = ? AND status = 'Active' AND user_role IN ($roles_filter) ORDER BY full_name");
$dept_staff->execute([$dept_id]);
$all_depts = $pdo->query("SELECT id, dept_name FROM departments ORDER BY dept_name")->fetchAll();

require_once '../includes/header_glass.php';
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-start border-primary border-4">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-plus-circle-dotted text-primary me-2"></i><?php echo $dept_name; ?> | Task Creation
                    </h3>
                    <p class="text-muted mb-0 small">
                        Logged in as: <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($full_name); ?> (<?php echo $user_role; ?>)</span>
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center animate__animated animate__shakeX">
        <i class="bi bi-exclamation-octagon-fill me-2"></i>
        <div><?php echo $error; ?></div>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-lg overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-plus me-2"></i>Register New Work Order / Task</h5>
                </div>
                <div class="card-body p-4 p-md-5 bg-light">
                    <form method="POST" action="">
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-12">
                                <label class="form-label text-uppercase small fw-bold text-primary">
                                    <i class="bi bi-share-fill me-1"></i> Destination Department
                                </label>
                                <select name="receiver_dept_id" class="form-select form-select-lg shadow-sm border-2" id="dept_selector" required>
                                    <option value="<?php echo $dept_id; ?>">Internal - [ <?php echo $dept_name; ?> ]</option>
                                    <optgroup label="Cross-Department Request">
                                        <?php foreach ($all_depts as $d): ?>
                                            <?php if ($d['id'] == $dept_id) continue; ?>
                                            <option value="<?php echo (int)$d['id']; ?>">
                                                External - <?php echo htmlspecialchars($d['dept_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                                <div id="dept_help" class="form-text text-info">ለራስህ ክፍል ከሆነ 'Internal' የሚለውን ምረጥ።</div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Task Category
                                </label>
                                <select name="task_type" id="task_type" class="form-select" required>
                                    <option value="">Select Category </option>
                                    <?php foreach ($task_type_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-speedometer2 text-danger me-1"></i> Priority Level
                                </label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="priority" id="p_normal" value="Normal" checked>
                                    <label class="btn btn-outline-success" for="p_normal">Normal</label>

                                    <input type="radio" class="btn-check" name="priority" id="p_high" value="High">
                                    <label class="btn btn-outline-warning" for="p_high">High</label>

                                    <input type="radio" class="btn-check" name="priority" id="p_emergency" value="Emergency">
                                    <label class="btn btn-outline-danger" for="p_emergency">Emergency</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase small fw-bold text-muted" id="label_title">
                                <i class="bi bi-pencil-square text-dark me-1"></i> <?php echo $task_label; ?>
                            </label>
                            <input type="text" name="title" class="form-control" 
                                   placeholder="እዚህ ጋር ርዕሱን ይጥቀሱ..." required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase small fw-bold text-muted">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i> Task Details & Instructions
                            </label>
                            <textarea name="description" class="form-control" rows="4" 
                                      placeholder="ዝርዝር የስራ መመሪያ እዚህ ጋር ይጥቀሱ..." required></textarea>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6" id="staff_assign_div">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-person-plus-fill text-success me-1"></i> Assign Staff (Internal)
                                </label>
                                <select name="assigned_to" id="assigned_to" class="form-select">
                                    <option value="">Keep Unassigned</option>
                                    <?php foreach ($dept_staff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo htmlspecialchars($staff['full_name']) . " (" . $staff['user_role'] . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-calendar-check text-danger me-1"></i> Target Deadline
                                </label>
                                <input type="datetime-local" name="due_date" id="due_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2 col-md-6 mx-auto">
                            <button type="submit" name="create_task" class="btn btn-primary btn-lg shadow">
                                <i class="bi bi-shield-check me-2"></i>Confirm & Save Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dept_selector').addEventListener('change', function() {
    const taskTypeSelect = document.getElementById('task_type');
    const staffSelect = document.getElementById('assigned_to');
    const staffDiv = document.getElementById('staff_assign_div');
    const labelTitle = document.getElementById('label_title');
    const myDeptId = "<?php echo $dept_id; ?>";
    
    // 1. ሁኔታው 'External' (ለሌላ ዲፓርትመንት) ሲሆን
    if (this.value !== myDeptId) {
        
        // ለሌላ ክፍል ሲላክ የሚመጡ ፕሮፌሽናል የጥያቄ አይነቶች
        taskTypeSelect.innerHTML = `
            <option value="">-- Select Request Type --</option>
            <option value="Maintenance Request">Maintenance (የጥገና ጥያቄ)</option>
            <option value="Technical Support">Technical Support (የቴክኒክ ድጋፍ)</option>
            <option value="Resource Request">Resource Request (የንብረት ጥያቄ)</option>
            <option value="Administrative">Administrative (አስተዳደራዊ)</option>
            <option value="Other">Other (ሌላ)</option>
        `;

        // ሰራተኛ መመደብን መከልከል (ምክንያቱም የሌላ ክፍል ሰራተኛን ማዘዝ አይቻልም)
        staffSelect.disabled = true;
        staffSelect.value = ""; // የተመረጠ ካለ ያጠፋዋል
        staffDiv.style.opacity = '0.5';
        staffDiv.style.pointerEvents = 'none'; // ክሊክ እንዳይደረግ

        // ሌብሉን ወደ 'Request Subject' መቀየር
        labelTitle.innerHTML = '<i class="bi bi-pencil-square text-dark me-1"></i>4. REQUEST SUBJECT / PURPOSE';
    } 
    // 2. ሁኔታው 'Internal' (ወደ ራስ ዲፓርትመንት) ሲመለስ
    else {
        // በ PHP የተዘጋጁትን የየክፍሉን ኦሪጅናል አማራጮች መመለስ
        taskTypeSelect.innerHTML = `
            <option value="">-- Select Category --</option>
            <?php foreach ($task_type_options as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
            <?php endforeach; ?>
        `;

        // ሰራተኛ መመደብን መፍቀድ
        staffSelect.disabled = false;
        staffDiv.style.opacity = '1';
        staffDiv.style.pointerEvents = 'auto';

        // ሌብሉን ወደ ቀድሞው (MACHINE, ASSET, ወዘተ) መመለስ
        labelTitle.innerHTML = '<i class="bi bi-pencil-square text-dark me-1"></i>4. <?php echo $task_label; ?>';
    }
});

// Deadline ያለፈ ቀን እንዳይሆን መቆጣጠር (Validation)
const dueDateInput = document.getElementById('due_date');
if(dueDateInput) {
    const now = new Date();
    // የአሁኑን ሰዓት ወደ ISO ፎርማት መቀየር (YYYY-MM-DDTHH:MM)
    const localNow = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    dueDateInput.min = localNow;
}
</script>

<?php include '../includes/footer_glass.php'; ?>