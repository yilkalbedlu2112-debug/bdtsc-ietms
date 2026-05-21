<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$shift_leader_id   = (int) $_SESSION['user_id'];
$dept_id           = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$shift_leader_name = trim((string) ($_SESSION['full_name'] ?? 'Unknown'));

// ── Redirect helper ─────────────────────────────────────────────────
function sl_assign_redirect(string $type, string $message = ''): void
{
    $q = ['flash' => $type];
    if ($message !== '') {
        $q['msg'] = $message;
    }
    header('Location: assign_task_view.php?' . http_build_query($q));
    exit;
}

// ── Helper: insert notification ─────────────────────────────────────
function insert_notification(PDO $pdo, int $user_id, string $message, string $type, ?int $dept_id = null, ?string $role_target = null): void
{
    $notifHasIsRead = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
    )->fetchColumn() > 0;

    $cols   = ['user_id', 'message', 'type'];
    $vals   = ['?', '?', '?'];
    $params = [$user_id, $message, $type];

    if ($dept_id !== null) {
        $cols[]   = 'dept_id';
        $vals[]   = '?';
        $params[] = $dept_id;
    }
    if ($role_target !== null) {
        $cols[]   = 'role_target';
        $vals[]   = '?';
        $params[] = $role_target;
    }
    if ($notifHasIsRead) {
        $cols[]   = 'is_read';
        $vals[]   = '0';
    }

    $sql = 'INSERT INTO notifications (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

// ── Validate request ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sl_assign_redirect('error', 'Invalid request method.');
}

if ($dept_id <= 0) {
    sl_assign_redirect('error', 'Your profile must have a department.');
}

$task_id     = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
$source      = trim((string) ($_POST['source'] ?? 'maintenance_requests'));

if (!$task_id || !$employee_id) {
    sl_assign_redirect('error', 'Invalid task or employee selection.');
}

if (!in_array($source, ['maintenance_requests', 'tasks'], true)) {
    $source = 'maintenance_requests';
}

// ── Process assignment ──────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // 1. Verify employee is active and belongs to this department
    $empLookup = $pdo->prepare(
        'SELECT id, full_name FROM users WHERE id = ? AND dept_id = ? AND status = ? AND user_role = ?'
    );
    $empLookup->execute([$employee_id, $dept_id, 'Active', 'Employee']);
    $empRow = $empLookup->fetch(PDO::FETCH_ASSOC);

    if (!$empRow) {
        $pdo->rollBack();
        sl_assign_redirect('error', 'Selected user is not an active Employee in your department.');
    }
    $employee_name = trim((string) ($empRow['full_name'] ?? 'Unknown'));

    // ────────────────────────────────────────────────────────────────
    // SOURCE: maintenance_requests table
    // ────────────────────────────────────────────────────────────────
    if ($source === 'maintenance_requests') {

        // Verify task exists and is assignable
        $tchk = $pdo->prepare(
            "SELECT id, status, COALESCE(title, machine_name, 'Untitled') AS ttitle, assigned_to
             FROM maintenance_requests
             WHERE id = ?
               AND (assigned_to = ? OR assigned_to IS NULL OR assigned_to = 0
                    OR sender_dept_id = ? OR receiver_dept_id = ?)
             FOR UPDATE"
        );
        $tchk->execute([$task_id, $shift_leader_id, $dept_id, $dept_id]);
        $taskRow = $tchk->fetch(PDO::FETCH_ASSOC);

        if (!$taskRow) {
            $pdo->rollBack();
            sl_assign_redirect('error', 'Task not found or you do not have permission to assign it.');
        }

        $allowedStatuses = ['Pending', 'Assigned', 'Pending Approval', 'Approved'];
        if (!in_array($taskRow['status'], $allowedStatuses, true)) {
            $pdo->rollBack();
            sl_assign_redirect('error', 'Task status (' . $taskRow['status'] . ') does not allow re-assignment.');
        }

        // Update: assign to employee
        $upd = $pdo->prepare(
            "UPDATE maintenance_requests 
             SET assigned_to = ?, status = 'Assigned', assigned_at = NOW()
             WHERE id = ?"
        );
        $upd->execute([$employee_id, $task_id]);

        $task_title = $taskRow['ttitle'];

    // ────────────────────────────────────────────────────────────────
    // SOURCE: tasks table (legacy)
    // ────────────────────────────────────────────────────────────────
    } else {

        $assigneeCol = ((int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to'"
        )->fetchColumn() > 0) ? 'assigned_to' : 'assigned_employee';

        $titleCol = ((int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'title'"
        )->fetchColumn() > 0) ? 'title' : 'task_title';

        $hasAssignedBy = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_by'"
        )->fetchColumn() > 0;

        $hasAssignedAt = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_at'"
        )->fetchColumn() > 0;

        $tchk = $pdo->prepare(
            "SELECT id, status, `{$titleCol}` AS ttitle FROM tasks WHERE id = ? AND assigned_to_dept = ? FOR UPDATE"
        );
        $tchk->execute([$task_id, $dept_id]);
        $taskRow = $tchk->fetch(PDO::FETCH_ASSOC);

        if (!$taskRow || !in_array($taskRow['status'], ['Pending', 'Redo'], true)) {
            $pdo->rollBack();
            sl_assign_redirect('error', 'Task is not Pending/Redo for your department.');
        }

        $setParts = ["`{$assigneeCol}` = ?", "status = 'Assigned'"];
        $params   = [$employee_id];

        if ($hasAssignedBy) {
            $setParts[] = 'assigned_by = ?';
            $params[]   = $shift_leader_id;
        }
        if ($hasAssignedAt) {
            $setParts[] = 'assigned_at = NOW()';
        }
        $params[] = $task_id;

        $upd = $pdo->prepare('UPDATE tasks SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $upd->execute($params);

        $task_title = $taskRow['ttitle'] ?? '#' . $task_id;
    }

    // ── NOTIFICATION: Notify the assigned Employee ──────────────────
    $empMsg = 'A new task has been assigned to you by your Shift Leader.';
    insert_notification($pdo, $employee_id, $empMsg, 'task_assignment', $dept_id);

    // ── REPORTING: Notify Supervisor(s) and Department Manager(s) ───
    $reportMsg = "Shift Leader {$shift_leader_name} has assigned Task #{$task_id} to Employee {$employee_name}.";
    $superiors = $pdo->prepare(
        "SELECT id, user_role FROM users
         WHERE dept_id = ? AND status = 'Active'
           AND user_role IN ('Supervisor', 'Department Manager')
         ORDER BY user_role"
    );
    $superiors->execute([$dept_id]);
    foreach ($superiors->fetchAll(PDO::FETCH_ASSOC) as $sup) {
        insert_notification($pdo, (int) $sup['id'], $reportMsg, 'task_delegation_report', $dept_id, $sup['user_role']);
    }

    // ── AUDIT LOG ───────────────────────────────────────────────────
    $oldAssigned = $taskRow['assigned_to'] ?? null;
    $logDetail = "Task #{$task_id} delegated to {$employee_name} (old_assigned_to={$oldAssigned})";
    if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
        $details = sprintf('task_id=%d; source=%s; old_assigned_to=%s; new_assigned_to=%d; by=%d', $task_id, $source, $oldAssigned ?? 'null', $employee_id, $shift_leader_id);
        Database::log_system_activity($pdo, $shift_leader_id, 'TASK_ASSIGNED', $details);
    } else {
        log_action($pdo, $shift_leader_id, 'TASK_ASSIGNMENT', $logDetail);
    }

    // ── Commit & redirect ───────────────────────────────────────────
    $pdo->commit();
    sl_assign_redirect('success', "Task #{$task_id} assigned to {$employee_name} successfully.");

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sl_assign_redirect('error', $e->getMessage());
}
