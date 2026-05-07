<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'delegate_authority') {
        $delegate_to = $_POST['delegate_to']; // የተወካዩ User ID
        $notes = $_POST['delegation_notes'];
        $delegated_by = $_SESSION['user_id'];
        $dept_id = $_SESSION['dept_id'];

        try {
            $pdo->beginTransaction();

            // 1. ማንኛውንም የቆየ Active ውክልና መዝጋት
            // --- የድሮውን ውክልና ስታቆም ---
// 'Inactive' የነበረውን ወደ 'Cancelled' ቀይረነዋል
// --- 1. የድሮውን ውክልና ስታቆም ---
// 'Inactive' የሚለውን ቃል በፍጹም እንዳትጠቀም፤ 'Cancelled' በለው
$update = $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE delegated_by = ? AND status = 'Active'");
$update->execute([$delegated_by]);

// --- 2. አዲሱን ውክልና ስታስገባ ---
// 'Active' የሚለው ቃል በትክክል ከዳታቤዙ ጋር መግጠሙን አረጋግጥ (ካፒታል A)
$sql = "INSERT INTO delegations (delegated_by, delegated_to, remark, status, created_at) 
        VALUES (?, ?, ?, 'Active', NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([$delegated_by, $delegate_to, $notes]);

            // 3. መረጃዎችን ለማሳወቂያ ማዘጋጀት (የተወካዩን ሮል ለማወቅ)
            $user_stmt = $pdo->prepare("SELECT user_role FROM users WHERE id = ?");
            $user_stmt->execute([$delegate_to]);
            $delegate_info = $user_stmt->fetch();
            $target_role = $delegate_info['user_role'] ?? 'Employee';

            // 4. ማሳወቂያ ለተወካዩ መላክ (Notifications Table)
            $msg = "You have been officially delegated authority by " . $_SESSION['full_name'] . ". Check your dashboard for details.";
            $link = "delegation_status.php"; // ተወካዩ ማየት የሚችልበት ገጽ ካለህ

            $notif_sql = "INSERT INTO notifications (user_id, user_role, dept_id, role_target, message, link, type, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $notif_stmt = $pdo->prepare($notif_sql);
            $notif_stmt->execute([
                $delegate_to,        // user_id
                $target_role,       // user_role
                $dept_id,           // dept_id
                $target_role,       // role_target
                $msg,               // message
                $link,              // link
                'Delegation'        // type
            ]);

            $pdo->commit();
            echo json_encode(['success' => true]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ውክልና መሰረዝ (Cancel)
    // ... ከላይ ያለው የ delegate_authority ክፍል እንዳለ ሆኖ ...

if ($action == 'cancel_delegation') {
    $id = $_POST['delegation_id'];
    try {
        // ስህተቱ እዚህ ነበር፡ 'Inactive' ሳይሆን 'Cancelled' መሆን አለበት
        $stmt = $pdo->prepare("UPDATE delegations SET status = 'Cancelled' WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
}