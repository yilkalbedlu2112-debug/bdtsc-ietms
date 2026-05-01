<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php'; // log_action() እዚህ ውስጥ መኖሩን እርግጠኛ ሁን

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
$full_name = $_SESSION['full_name'];
$dept_name = trim($_SESSION['dept_name'] ?? 'Department');

// 3. የዲፓርትመንት ምድቦችን ማዘጋጀት (Exact Match)
$groups = [
    'production' => ['Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department'],
    'technical'  => ['Engineering', 'Quality Assurance'],
    'finance'    => ['Finance Department', 'Procurement / Property'],
    'admin'      => ['General Management', 'Human Resource (HR)', 'Planning', 'Strategy & Innovation', 'System Research & Development', 'Legal Service', 'Audit & Inspection']
];

// 4. እንደ ዲፓርትመንቱ አይነት UI መቀያየር (UI Adaptation)
if (in_array($dept_name, $groups['production'])) {
    $task_label = 'MACHINE / STATION';
    $task_type_options = ['Daily Production', 'Quality Check', 'Maintenance', 'Breakdown'];
    $roles_filter = "'Shift Leader', 'Supervisor', 'Employee'";
} elseif (in_array($dept_name, $groups['technical'])) {
    $task_label = 'ASSET / EQUIPMENT';
    $task_type_options = ['Emergency Repair', 'Preventive Maintenance', 'Lab Analysis', 'Calibration'];
    $roles_filter = "'Technician', 'Electrician', 'Lab Analyst', 'Employee'";
} elseif (in_array($dept_name, $groups['finance'])) {
    $task_label = 'TRANSACTION / ITEM REF';
    $task_type_options = ['Budget Approval', 'Payment Processing', 'Purchase Order', 'Inventory Audit'];
    $roles_filter = "'Accountant', 'Purchaser', 'Store Keeper', 'Officer'";
} else {
    $task_label = 'SUBJECT / CASE TITLE';
    $task_type_options = ['Report Preparation', 'Strategic Planning', 'Legal Review', 'Audit Inspection', 'Staffing/HR'];
    $roles_filter = "'Officer', 'Clerk', 'Secretary', 'Auditor', 'Employee'";
}

// 5. ፎርሙ ሲላክ የሚሰራ ሎጂክ (Form Submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $priority    = $_POST['priority'];
    $task_type   = $_POST['task_type']; // በ JS የተቀየረው እዚህ ጋር ይመጣል
    $due_date    = $_POST['due_date'];
    $target_dept = $_POST['receiver_dept_id'] ?? $dept_id;

    // 1. የሁኔታ (Status) እና የተመዳቢ (Assigned To) ሎጂክ ማስተካከያ
    if ($target_dept != $dept_id) {
        // ስራው ለሌላ ዲፓርትመንት ከሆነ፡
        $assigned_to = null; // የሌላ ክፍል ሰራተኛ መመደብ አይቻልም
        $status      = 'Pending External'; // ሌላኛው ማናጀር እስኪያጸድቀው
    } else {
        // ስራው በራስ ዲፓርትመንት ውስጥ ከሆነ፡
        $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        
        // ማናጀር ከሆነ ወዲያው ይጸድቃል፣ ሱፐርቫይዘር ከሆነ ግን ማናጀር ማየት አለበት
        if ($user_role === 'Department Manager' || $user_role === 'Engineering Manager') {
            $status = ($assigned_to) ? 'Assigned' : 'Approved';
        } else {
            $status = 'Pending Approval';
        }
    }

    // 2. Validation
    if (empty($title) || empty($description) || empty($due_date)) {
        $error = "እባክዎ ሁሉንም አስፈላጊ መረጃዎች ይሙሉ!";
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO maintenance_requests 
                    (user_id, dept_id, assigned_to, receiver_dept_id, machine_name, issue_description, priority, status, task_type, due_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $dept_id, $assigned_to, $target_dept, $title, $description, $priority, $status, $task_type, $due_date]);
            
            $new_task_id = $pdo->lastInsertId();

            // 3. ኦዲት ሎግ መመዝገብ
            $log_msg = ($target_dept != $dept_id) ? "Sent Cross-Dept request #$new_task_id to Dept $target_dept" : "Created internal task #$new_task_id";
            log_action($pdo, $user_id, 'Task Created', $log_msg);

            // 4. ማሳወቂያ (Notification) - ለተቀባዩ ማናጀር መላክ
            $notif_msg = "New " . ($target_dept != $dept_id ? "External " : "") . "Task Request from $dept_name: $title";
            $notif_sql = "INSERT INTO notifications (user_id, dept_id, role_target, message, type, created_at) 
                          SELECT id, ?, 'Department Manager', ?, 'task_assignment', NOW() 
                          FROM users WHERE dept_id = ? AND user_role IN ('Department Manager', 'Engineering Manager') AND status = 'Active'";
            
            $notif_stmt = $pdo->prepare($notif_sql);
            $notif_stmt->execute([$target_dept, $notif_msg, $target_dept]);

            $pdo->commit();
            header("Location: dashboard.php?success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "ሲስተም ስህተት፡ " . $e->getMessage();
        }
    }
}

// 6. ለተመዳቢ ሰራተኞች እና ለዲፓርትመንቶች ዝርዝር ማምጫ
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
                                    <i class="bi bi-share-fill me-1"></i>1. Destination Department
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
                                    <i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i>2. Task Category
                                </label>
                                <select name="task_type" id="task_type" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($task_type_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-speedometer2 text-danger me-1"></i>3. Priority Level
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
                                <i class="bi bi-pencil-square text-dark me-1"></i>4. <?php echo $task_label; ?>
                            </label>
                            <input type="text" name="title" class="form-control" 
                                   placeholder="እዚህ ጋር ርዕሱን ይጥቀሱ..." required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-uppercase small fw-bold text-muted">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>5. Task Details & Instructions
                            </label>
                            <textarea name="description" class="form-control" rows="4" 
                                      placeholder="ዝርዝር የስራ መመሪያ እዚህ ጋር ይጥቀሱ..." required></textarea>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6" id="staff_assign_div">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-person-plus-fill text-success me-1"></i>6. Assign Staff (Internal)
                                </label>
                                <select name="assigned_to" id="assigned_to" class="form-select">
                                    <option value="">-- Keep Unassigned --</option>
                                    <?php foreach ($dept_staff as $staff): ?>
                                        <option value="<?php echo $staff['id']; ?>">
                                            <?php echo htmlspecialchars($staff['full_name']) . " (" . $staff['user_role'] . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-uppercase small fw-bold text-muted">
                                    <i class="bi bi-calendar-check text-danger me-1"></i>7. Target Deadline
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