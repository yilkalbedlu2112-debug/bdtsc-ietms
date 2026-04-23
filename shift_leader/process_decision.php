<?php
session_start();
require_once '../includes/db.php';
include '../includes/header_glass.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $severity = $_POST['severity'];
    $shift_leader_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];

    try {
        $pdo->beginTransaction();

        if (isset($_POST['assign_to_employee'])) {
            $employee_id = $_POST['employee_id'];
            
            $stmt = $pdo->prepare("UPDATE maintenance_requests 
                                   SET assigned_to = ?, status = 'Assigned', is_read_by_receiver = 1, updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$employee_id, $req_id]);

            // Audit Log
            log_action($pdo, $shift_leader_id, "Task Assignment", "Shift Leader assigned task #$req_id to employee #$employee_id");

        } elseif (isset($_POST['escalate'])) {
            $stmt = $pdo->prepare("UPDATE maintenance_requests 
                                   SET receiver_dept_id = 16, status = 'Pending', is_read_by_receiver = 1, updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$req_id]);

            // Audit Log
            log_action($pdo, $shift_leader_id, "Task Escalation", "Shift Leader escalated task #$req_id to Engineering/Manager");

        }

        $pdo->commit();
        echo "<script>alert('Decision recorded successfully!'); window.location.href='dashboard.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error occurred: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
    exit();
}