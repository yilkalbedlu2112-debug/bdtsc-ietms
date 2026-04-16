<?php
session_start();
require_once '../includes/db.php'; // Path ተስተካክሏል

header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($task_id && $status) {
        try {
            // 2. ስራው ለዚህ ሰራተኛ መሰጠቱን እና አሁን ያለበትን ሁኔታ ማረጋገጥ (Security & Workflow)
            $stmt = $pdo->prepare("SELECT status FROM maintenance_requests WHERE id = ? AND assigned_to = ?");
            $stmt->execute([$task_id, $user_id]);
            $current_task = $stmt->fetch();

            if ($current_task) {
                // BR-04 & BR-05: Workflow Validation
                // ስራው አስቀድሞ Completed ከሆነ መቀየር አይቻልም
                if ($current_task['status'] === 'Completed') {
                    echo json_encode(['success' => false, 'message' => 'ይህ ስራ ቀድሞውኑ ተጠናቋል። መለወጥ አይቻልም።']);
                    exit();
                }

                // 3. ዳታቤዝ ማዘመን (Update Logic)
                $sql = "UPDATE maintenance_requests SET status = ? ";
                $params = [$status, $task_id];

                // UC-11: ስራው ሲጠናቀቅ ሰዓት መመዝገብ
                if ($status === 'Completed') {
                    $sql .= ", completed_at = NOW() ";
                }
                
                $sql .= "WHERE id = ?";
                $update = $pdo->prepare($sql);

                if ($update->execute($params)) {
                    // UC-16: Audit Log መመዝገብ (የ log_action ፋንክሽን በ functions.php ውስጥ መኖሩን እርግጠኛ ሁን)
                    if (function_exists('log_action')) {
                        log_action($pdo, $user_id, "Task Update", "Task #$task_id changed to $status");
                    }

                    echo json_encode([
                        'success' => true, 
                        'message' => "ሁኔታው ወደ $status ተቀይሯል"
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ዳታቤዝ ማዘመን አልተቻለም።']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ስራው አልተገኘም ወይም ለእርስዎ አልተመደበም።']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
        }
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);