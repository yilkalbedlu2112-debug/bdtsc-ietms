<?php
session_start();
require_once '../includes/db.php';

// 1. ማናጀር መሆኑን ማረጋገጥ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$dept_id = $_SESSION['dept_id'];
$full_name = $_SESSION['full_name'];

// ማሳሰቢያ፡ በዳታቤዝህ ውስጥ የEngineering/Mintenance ID 2 መሆኑን አረጋግጥ
$engineering_dept_id = 2; 

// --- ስህተቶችን ለመከላከል ተለዋዋጮቹን አስቀድመን ባዶ እናድርጋቸው ---
$requests = [];
$technicians = [];
$my_requests = [];

// 2. ለኢንጂነሪንግ ማናጀር - ሁሉንም የጸደቁ የጥገና ጥያቄዎች አምጣ
if ($dept_id == $engineering_dept_id) {
    $stmt = $pdo->query("SELECT m.*, d.dept_name FROM maintenance_requests m 
                         JOIN departments d ON m.dept_id = d.id 
                         WHERE m.status IN ('Approved', 'Assigned') 
                         ORDER BY m.id DESC");
    $requests = $stmt->fetchAll();
    
    // ለቴክኒሻን መመደቢያ የሚሆኑ ባለሙያዎችን አምጣ
    $tech_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Technician'");
    $technicians = $tech_stmt->fetchAll();
} 
// 3. ለሌሎች ማናጀሮች - የራሳቸውን ክፍል ጥያቄዎች ብቻ አምጣ
else {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE dept_id = ? ORDER BY id DESC");
    $stmt->execute([$dept_id]);
    $my_requests = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-factory"></i> IETMS - ማናጀር ዳሽቦርድ</span>
        <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">ውጣ</a>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h3>እንኳን ደህና መጡ፣ <span class="text-primary"><?php echo htmlspecialchars($full_name); ?></span></h3>
            <p class="text-muted fw-bold">የዲፓርትመንት ኃላፊነት፦ 
                <span class="badge bg-secondary">
                    <?php echo ($dept_id == $engineering_dept_id) ? 'Engineering (ጥገና ክፍል)' : 'Production / General Unit'; ?>
                </span>
            </p>
        </div>
    </div>

    <?php if ($dept_id == $engineering_dept_id): ?>
        <div class="alert alert-info shadow-sm border-0"><i class="bi bi-tools"></i> የኢንጂነሪንግ ክፍል ማናጀር - የጥገና ስራዎችን ለቴክኒሻኖች እዚህ ይመድቡ።</div>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">ከሁሉም ክፍሎች የመጡ የጥገና ጥያቄዎች</h5>
                <span class="badge bg-light text-primary"><?php echo count($requests); ?> ጥያቄዎች</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ዲፓርትመንት</th>
                                <th>ማሽን</th>
                                <th>ብልሽት</th>
                                <th>ሁኔታ</th>
                                <th>ቴክኒሻን መድብ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="5" class="text-center p-4">ምንም የሚጠበቅ የጥገና ጥያቄ የለም።</td></tr>
                            <?php else: ?>
                                <?php foreach($requests as $req): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($req['dept_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['machine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['issue_description']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($req['status'] == 'Assigned') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo $req['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="../includes/assign_logic.php" method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                                            <select name="tech_id" class="form-select form-select-sm" required>
                                                <option value="">ባለሙያ ምረጥ</option>
                                                <?php foreach($technicians as $t): ?>
                                                    <option value="<?php echo $t['id']; ?>" <?php echo ($req['assigned_to'] == $t['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($t['full_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">መድብ</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-secondary shadow-sm">የዲፓርትመንት ማናጀር - የክፍልዎን የጥገና ሁኔታ እና ምርት ይከታተሉ።</div>
        
        <div class="row g-4">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white">የክፍሌ የጥገና ጥያቄዎች ሁኔታ</div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ማሽን</th>
                                    <th>ብልሽት</th>
                                    <th>ሁኔታ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_requests)): ?>
                                    <tr><td colspan="3" class="text-center p-3">ምንም የተመዘገበ ጥያቄ የለም።</td></tr>
                                <?php else: ?>
                                    <?php foreach($my_requests as $m): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($m['machine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($m['issue_description']); ?></td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?php echo $m['status']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white">የምርት ሪፖርት (Production)</div>
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <h1 class="display-4 text-success fw-bold">85%</h1>
                        <p class="text-muted">የዛሬው የክፍሉ የምርት ውጤታማነት</p>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                        </div>
                        <button class="btn btn-outline-success btn-sm w-100">ዝርዝር ሪፖርት እይ</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>