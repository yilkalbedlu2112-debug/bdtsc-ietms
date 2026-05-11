<?php
// includes/ProductionTask.php
class ProductionTask {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ተግባር ለመፍጠር (UC-06)
    public function createTask($data) {
        $sql = "INSERT INTO maintenance_requests 
                (user_id, dept_id, assigned_to, receiver_dept_id, machine_name, issue_description, priority, status, task_type, due_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['user_id'], $data['dept_id'], $data['assigned_to'], 
            $data['target_dept'], $data['title'], $data['description'], 
            $data['priority'], $data['status'], $data['task_type'], $data['due_date']
        ]);
    }

    // ማሳወቂያ ለመላክ (UC-09)
    public function sendNotification($target_dept, $msg) {
        $sql = "INSERT INTO notifications (user_id, dept_id, role_target, message, type, created_at) 
                SELECT id, ?, 'Department Manager', ?, 'task_assignment', NOW() 
                FROM users WHERE dept_id = ? AND user_role IN ('Department Manager', 'Engineering Manager') AND status = 'Active'";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$target_dept, $msg, $target_dept]);
    }
}