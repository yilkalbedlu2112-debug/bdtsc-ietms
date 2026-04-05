<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['req_id'])) {
    $req_id = $_POST['req_id'];
    $feedback = $_POST['feedback'];

    // ሁኔታውን ወደ 'Completed' መቀየር እና ቴክኒሻኑ የሰራውን መመዝገብ
    $sql = "UPDATE maintenance_requests SET status = 'Completed', feedback = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$feedback, $req_id])) {
        header("Location: dashboard.php?msg=Task Completed");
    } else {
        echo "ስህተት ተፈጥሯል፣ እባክዎ እንደገና ይሞክሩ።";
    }
}
?>