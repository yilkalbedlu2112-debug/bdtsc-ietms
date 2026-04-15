<?php
session_start();

// 1. የዳታቤዝ ግንኙነት (Path አስተካክለናል)
if (file_exists('../includes/db.php')) {
    require_once '../includes/db.php';
} else {
    die("Database connection file missing at ../includes/db.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_maintenance'])) {
    
    // 2. መረጃውን ከሴሽን እና ከፎርም መቀበል
    $user_id = $_SESSION['user_id'] ?? null;
    $dept_id = $_SESSION['dept_id'] ?? null; // የላኪው ዲፓርትመንት መታወቂያ

    if (!$user_id || !$dept_id) {
        die("Error: Session expired. Please login again.");
    }

    $machine_name = $_POST['machine_name'];
    $description  = $_POST['issue_description'];
    $priority     = $_POST['priority'] ?? 'Urgent';
    $status       = $_POST['status'] ?? 'Pending';
    $task_type    = $_POST['task_type'] ?? 'Maintenance';

    try {
        // 3. SQL Query - ጥያቄውን ወደ ዳታቤዝ ማስገባት
        // እዚህ ጋር user_id እና dept_id የላኪውን ዲፓርትመንት ይይዛሉ
        $sql = "INSERT INTO maintenance_requests 
                (user_id, dept_id, machine_name, issue_description, priority, status, task_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id, 
            $dept_id, 
            $machine_name, 
            $description, 
            $priority, 
            $status, 
            $task_type
        ]);

        // 4. ስኬታማ ከሆነ ወደ ዳሽቦርድ ይመለሳል
        header("Location: dashboard.php?success=sent");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: dashboard.php");
    exit();
}