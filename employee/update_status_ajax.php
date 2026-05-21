<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/TaskAction.php';
require_once '../includes/functions.php';
/** @var PDO $pdo */
// ...
$taskAction = new TaskAction($pdo); // $pdo እዚህ ጋር መግባት አለበት
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($task_id && $status) {
        $taskAction = new TaskAction($pdo);
        $result = $taskAction->updateStatus($task_id, $status, $user_id);

        if ($result['success']) {
            $details = sprintf('task_id=%d; new_status=%s; updated_by=%d', $task_id, $status, $user_id);
            if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
                Database::log_system_activity($pdo, $user_id, 'TASK_STATUS_UPDATED', $details);
            } else {
                log_action($pdo, $user_id, "Task Update", "Task #$task_id moved to $status");
            }
        }
        echo json_encode($result);
        exit();
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid Request']);