<?php
require_once '../includes/db.php';
include '../includes/admin_header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $dept_id = !empty($_POST['dept_id']) ? $_POST['dept_id'] : null;
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'የዚህ ኢሜይል የተጠቃሚ ቀድሞ አለ።';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, dept_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $email, $password, $role, $dept_id])) {
                log_action($pdo, $_SESSION['user_id'], 'User Registration', "Registered new user: $full_name as $role");
                $message = 'User registered successfully.';
            }
        }
    }

    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $dept_id = !empty($_POST['dept_id']) ? $_POST['dept_id'] : null;

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, dept_id = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $role, $dept_id, $user_id])) {
            log_action($pdo, $_SESSION['user_id'], 'User Update', "Updated user: $full_name (ID: $user_id)");
            $message = 'User updated successfully.';
        }
    }

    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        if ($user_id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own administrator account.';
        } else {
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if ($user) {
                $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($delete->execute([$user_id])) {
                    log_action($pdo, $_SESSION['user_id'], 'User Deletion', "Deleted user: {$user['full_name']} (ID: $user_id)");
                    $message = 'User deleted successfully.';
                }
            }
        }
    }

    if (isset($_POST['reset_password'])) {
        $user_id = (int)$_POST['user_id'];
        $temp_password = '123456'; // Fixed password as requested
        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$password_hash, $user_id])) {
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            $message = 'Password reset to: ' . htmlspecialchars($temp_password) . ' for user: ' . htmlspecialchars($user['full_name']);
            log_action($pdo, $_SESSION['user_id'], 'Password Reset', "Password Reset for {$user['full_name']} (ID: $user_id)");
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY dept_name ASC")->fetchAll();
$users = $pdo->query("SELECT u.*, d.dept_name FROM users u LEFT JOIN departments d ON u.dept_id = d.id ORDER BY u.id DESC")->fetchAll();

$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $edit_user = $stmt->fetch();
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark"><i class="bi bi-people-fill"></i> Manage Users</h2>
            <p class="text-muted">Create, edit, delete and reset staff accounts.</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-house"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row gy-4">
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-3 border-0">
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
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach (['General Manager','Deputy General Manager','Engineering Manager','Department Manager','Shift Leader','Supervisor','Technician','Employee'] as $role): ?>
                                    <option value="<?php echo $role; ?>" <?php echo $edit_user && $edit_user['role'] === $role ? 'selected' : ''; ?>><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="dept_id" class="form-select">
                                <option value="">No department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $edit_user && $edit_user['dept_id'] == $dept['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="<?php echo $edit_user ? 'update_user' : 'add_user'; ?>" class="btn btn-<?php echo $edit_user ? 'warning' : 'primary'; ?> w-100"><?php echo $edit_user ? 'Update User' : 'Create User'; ?></button>
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
                    <div>
                        <h6 class="mb-0"><i class="bi bi-people-fill me-2"></i>Registered Users</h6>
                        <small class="text-light opacity-75">Manage system users and their access</small>
                    </div>
                    <button class="btn btn-outline-light btn-sm rounded-pill" onclick="window.print();">
                        <i class="bi bi-printer me-1"></i>Print / Export
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="border-0 fw-semibold"><i class="bi bi-person me-1"></i>Name</th>
                                <th class="border-0 fw-semibold"><i class="bi bi-envelope me-1"></i>Email</th>
                                <th class="border-0 fw-semibold"><i class="bi bi-shield me-1"></i>Role</th>
                                <th class="border-0 fw-semibold"><i class="bi bi-building me-1"></i>Department</th>
                                <th class="border-0 fw-semibold text-center"><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr class="border-bottom border-light">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                            <i class="bi bi-person-fill text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo ($u['role'] == 'General Manager') ? 'bg-danger' : 'bg-info text-dark'; ?> px-3 py-2">
                                        <i class="bi bi-star-fill me-1"></i><?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-secondary">
                                        <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($u['dept_name'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="manage_users.php?edit_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Edit User">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form method="POST" class="d-inline-block" style="margin:0;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="reset_password" class="btn btn-sm btn-outline-warning rounded-pill" title="Reset Password to 123456" onclick="return confirm('Are you sure you want to reset the password for <?php echo htmlspecialchars($u['full_name']); ?> to \'123456\'?')">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline-block" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger rounded-pill" title="Delete User">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
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

<?php include '../includes/admin_footer.php'; ?>