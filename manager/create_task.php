<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$dept_name = $_SESSION['dept_name'] ?? 'Department';
$full_name = $_SESSION['full_name'] ?? 'Manager';
$user_id = $_SESSION['user_id'] ?? 0;

// ለሁሉም ዲፓርትመንቶች የሚሆኑ የተግባር አይነቶች (Dynamic Task Types)
$dynamic_task_types = [
    'Production', 'Maintenance', 'Administrative', 'Quality', 'Financial', 
    'ICT Support', 'Human Resources', 'Logistics', 'Security', 'General'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_type = $_POST['task_type'] ?? 'General';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if (empty($title) || empty($description)) {
        $error = "Title and description are required.";
    } else {
        // ሰራተኛ ከተመረጠ Status 'Assigned' ይሆናል ካልሆነ ግን 'Pending Approval'
        $status = $assigned_to ? 'Assigned' : 'Pending Approval';
        try {
            // ማስታወሻ፡ machine_name የሚለው በዳታቤዝህ 'Task Title' እንዲሆን ታስቦ ነው የተቀመጠው
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, dept_id, assigned_to, machine_name, issue_description, priority, status, task_type, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $dept_id, $assigned_to, $title, $description, $priority, $status, $task_type, $due_date]);

            $_SESSION['success'] = "Task created successfully!";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $error = "Error creating task: " . $e->getMessage();
        }
    }
}

// ማጣሪያ፡ የማናጀሩ ዲፓርትመንት አይነት ላይ በመመስረት የተለያዩ ሰራተኞችን ያመጣል
$dept_type_stmt = $pdo->prepare("SELECT dept_type FROM departments WHERE id = ?");
$dept_type_stmt->execute([$dept_id]);
$dept_type = $dept_type_stmt->fetchColumn() ?: 'Support';

if ($dept_type === 'Production') {
    $roles = "'Shift Leader', 'Supervisor'";
} else {
    $roles = "'Employee', 'Technician'";
}
$users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE dept_id = ? AND status = 'Active' AND role IN ($roles) ORDER BY full_name");
$users_stmt->execute([$dept_id]);
$users = $users_stmt->fetchAll();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1">BDTSC - <?php echo htmlspecialchars($dept_name); ?> Task Management</h1>
                    <h5 class="text-muted">Manager: <?php echo htmlspecialchars($full_name); ?></h5>
                </div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card glass-card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Departmental Task</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="taskForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="task_type" class="form-label">Task Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="task_type" name="task_type" required>
                                    <?php foreach ($dynamic_task_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($type == 'General') ? 'selected' : ''; ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Task Title / Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="100" placeholder="Enter task title">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Task Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                placeholder="Describe the task details here..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="assigned_to" class="form-label">Assign To (Staff in <?php echo htmlspecialchars($dept_name); ?>)</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">Leave Unassigned (Pending)</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['role'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Deadline (Due Date & Time)</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-circle"></i> Create Task
                            </button>
                            <button type="reset" class="btn btn-secondary px-4">
                                <i class="bi bi-x-circle"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
</div>

<script>
// 14ቱም ዲፓርትመንት ሲጠቀሙበት እንደ ምርጫቸው ጽሁፉ እንዲቀየር
document.getElementById('task_type').addEventListener('change', function() {
    const type = this.value;
    const desc = document.getElementById('description');
    desc.placeholder = "Enter details for " + type + " task...";
});

// የጊዜ ገደቡ ካሁኑ ሰዓት በፊት እንዳይሆን መቆጣጠሪያ
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('due_date').min = now.toISOString().slice(0, 16);
});
</script>

<?php include '../includes/footer_glass.php'; ?>