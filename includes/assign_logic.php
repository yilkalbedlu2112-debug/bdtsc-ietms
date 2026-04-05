<?php
if (isset($_POST['assign_now'])) {
    $req_id = $_POST['req_id'];
    $tech_id = $_POST['tech_id'];
    $assigner_id = $_SESSION['user_id'];

    $sql = "UPDATE maintenance_requests SET assigned_to = ?, supervisor_id = ?, status = 'Assigned' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tech_id, $assigner_id, $req_id]);
    echo "<div class='alert alert-success'>ስራው ለቴክኒሻን ተመድቧል!</div>";
}
?>