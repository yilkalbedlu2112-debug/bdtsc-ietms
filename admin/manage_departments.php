<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// አዲስ ዲፓርትመንት ለመመዝገብ
if (isset($_POST['add_dept'])) {
    $name = $_POST['dept_name'];
    $desc = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO departments (dept_name, description) VALUES (?, ?)");
    if ($stmt->execute([$name, $desc])) {
        echo "<div class='alert alert-success'>Department added successfully!</div>";
    }
}

// ያሉትን ለማየት
$depts = $pdo->query("SELECT * FROM departments")->fetchAll();
?>

<h2>Manage Departments (የሥራ ክፍሎች ማስተዳደሪያ)</h2>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card p-3">
            <h5>Add New Department</h5>
            <form method="POST">
                <div class="mb-3">
                    <label>Department Name</label>
                    <input type="text" name="dept_name" class="form-control" required placeholder="e.g. Spinning">
                </div>
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <button type="submit" name="add_dept" class="btn btn-primary w-100">Save Department</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <table class="table table-bordered bg-white shadow-sm">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($depts as $d): ?>
                <tr>
                    <td><?php echo $d['id']; ?></td>
                    <td><?php echo $d['dept_name']; ?></td>
                    <td><?php echo $d['description']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>