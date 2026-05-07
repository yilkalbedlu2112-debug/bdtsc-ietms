<?php
/**
 * UC-07: Assign task (POST). Requires tasks.assigned_to_dept + leader dept_id.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$shift_leader_id = (int) $_SESSION['user_id'];
$dept_id = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$shift_leader_name = trim((string) ($_SESSION['full_name'] ?? 'Unknown'));

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

$hasAssignedToDept = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'assigned_to_dept'"
)->fetchColumn() > 0;

$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

function sl_redirect_flash(string $type, string $message = ''): void
{
    $q = ['flash' => $type];
    if ($message !== '') {
        $q['msg'] = $message;
    }
    header('Location: assign_task_view.php?' . http_build_query($q));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sl_redirect_flash('error', 'Use the dashboard to assign tasks.');
}

if (!$hasAssignedToDept || $dept_id <= 0) {
    sl_redirect_flash('error', 'Assignment requires your department and tasks.assigned_to_dept on each task.');
}

$task_id = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
$employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);

if (!$task_id || !$employee_id) {
    sl_redirect_flash('error', 'Invalid task or employee selection.');
}

try {
    $pdo->beginTransaction();

    $tchk = $pdo->prepare(
        "SELECT id, status, `{$titleCol}` AS ttitle FROM tasks WHERE id = ? AND assigned_to_dept = ? FOR UPDATE"
    );
    $tchk->execute([$task_id, $dept_id]);
    $taskRow = $tchk->fetch(PDO::FETCH_ASSOC);
    if (!$taskRow || !in_array($taskRow['status'], ['Pending', 'Redo'], true)) {
        $pdo->rollBack();
        sl_redirect_flash('error', 'Task is not Pending/Redo for your department, or is not visible.');
    }

    $empLookup = $pdo->prepare(
        'SELECT id, full_name FROM users WHERE id = ? AND dept_id = ? AND status = ? AND user_role = ?'
    );
    $empLookup->execute([$employee_id, $dept_id, 'Active', 'Employee']);
    $empRow = $empLookup->fetch(PDO::FETCH_ASSOC);
    if (!$empRow) {
        $pdo->rollBack();
        sl_redirect_flash('error', 'Selected user is not an active Employee in your department.');
    }
    $employee_name = trim((string) ($empRow['full_name'] ?? 'Unknown'));

    $check_load = $pdo->prepare(
        "SELECT COUNT(*) FROM tasks WHERE `{$assigneeCol}` = ? AND (status IS NULL OR status <> 'Completed')"
    );
    $check_load->execute([$employee_id]);
    $current_load = (int) $check_load->fetchColumn();

    $high_workload = $current_load >= 3;

    $setParts = ["`{$assigneeCol}` = ?", 'status = ?'];
    $params = [$employee_id, 'Assigned'];
    if ($hasAssignedBy) {
        $setParts[] = 'assigned_by = ?';
        $params[] = $shift_leader_id;
    }
    if ($hasAssignedAt) {
        $setParts[] = 'assigned_at = NOW()';
    }
    $params[] = $task_id;

    $upd = $pdo->prepare('UPDATE tasks SET ' . implode(', ', $setParts) . ' WHERE id = ?');
    $upd->execute($params);

    $msg = 'You have been assigned a task: ' . ($taskRow['ttitle'] ?? ('#' . $task_id));
    if ($notifHasIsRead) {
        $ins = $pdo->prepare(
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'task_assignment', 0)"
        );
        $ins->execute([$employee_id, $msg]);
    } else {
        $ins = $pdo->prepare(
            "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'task_assignment')"
        );
        $ins->execute([$employee_id, $msg]);
    }

    if ($high_workload) {
        log_action(
            $pdo,
            $shift_leader_id,
            'High Workload',
            "Alert: Employee {$employee_name} (ID {$employee_id}) had {$current_load} active task(s) before assignment of task #{$task_id}."
        );
    }

    $delegation_log = 'Shift Leader ' . $shift_leader_name . ' delegated Task #' . $task_id . ' to Employee ' . $employee_name;
    log_action($pdo, $shift_leader_id, 'TASK_ASSIGNMENT', $delegation_log);

    $pdo->commit();
    sl_redirect_flash('success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sl_redirect_flash('error', $e->getMessage());
}
