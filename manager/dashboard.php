<?php
session_start();
require_once '../includes/db.php';
include '../includes/assign_logic.php'; // ቅድም የሰራነው የጋራ መመደቢያ ኮድ

if ($_SESSION['role'] !== 'Department Manager') { header("Location: ../auth/login.php"); exit(); }

// 1. በሲስተሙ ያሉትን ሁሉንም ቴክኒሻኖች አምጣ (ከማንኛውም ዲፓርትመንት ሊሆኑ ይችላሉ)
$tech_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'Technician'");
$tech_stmt->execute();
$technicians = $tech_stmt->fetchAll();

// 2. ከሁሉም ዲፓርትመንት Approved ተደርገው የመጡትን ጥያቄዎች ማምጣት
$stmt = $pdo->prepare("
    SELECT m.*, d.dept_name 
    FROM maintenance_requests m 
    JOIN departments d ON m.dept_id = d.id 
    WHERE m.status = 'Approved'
");
$stmt->execute();
$approved_requests = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2 class="text-danger">የጥገና ክፍል ማናጀር ገጽ</h2>
    <div class="card shadow mt-3">
        <div class="card-header bg-dark text-white"><h5>ከዲፓርትመንቶች የመጡ የጥገና ጥያቄዎች</h5></div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ዲፓርትመንት</th>
                        <th>ማሽን</th>
                        <th>ብልሽት</th>
                        <th>ቴክኒሻን መድብ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($approved_requests as $req): ?>
                    <tr>
                        <td><span class="badge bg-info text-dark"><?php echo $req['dept_name']; ?></span></td>
                        <td><?php echo $req['machine_name']; ?></td>
                        <td><?php echo $req['issue_description']; ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                                <select name="tech_id" class="form-select form-select-sm" required>
                                    <option value="">-- ባለሙያ ምረጥ --</option>
                                    <?php foreach($technicians as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_now" class="btn btn-success btn-sm">መድብ</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>