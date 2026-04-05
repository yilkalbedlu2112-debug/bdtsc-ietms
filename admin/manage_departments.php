<?php
session_start();
require_once '../includes/db.php';

// ጀነራል ማናጀር መሆኑን ማረጋገጥ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

// አዲስ ዲፓርትመንት ለመመዝገብ
if (isset($_POST['add_dept'])) {
    $name = $_POST['dept_name'];
    $desc = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO departments (dept_name, description) VALUES (?, ?)");
    if ($stmt->execute([$name, $desc])) {
        $success = "ዲፓርትመንቱ በትክክል ተመዝግቧል!";
    }
}

// ሁሉንም ዲፓርትመንቶች ለማምጣት
$departments = $pdo->query("SELECT * FROM departments ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <title>Departments Management | IETMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-building"></i> የዲፓርትመንት መቆጣጠሪያ</h2>
        <a href="dashboard.php" class="btn btn-secondary">ወደ ዳሽቦርድ ተመለስ</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">አዲስ ዲፓርትመንት ጨምር</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">የዲፓርትመንት ስም</label>
                            <input type="text" name="dept_name" class="form-control" placeholder="ምሳሌ፡ Spinning" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">መግለጫ (Description)</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_dept" class="btn btn-success w-100">መዝግብ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-table">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>የዲፓርትመንት ስም</th>
                                <th>መግለጫ</th>
                                <th>ድርጊት</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($dept['description']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>