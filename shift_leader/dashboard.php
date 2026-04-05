<?php 
session_start();
require_once '../includes/db.php';
include '../includes/assign_logic.php'; // 1. ሎጂኩን እዚህ ጠራነው

// 2. በዲፓርትመንቱ ያሉ ቴክኒሻኖችን ዝርዝር ማምጣት
$tech_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'Technician' AND dept_id = ?");
$tech_stmt->execute([$_SESSION['dept_id']]);
$technicians = $tech_stmt->fetchAll();
?>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-primary text-white"><h5>ያልተመደቡ ጥያቄዎች (Manager View)</h5></div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th>ማሽን</th><th>ብልሽት</th><th>ቴክኒሻን መድብ</th></tr>
            </thead>
            <tbody>
                <?php 
                $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? AND status = 'Pending'");
                $stmt->execute([$_SESSION['dept_id']]);
                while($row = $stmt->fetch()): ?>
                <tr>
                    <td><?php echo $row['machine_name']; ?></td>
                    <td><?php echo $row['issue_description']; ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="req_id" value="<?php echo $row['id']; ?>">
                            <select name="tech_id" class="form-select form-select-sm" required>
                                <option value="">-- ምረጥ --</option>
                                <?php foreach($technicians as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_now" class="btn btn-success btn-sm">መድብ</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>