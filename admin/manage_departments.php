<?php
require_once '../includes/db.php';
include '../includes/header_glass.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_dept']) || isset($_POST['update_dept'])) {
        $dept_name = trim($_POST['dept_name']);
        $description = trim($_POST['description']);

        if (isset($_POST['update_dept'])) {
            $dept_id = (int)$_POST['dept_id'];
            $stmt = $pdo->prepare("UPDATE departments SET dept_name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$dept_name, $description, $dept_id])) {
                $message = 'Department updated successfully.';
                log_action($pdo, $_SESSION['user_id'], 'Department Update', "Updated department: $dept_name (ID: $dept_id)");
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (dept_name, description) VALUES (?, ?)");
            if ($stmt->execute([$dept_name, $description])) {
                $message = 'Department added successfully.';
                log_action($pdo, $_SESSION['user_id'], 'Department Add', "Added department: $dept_name");
            }
        }
    }

    if (isset($_POST['delete_dept'])) {
        $dept_id = (int)$_POST['dept_id'];
        $stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $stmt->execute([$dept_id]);
        $dept = $stmt->fetch();
        if ($dept) {
            $delete = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            if ($delete->execute([$dept_id])) {
                $message = 'Department deleted successfully.';
                log_action($pdo, $_SESSION['user_id'], 'Department Deletion', "Deleted department: {$dept['dept_name']} (ID: $dept_id)");
            }
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();
$edit_department = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_id']]);
    $edit_department = $stmt->fetch();
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark"><i class="bi bi-building"></i> Manage Departments</h2>
            <p class="text-muted">Create, edit, and remove factory departments like Weaving and Garment.</p>
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
                    <h5 class="card-title mb-4"><?php echo $edit_department ? 'Edit Department' : 'Add Department'; ?></h5>
                    <form method="POST">
                        <?php if ($edit_department): ?>
                            <input type="hidden" name="dept_id" value="<?php echo $edit_department['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" name="dept_name" class="form-control" required value="<?php echo $edit_department ? htmlspecialchars($edit_department['dept_name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $edit_department ? htmlspecialchars($edit_department['description']) : ''; ?></textarea>
                        </div>
                        <button type="submit" name="<?php echo $edit_department ? 'update_dept' : 'add_dept'; ?>" class="btn btn-<?php echo $edit_department ? 'warning' : 'success'; ?> w-100"><?php echo $edit_department ? 'Update Department' : 'Add Department'; ?></button>
                        <?php if ($edit_department): ?>
                            <a href="manage_departments.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Departments List</h6>
                    </div>
                    <button class="btn btn-outline-light btn-sm" onclick="window.print();"><i class="bi bi-printer"></i> Print</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($dept['description'] ?? ''); ?></td>
                                <td>
                                    <a href="manage_departments.php?edit_id=<?php echo $dept['id']; ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-pencil-square"></i></a>
                                    <form method="POST" class="d-inline-block" style="margin:0;" onsubmit="return confirm('Delete this department?');">
                                        <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                        <button type="submit" name="delete_dept" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
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