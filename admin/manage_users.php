<?php
require_once '../includes/db.php';
include '../includes/header_glass.php';

$message = '';
$error = '';

// --- ማስተካከያ 1: Edit የሚደረገውን ተጠቃሚ መረጃ ማምጣት ---
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ተጠቃሚ ለመመዝገብ
    if (isset($_POST['add_user'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $user_role = trim($_POST['user_role']);
        $dept_id = !empty($_POST['dept_id']) ? $_POST['dept_id'] : null;
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'የዚህ ኢሜይል ተጠቃሚ ቀድሞ አለ።';
        } else {
            // status በዲፎልት Active እንዲሆን ተጨምሯል
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, user_role, dept_id, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            if ($stmt->execute([$full_name, $email, $password, $user_role, $dept_id])) {
                log_action($pdo, $_SESSION['user_id'], 'User Registration', "Registered new user: $full_name as $user_role");
                $message = 'User registered successfully.';
            }
        }
    }

    // ተጠቃሚ ለማዘመን (Update)
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $user_role = trim($_POST['user_role']);
        $dept_id = !empty($_POST['dept_id']) ? $_POST['dept_id'] : null;
        $status = $_POST['status']; // Status እንዲቀየር ተጨምሯል

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_role = ?, dept_id = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $user_role, $dept_id, $status, $user_id])) {
            log_action($pdo, $_SESSION['user_id'], 'User Update', "Updated user: $full_name (ID: $user_id)");
            $message = 'User updated successfully.';
            $edit_user = null; // ከ Update በኋላ ፎርሙን ክሊር ለማድረግ
        }
    }

    // --- ማስተካከያ 2: Delete ፈንታ ወደ Inactive መቀየር ---
    if (isset($_POST['toggle_status'])) {
        $user_id = (int)$_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';

        if ($user_id === $_SESSION['user_id']) {
            $error = 'የራስዎን አካውንት መዝጋት አይችሉም።';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $user_id])) {
                log_action($pdo, $_SESSION['user_id'], 'Status Change', "Changed status of ID $user_id to $new_status");
                $message = "User status changed to $new_status successfully.";
            }
        }
    }

    // Password Reset (እንደነበረው)
    if (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $temp_password = '123456'; 
        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$password_hash, $user_id])) {
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            $message = 'Password reset to: ' . htmlspecialchars($temp_password) . ' for ' . htmlspecialchars($user['full_name']);
            log_action($pdo, $_SESSION['user_id'], 'Password Reset', "Password Reset for {$user['full_name']}");
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();

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