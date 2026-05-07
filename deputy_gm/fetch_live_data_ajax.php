<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

// ደህንነት ማረጋገጫ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Deputy General Manager') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $production_depts = ['Spinning', 'Weaving', 'Processing', 'Garment'];
    $stats = [];
    
    // 1. KPI Stats ማሰባሰብ
    foreach ($production_depts as $dept) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN m.status = 'Completed' THEN 1 ELSE 0 END) as completed
                  FROM maintenance_requests m
                  JOIN departments d ON m.dept_id = d.id
                  WHERE d.dept_name = ?"; 
        $stmt = $pdo->prepare($query);
        $stmt->execute([$dept]);
        $res = $stmt->fetch();
        
        $stats[$dept] = [
            'total' => (int)($res['total'] ?? 0),
            'completed' => (int)($res['completed'] ?? 0)
        ];
    }
    
    // 2. የቀጥታ ስራዎች ክትትል (Live Stream)
    $tasks_stmt = $pdo->query("
        SELECT m.machine_name, m.priority, m.status, d.dept_name, m.created_at, u.full_name as technician 
        FROM maintenance_requests m 
        JOIN departments d ON m.dept_id = d.id 
        LEFT JOIN users u ON m.assigned_to = u.id
        ORDER BY CASE 
            WHEN m.priority = 'Emergency' AND m.status != 'Completed' THEN 1 
            WHEN m.priority = 'High' AND m.status != 'Completed' THEN 2 
            ELSE 3 
        END, m.created_at DESC 
        LIMIT 20
    ");
    $tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. ያልተጠናቀቁ Emergency ስራዎች ብዛት
    $emergency_stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE priority = 'Emergency' AND status != 'Completed'");
    $emergency_count = $emergency_stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'tasks' => $tasks,
        'emergency_count' => (int)$emergency_count,
        'timestamp' => date('H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}