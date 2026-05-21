<?php
// includes/TaskAction.php (Updated)

class TaskAction {
    private $pdo;
    private $allowed_transitions = [
        'Pending' => ['In Progress'],
        'In Progress' => ['Blocked', 'Under Review'],
        'Blocked' => ['In Progress'],
        'Under Review' => [],
        'Completed' => []
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function updateStatus($task_id, $new_status, $user_id) {
        // 1. የአሁኑን ሁኔታ እና ዲፓርትመንት ማረጋገጥ
        $stmt = $this->pdo->prepare("SELECT status, dept_id FROM maintenance_requests WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch();

        if (!$task) return ['success' => false, 'message' => 'Task not found or unauthorized.'];

        // 2. የ Workflow ህግን ማረጋገጥ (BR-04)
        if (!in_array($new_status, $this->allowed_transitions[$task['status']] ?? [])) {
            return ['success' => false, 'message' => "Invalid transition from {$task['status']} to $new_status"];
        }

        try {
            $this->pdo->beginTransaction();

            // 3. Status ማዘመን
            $sql = "UPDATE maintenance_requests SET status = ? WHERE id = ?";
            $params = [$new_status, $task_id];
            
            // UC-11: Verification Logic
            if ($new_status === 'Under Review') {
                $sql = "UPDATE maintenance_requests SET status = 'Under Review', is_verified = 0 WHERE id = ?";
                $params = [$task_id];
                $this->notifyShiftLeader($task['dept_id'], $task_id);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->pdo->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function notifyShiftLeader($dept_id, $task_id) {
        $sql = "INSERT INTO notifications (user_id, message, type) 
                SELECT id, 'Task #$task_id submitted for verification', 'verification_pending' 
                FROM users WHERE dept_id = ? AND user_role = 'Shift Leader' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$dept_id]);
    }
    public function getMyTasks($user_id) {
    $sql = "SELECT id, title, description, deadline, priority, status, is_verified, feedback, created_at, machine_name, issue_description 
            FROM maintenance_requests 
            WHERE assigned_to = ? 
            ORDER BY created_at DESC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

