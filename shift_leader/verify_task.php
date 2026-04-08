<?php
// በ dashboard.php ውስጥ የሚጨመር የ"ማሳወቂያ" ሰንጠረዥ
$stmt = $pdo->prepare("SELECT r.*, u.full_name FROM maintenance_requests r 
                       JOIN users u ON r.assigned_to = u.id 
                       WHERE r.dept_id = ? AND r.status = 'Completed' AND r.is_verified = 0");
$stmt->execute([$_SESSION['dept_id']]);
$completed_tasks = $stmt->fetchAll();
?>

<div class="card shadow-sm mt-4 border-warning">
    <div class="card-header bg-warning text-dark fw-bold">
        <i class="bi bi-check-circle-fill"></i> መጠናቀቃቸው የተገለጹ ስራዎች (ለማረጋገጥ)
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ማሽን</th>
                    <th>የሰራው ቴክኒሻን</th>
                    <th>የጥገና ማስታወሻ</th>
                    <th>ውሳኔ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($completed_tasks as $ct): ?>
                <tr>
                    <td><?php echo $ct['machine_name']; ?></td>
                    <td><?php echo $ct['full_name']; ?></td>
                    <td><small><?php echo $ct['completion_notes']; ?></small></td>
                    <td>
                        <form method="POST" action="approve_task.php">
                            <input type="hidden" name="req_id" value="<?php echo $ct['id']; ?>">
                            <button type="submit" name="approve" class="btn btn-sm btn-success">አረጋግጥና ለማናጀር አሳውቅ</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>