<?php
session_start();
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'Supervisor') { header("Location: ../auth/login.php"); exit(); }

$my_dept = $_SESSION['dept_id'];

// 1. ጥያቄውን ወደ ጥገና ክፍል ማስተላለፍ (Approve)
if (isset($_POST['approve_btn'])) {
    $req_id = $_POST['req_id'];
    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = 'Approved' WHERE id = ?");
    $stmt->execute([$req_id]);
    echo "<div class='alert alert-success'>ጥያቄው ጸድቆ ወደ ጥገና ክፍል ተልኳል!</div>";
}

// 2. የራሱ ዲፓርትመንት የላካቸውን አዳዲስ ጥያቄዎች ብቻ ማምጣት
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending Approval'");
$stmt->execute([$my_dept]);
$pending_list = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h3>የዲፓርትመንት ቁጥጥር ገጽ (Supervisor Dashboard)</h3>
    <table class="table table-bordered shadow-sm mt-3">
        <thead class="table-dark">
            <tr>
                <th>ማሽን</th>
                <th>ብልሽት</th>
                <th>ሪፖርት ያደረገው</th>
                <th>እርምጃ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pending_list as $row): ?>
            <tr>
                <td><?php echo $row['machine_name']; ?></td>
                <td><?php echo $row['issue_description']; ?></td>
                <td>ሰራተኛ ID: <?php echo $row['user_id']; ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="req_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="approve_btn" class="btn btn-primary btn-sm">ወደ ጥገና ክፍል ላክ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>