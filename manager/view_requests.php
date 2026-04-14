<?php 
require_once '../includes/db.php';
include '../includes/header_glass.php';
$dept_id = $_SESSION['dept_id'];

// የዲፓርትመንቱን ስም ለማወቅ
$stmt_dept = $pdo->prepare("SELECT dept_name FROM departments WHERE id = ?");
$stmt_dept->execute([$dept_id]);
$current_dept = $stmt_dept->fetch();

// ሁኔታውን (Status) ለመቀየር
if (isset($_POST['update_status'])) {
    $req_id = $_POST['req_id'];
    $new_status = $_POST['status_value'];
    $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $req_id]);
    echo "<div class='alert alert-success'>ሁኔታው ወደ '$new_status' ተቀይሯል!</div>";
}

// የዚህን ዲፓርትመንት ጥያቄዎች ብቻ ከነ ሰራተኛው ስም አምጣ
$sql = "SELECT maintenance_requests.*, users.full_name 
        FROM maintenance_requests 
        JOIN users ON maintenance_requests.user_id = users.id 
        WHERE maintenance_requests.dept_id = ? 
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$dept_id]);
$requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>የ<?php echo $current_dept['dept_name']; ?> ክፍል የጥገና ጥያቄዎች</h3>
    <span class="badge bg-primary p-2">ጠቅላላ፡ <?php echo count($requests); ?></span>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ሪፖርት ያደረገው</th>
                    <th>ማሽን/ንብረት</th>
                    <th>የብልሽት ዝርዝር</th>
                    <th>አስቸኳይነት</th>
                    <th>ሁኔታ (Status)</th>
                    <th>እርምጃ (Action)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $r): ?>
                <tr>
                    <td><strong><?php echo $r['full_name']; ?></strong></td>
                    <td><?php echo $r['machine_name']; ?></td>
                    <td><?php echo $r['issue_description']; ?></td>
                    <td>
                        <?php 
                        $p_class = ($r['priority'] == 'Urgent' || $r['priority'] == 'High') ? 'bg-danger' : 'bg-info text-dark';
                        ?>
                        <span class="badge <?php echo $p_class; ?>"><?php echo $r['priority']; ?></span>
                    </td>
                    <td>
                        <span class="badge border text-dark">
                            <?php echo $r['status']; ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="req_id" value="<?php echo $r['id']; ?>">
                            <?php if($r['status'] == 'Pending'): ?>
                                <input type="hidden" name="status_value" value="In Progress">
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">ጥገና ጀምር</button>
                            <?php elseif($r['status'] == 'In Progress'): ?>
                                <input type="hidden" name="status_value" value="Completed">
                                <button type="submit" name="update_status" class="btn btn-success btn-sm">አጠናቅቅ</button>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-check-circle-fill"></i> ተጠናቋል</span>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($requests) == 0): ?>
                <tr>
                    <td colspan="6" class="text-center p-4 text-muted">ምንም የተመዘገበ የብልሽት ጥያቄ የለም።</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>