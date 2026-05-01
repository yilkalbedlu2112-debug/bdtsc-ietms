<?php
// manager/view_requests.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php'; // log_action() እዚህ መኖሩን እርግጠኛ ሁን

// 1. Auth guard
if (!isset($_SESSION['user_role']) || 
    !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id   = (int)($_SESSION['dept_id']   ?? 0);
$user_id   = (int)($_SESSION['user_id']   ?? 0);
$user_role = $_SESSION['user_role'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'Manager';

// 2. Fetch this department's name
$stmt_dept = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$stmt_dept->execute([$dept_id]);
$current_dept = $stmt_dept->fetch();
$dept_name = $current_dept['dept_name'] ?? 'Department';

// 3. Handle status update and Assignment
if (isset($_POST['update_status'])) {
    $req_id     = (int)$_POST['req_id'];
    $new_status = trim($_POST['status_value']);
    $assign_to  = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
    
    // 'Pending External' የነበረውን ወደ 'Pending' ወይም 'In Progress' መቀየር ይቻላል
    $allowed_statuses = ['Pending External', 'Pending', 'In Progress', 'Completed', 'Rejected'];

    if (in_array($new_status, $allowed_statuses, true)) {
        // የጥያቄው ተቀባይ እኛው መሆናችንን አረጋግጠን እናሻሽላለን
        $upd = $pdo->prepare(
            "UPDATE maintenance_requests 
             SET status = ?, assigned_to = ?, is_read_by_receiver = 1 
             WHERE id = ? AND receiver_dept_id = ?"
        );
        $upd->execute([$new_status, $assign_to, $req_id, $dept_id]);

        log_action($pdo, $user_id, 'Request Updated', 
            "$user_role ($full_name) updated request #$req_id to '$new_status' and assigned to user ID: $assign_to");
        
        $success_msg = "ጥያቄው በትክክል ተስተካክሏል!";
    }
}

// 4. Mark incoming requests as read
$pdo->prepare("UPDATE maintenance_requests SET is_read_by_receiver = 1 WHERE receiver_dept_id = ? AND is_read_by_receiver = 0")
    ->execute([$dept_id]);

// 5. Fetch filter values
$filter_status = $_GET['status']    ?? '';
$filter_type   = $_GET['task_type'] ?? ''; // ካለፈው ኮድ ጋር እንዲመሳሰል task_type ተደርጓል
$filter_dir    = $_GET['direction'] ?? 'incoming'; 

// 6. Build query
$conditions = [];
$params     = [];

if ($filter_dir === 'outgoing') {
    $conditions[] = "mr.dept_id = ?"; // ላኪው እኔ የሆንኩባቸው
    $params[]     = $dept_id;
} else {
    $conditions[] = "mr.receiver_dept_id = ?"; // ተቀባዩ እኔ የሆንኩባቸው
    $params[]     = $dept_id;
}

if ($filter_status !== '') {
    $conditions[] = "mr.status = ?";
    $params[]     = $filter_status;
}
if ($filter_type !== '') {
    $conditions[] = "mr.task_type = ?";
    $params[]     = $filter_type;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT mr.*, 
               usr.full_name      AS requester_name,
               sd.dept_name       AS sender_dept_name,
               rd.dept_name       AS receiver_dept_name,
               ast.full_name      AS assigned_staff_name
        FROM   maintenance_requests mr
        LEFT JOIN users usr ON mr.user_id = usr.id
        LEFT JOIN users ast ON mr.assigned_to = ast.id
        LEFT JOIN departments sd ON mr.dept_id = sd.id
        LEFT JOIN departments rd ON mr.receiver_dept_id = rd.id
        $where
        ORDER BY mr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// 7. Summary counts
$cnt_stmt = $pdo->prepare(
    "SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status='Pending External' THEN 1 ELSE 0 END) AS pending_ext,
        SUM(CASE WHEN status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed
     FROM maintenance_requests WHERE receiver_dept_id = ?");
$cnt_stmt->execute([$dept_id]);
$counts = $cnt_stmt->fetch();

// 8. ለሰራተኛ መመደቢያ የሚሆኑ የዚህ ዲፓርትመንት ሰራተኞች
$staff_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE dept_id = ? AND status = 'Active' AND user_role NOT IN ('Department Manager', 'Engineering Manager')");
$staff_stmt->execute([$dept_id]);
$dept_staff = $staff_stmt->fetchAll();

include '../includes/header_glass.php';
?>
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-table text-primary me-2"></i>
                    <?php echo $filter_dir === 'outgoing' ? 'Sent Tasks' : 'Received Tasks'; ?>
                    <span class="badge bg-primary ms-2"><?php echo count($requests); ?> records</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 fw-bold">Requester</th>
                                <th class="border-0 fw-bold"><?php echo $filter_dir === 'outgoing' ? 'To Dept' : 'From Dept'; ?></th>
                                <th class="border-0 fw-bold">Task Type</th>
                                <th class="border-0 fw-bold">Subject / Asset</th>
                                <th class="border-0 fw-bold">Assigned To</th> <th class="border-0 fw-bold">Priority</th>
                                <th class="border-0 fw-bold">Status</th>
                                <?php if ($filter_dir === 'incoming'): ?>
                                <th class="border-0 fw-bold text-center">Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No requests found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $r): ?>
                                    <?php 
                                        // Status Colors
                                        $s_class = match($r['status']) {
                                            'Completed' => 'bg-success',
                                            'In Progress' => 'bg-primary',
                                            'Pending External' => 'bg-warning text-dark',
                                            'Rejected' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($r['requester_name']); ?></div>
                                            <small class="text-muted"><?php echo date('M d, H:i', strtotime($r['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($filter_dir === 'outgoing' ? $r['receiver_dept_name'] : $r['sender_dept_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['task_type']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($r['machine_name']); ?></div>
                                            <div class="small text-muted text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($r['issue_description']); ?></div>
                                        </td>
                                        <td>
                                            <span class="text-primary small fw-bold">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <?php echo htmlspecialchars($r['assigned_staff_name'] ?? 'Not Assigned'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?php echo ($r['priority'] == 'Emergency') ? 'bg-danger' : 'bg-info'; ?>">
                                                <?php echo $r['priority']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $s_class; ?> rounded-pill"><?php echo $r['status']; ?></span>
                                        </td>

                                        <?php if ($filter_dir === 'incoming'): ?>
                                        <td class="text-center">
                                            <form method="POST" class="d-flex gap-1 justify-content-center">
                                                <input type="hidden" name="req_id" value="<?php echo $r['id']; ?>">
                                                
                                                <?php if ($r['status'] !== 'Completed' && $r['status'] !== 'Rejected'): ?>
                                                    <select name="assigned_to" class="form-select form-select-sm" style="width: 120px;" required>
                                                        <option value="">Select Staff</option>
                                                        <?php foreach ($dept_staff as $staff): ?>
                                                            <option value="<?php echo $staff['id']; ?>" <?php echo ($r['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($staff['full_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <select name="status_value" class="form-select form-select-sm" style="width: 110px;">
                                                        <option value="In Progress" <?php echo ($r['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="Completed">Completed</option>
                                                        <option value="Rejected">Reject</option>
                                                    </select>

                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">No Action Needed</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer_glass.php'; ?>