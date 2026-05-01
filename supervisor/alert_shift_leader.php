<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ደህንነት፡ ሱፐርቫይዘር መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Supervisor') {
    header("Location: ../auth/login.php");
    exit();
}

// ፎርሙ በ POST መላኩን እና አስፈላጊ ዳታዎች መኖራቸውን ማረጋገጥ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_id'])) {
    
    $req_id = filter_var($_POST['req_id'], FILTER_SANITIZE_NUMBER_INT);
    $target_leader_id = isset($_POST['target_leader']) ? filter_var($_POST['target_leader'], FILTER_SANITIZE_NUMBER_INT) : null;
    $dept_id = $_SESSION['dept_id'];
    $user_id = $_SESSION['user_id'];
    $supervisor_name = $_SESSION['full_name'] ?? 'Supervisor';

    try {
        $pdo->beginTransaction(); // ለዳታ ደህንነት (Atomicity)

        // 1. የጥገና ጥያቄውን መረጃ ማግኘት (ለኦዲት ሎግ እና ለመልዕክት ይዘት)
        $check = $pdo->prepare("SELECT machine_name, title FROM maintenance_requests WHERE id = ?");
        $check->execute([$req_id]);
        $request_data = $check->fetch();

        if (!$request_data) {
            throw new Exception("Request not found.");
        }

        $subject = $request_data['machine_name'] ?: $request_data['title'];
        $msg = "URGENT: Supervisor $supervisor_name sent an alert for Task #$req_id ($subject). Needs immediate action!";

        // 2. ኖቲፊኬሽን መላክ (UC-07/BR-09)
        if ($target_leader_id) {
            // ተጠቃሚው ሽፍት ሊደር መርጦ ከሆነ - ለተመረጠው ሰው ብቻ መላክ
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read, created_at) 
                                   VALUES (?, ?, ?, 'Urgent Alert', 0, NOW())");
            $notif->execute([$target_leader_id, $dept_id, $msg]);
            $log_detail = "Specific Alert sent to Shift Leader (ID: $target_leader_id)";
        } else {
            // ምርጫ ካልተደረገ - ለሁሉም የዲፓርትመንቱ ሽፍት ሊደሮች በብሮድካስት መላክ
            $notif = $pdo->prepare("INSERT INTO notifications (user_role, dept_id, message, type, is_read, created_at) 
                                   VALUES ('Shift Leader', ?, ?, 'Urgent Alert', 0, NOW())");
            $notif->execute([$dept_id, $msg]);
            $log_detail = "Broadcast Alert sent to all Shift Leaders in Dept ID: $dept_id";
        }

        // 3. Audit Log መመዝገብ (UC-16/BR-08)
        log_action($pdo, $user_id, "Urgent Alert Sent", "$log_detail for Task ID: $req_id");

        $pdo->commit();

        echo "<script>
                alert('የአስቸኳይ ጊዜ መልዕክቱ ለተመረጠው ሽፍት ሊደር ተልኳል!');
                window.location.href='dashboard.php';
              </script>";

    } catch (Exception $e) {
        $pdo->rollBack(); // ስህተት ካለ ወደ ኋላ ይመለሳል
        echo "<script>
                alert('ስህተት ተከስቷል: " . addslashes($e->getMessage()) . "');
                window.location.href='dashboard.php';
              </script>";
    }
} else {
    header("Location: dashboard.php");
    exit();
}