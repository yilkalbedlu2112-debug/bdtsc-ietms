<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/User.php'; // ክላሱን መጥራት
include '../includes/header_glass.php';
/** @var PDO $pdo */
// 1. አስተዳዳሪ መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
$message = ''; 
$error = '';
// OOP Instance መፍጠር
$userObj = new User($pdo);

// Edit የሚደረገውን ተጠቃሚ መረጃ ማምጣት
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $edit_user = $userObj->getUserById((int)$_GET['edit_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Add User
    if (isset($_POST['add_user'])) {
        $res = $userObj->createUser($_POST['full_name'], $_POST['email'], $_POST['password'], $_POST['user_role'], $_POST['dept_id']);
        if ($res) {
            Database::log_action($pdo, $_SESSION['user_id'], 'User Registration', "Registered: {$_POST['full_name']}");
            $message = 'User registered successfully.';
        }
    }

    // 2. Update User
    if (isset($_POST['update_user'])) {
        $res = $userObj->updateUser($_POST['user_id'], $_POST['full_name'], $_POST['email'], $_POST['user_role'], $_POST['dept_id'], $_POST['status']);
        if ($res) {
            Database::log_action($pdo, $_SESSION['user_id'], 'User Update', "Updated ID: {$_POST['user_id']}");
            $message = 'User updated successfully.';
            $edit_user = null;
        }
    }

    // 3. Toggle Status
    if (isset($_POST['toggle_status'])) {
        $new_status = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';
        if ((int)$_POST['user_id'] === $_SESSION['user_id']) {
            $error = 'የራስዎን አካውንት መዝጋት አይችሉም።';
        } else {
            $userObj->toggleStatus($_POST['user_id'], $new_status);
            Database::log_action($pdo, $_SESSION['user_id'], 'Status Change', "ID {$_POST['user_id']} to $new_status");
            $message = "Status changed to $new_status.";
        }
    }

    // 4. Password Reset
    if (isset($_POST['reset_password'])) {
        $userObj->resetPassword($_POST['user_id'], '123456');
        Database::log_action($pdo, $_SESSION['user_id'], 'Password Reset', "ID: {$_POST['user_id']}");
        $message = 'Password reset to 123456.';
    }
}

// መረጃዎችን ለሰንጠረዡ ማዘጋጀት
$departments = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();
$users = $userObj->getAllUsersWithDept();


// Define roles
$management_roles = ['General Manager', 'Deputy General Manager', 'Engineering Manager', 'Department Manager'];
$production_roles = ['Shift Leader', 'Supervisor'];
$technical_roles = ['Technician', 'Electrician', 'Lab Analyst'];
$finance_roles = ['Accountant', 'Purchaser', 'Store Keeper'];
$admin_roles = ['Officer', 'Auditor', 'Secretary', 'Clerk'];
$general_roles = ['Employee'];

// Define comprehensive roles synchronized with dashboard groups
$all_roles = [
    // Management
    'General Manager',
    'Deputy General Manager',
    'Engineering Manager',
    'Department Manager',
    // Production
    'Shift Leader',
    'Supervisor',
    // Technical/Maintenance
    'Technician',
    'Electrician',
    'Lab Analyst',
    // Finance/Resource
    'Accountant',
    'Purchaser',
    'Store Keeper',
    // Admin/Strategy
    'Officer',
    'Auditor',
    'Secretary',
    'Clerk',
    // General
    'Employee'
];

// Role hierarchy for ordering
$role_hierarchy = [
    'General Manager' => 1,
    'Deputy General Manager' => 2,
    'Engineering Manager' => 3,
    'Department Manager' => 4,
    'Shift Leader' => 5,
    'Supervisor' => 6,
    'Technician' => 7,
    'Electrician' => 8,
    'Lab Analyst' => 9,
    'Accountant' => 10,
    'Purchaser' => 11,
    'Store Keeper' => 12,
    'Officer' => 13,
    'Auditor' => 14,
    'Secretary' => 15,
    'Clerk' => 16,
    'Employee' => 17
];

// Role category for badge colors
$role_categories = [
    'General Manager' => 'management',
    'Deputy General Manager' => 'management',
    'Engineering Manager' => 'management',
    'Department Manager' => 'management',
    'Shift Leader' => 'production',
    'Supervisor' => 'production',
    'Technician' => 'technical',
    'Electrician' => 'technical',
    'Lab Analyst' => 'technical',
    'Accountant' => 'finance',
    'Purchaser' => 'finance',
    'Store Keeper' => 'finance',
    'Officer' => 'admin',
    'Auditor' => 'admin',
    'Secretary' => 'admin',
    'Clerk' => 'admin',
    'Employee' => 'general'
];

$users = $pdo->query("SELECT u.*, d.dept_name 
                      FROM users u 
                      LEFT JOIN departments d ON u.dept_id = d.id 
                      ORDER BY d.dept_name ASC, 
                      CASE 
                        WHEN u.user_role = 'General Manager' THEN 1
                        WHEN u.user_role = 'Deputy General Manager' THEN 2
                        WHEN u.user_role = 'Engineering Manager' THEN 3
                        WHEN u.user_role = 'Department Manager' THEN 4
                        WHEN u.user_role = 'Shift Leader' THEN 5
                        WHEN u.user_role = 'Supervisor' THEN 6
                        WHEN u.user_role = 'Technician' THEN 7
                        WHEN u.user_role = 'Electrician' THEN 8
                        WHEN u.user_role = 'Lab Analyst' THEN 9
                        WHEN u.user_role = 'Accountant' THEN 10
                        WHEN u.user_role = 'Purchaser' THEN 11
                        WHEN u.user_role = 'Store Keeper' THEN 12
                        WHEN u.user_role = 'Officer' THEN 13
                        WHEN u.user_role = 'Auditor' THEN 14
                        WHEN u.user_role = 'Secretary' THEN 15
                        WHEN u.user_role = 'Clerk' THEN 16
                        WHEN u.user_role = 'Employee' THEN 17
                        ELSE 18 
                      END ASC")->fetchAll();
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark"><i class="bi bi-people-fill"></i> Manage Users</h2>
            <p class="text-muted">Create, edit, and manage staff account statuses.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-house"></i> Dashboard</a>
    </div>

    <?php if ($message): ?> <div class="alert alert-success"><?php echo $message; ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="row gy-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-4"><?php echo $edit_user ? 'Update User' : 'Add New User'; ?></h5>
                    <form method="POST">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['full_name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>">
                        </div>
                        
                        <?php if (!$edit_user): ?>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo $edit_user['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $edit_user['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="user_role" class="form-select" required>
                                <?php foreach ($all_roles as $role): ?>
                                    <option value="<?php echo $role; ?>" <?php echo ($edit_user && $edit_user['user_role'] == $role) ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="dept_id" class="form-select">
                                <option value="">No department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($edit_user && $edit_user['dept_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="<?php echo $edit_user ? 'update_user' : 'add_user'; ?>" class="btn btn-<?php echo $edit_user ? 'warning' : 'primary'; ?> w-100">
                            <?php echo $edit_user ? 'Update User Information' : 'Create User'; ?>
                        </button>
                        <?php if ($edit_user): ?>
                            <a href="manage_users.php" class="btn btn-outline-secondary w-100 mt-2">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0">Registered Users</h6>
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
    <h6 class="mb-0">Registered Users</h6>
    
    <div class="dropdown">
        <button class="btn btn-outline-light btn-sm rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> Export List
        </button>
        <ul class="dropdown-menu shadow">
            <li><a class="dropdown-item" href="export_excel.php?type=users"><i class="bi bi-file-earmark-excel text-success"></i> Excel (XLS)</a></li>
            <li><a class="dropdown-item" href="generate_pdf.php?type=users" target="_blank"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF (Report)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="window.print();"><i class="bi bi-printer"></i> Quick Print</a></li>
        </ul>
    </div>
</div>
</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Name</th>
                                <th>Role & Dept</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_dept = ""; 
                            foreach ($users as $u): 
                                $dept_name = $u['dept_name'] ?? 'System Admins / Others';
                                if ($current_dept !== $dept_name): 
                                    $current_dept = $dept_name;
                            ?>
                                <tr class="table-light"><td colspan="4" class="fw-bold text-primary"><?php echo htmlspecialchars($dept_name); ?></td></tr>
                            <?php endif; ?>

                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($u['email']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $category = $role_categories[$u['user_role']] ?? 'general';
                                    $badge_class = match($category) {
                                        'management' => 'bg-danger',
                                        'production' => 'bg-info',
                                        'technical' => 'bg-primary',
                                        'finance' => 'bg-success',
                                        'admin' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> text-white"><?php echo $u['user_role']; ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $u['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="manage_users.php?edit_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $u['status']; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-<?php echo $u['status'] == 'Active' ? 'danger' : 'success'; ?>" title="<?php echo $u['status'] == 'Active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>

                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="reset_password" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reset to 123456?')" title="Reset Pass"><i class="bi bi-key"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>