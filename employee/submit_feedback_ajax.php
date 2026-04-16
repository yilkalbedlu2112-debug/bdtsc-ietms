<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php'; // log_action() እዚህ ይገኛል ተብሎ ታስቧል

header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $dept_id = $_SESSION['dept_id'];
    $category = $_POST['category'] ?? null;
    $description = $_POST['description'] ?? null;
    $task_id = $_POST['task_id'] ?? null; // አማራጭ፡ ከአንድ የተወሰነ ታስክ ጋር የተያያዘ ከሆነ

    // 2. Validation (BR-07: Feedback must have description)
    if (!$category || !$description) {
        echo json_encode(['success' => false, 'message' => 'እባክዎ ሁሉንም አስፈላጊ ቦታዎች ይሙሉ']);
        exit();
    }

    try {
        // 3. መረጃውን በ feedback_logs ሰንጠረዥ ውስጥ ማስቀመጥ
        $sql = "INSERT INTO feedback_logs (user_id, dept_id, task_id, category, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$user_id, $dept_id, $task_id, $category, $description])) {
            $feedback_id = $pdo->lastInsertId();

            // 4. UC-16: Audit Log መመዝገብ
            if (function_exists('log_action')) {
                log_action($pdo, $user_id, "Feedback Submitted", "Submitted $category blocker: " . substr($description, 0, 50));
            }

            // 5. BR-09: ለShift Leader እና ማናጀር ኖቲፊኬሽን መላክ (Notification System ካለህ)
            // እዚህ ጋር በ 'notifications' table ውስጥ ሪከርድ ማስገባት ትችላለህ
            $notif_sql = "INSERT INTO notifications (user_id, role_target, message, link, created_at) 
                          SELECT id, user_role, 'አዲስ የብልሽት ሪፖርት ከ $dept_id ቀርቧል', 'view_feedback.php?id=$feedback_id', NOW() 
                          FROM users WHERE dept_id = ? AND user_role IN ('Shift Leader', 'Manager')";
            $notif_stmt = $pdo->prepare($notif_sql);
            $notif_stmt->execute([$dept_id]);

            echo json_encode([
                'success' => true, 
                'message' => 'ሪፖርቱ በተሳካ ሁኔታ ተልኳል! ማናጀርዎ እንዲያውቀው ተደርጓል።'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ሪፖርቱን መላክ አልተቻለም።']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid Request']);