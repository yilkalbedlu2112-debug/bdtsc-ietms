<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php'; // ይህ ፋይል ነው Sidebar የሚጨምርልህ

// ለአጭር መረጃ (Statistics)
$user_count = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$dept_count = $pdo->query("SELECT count(*) FROM departments")->fetchColumn();
$request_count = $pdo->query("SELECT count(*) FROM maintenance_requests")->fetchColumn();
?>

<div class="row">
    <div class="col-md-12">
        <div class="card p-4 border-0 shadow-sm" style="border-left: 5px solid #28687F !important;">
            <h3>Welcome, BDTSC General Manager!</h3>
            <p class="text-muted">የባህር ዳር ጨርቃጨርቅ ፋብሪካ የጥገና አስተዳደር ሲስተም (IETMS)</p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-3 shadow-sm border-0">
            <h5>Total Users</h5>
            <h2><?php echo $user_count; ?></h2>
            <a href="manage_users.php" class="text-white small text-decoration-none">View All Users →</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-success text-white p-3 shadow-sm border-0">
            <h5>Total Departments</h5>
            <h2><?php echo $dept_count; ?></h2>
            <a href="manage_departments.php" class="text-white small text-decoration-none">Manage Depts →</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-danger text-white p-3 shadow-sm border-0">
            <h5>New Requests</h5>
            <h2><?php echo $request_count; ?></h2>
            <a href="all_requests.php" class="text-white small text-decoration-none">View All Requests →</a>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>