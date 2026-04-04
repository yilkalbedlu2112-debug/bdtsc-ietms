<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// 1. ዲፓርትመንቶችን ከዳታቤዝ አምጣ (ለ Dropdown እንዲሆኑ)
$depts = $pdo->query("SELECT * FROM departments")->fetchAll();

// 2. አዲስ ተጠቃሚ ለመመዝገብ (Form Submission)
if (isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // ለጊዜው ተራ ጽሁፍ፣ በኋላ ወደ ሀሽ እንቀይረዋለን
    $role = $_POST['role'];
    $dept_id = $_POST['dept_id'];

    $sql = "INSERT INTO users (full_name, email, password, role, dept_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$full_name, $email, $password, $role, $dept_id])) {
        echo "<div class='alert alert-success'>User registered successfully!</div>";
    }
}

// 3. የተመዘገቡ ተጠቃሚዎችን ከነ ዲፓርትመንታቸው ለማየት
$users = $pdo->query("SELECT users.*, departments.dept_name 
                      FROM users 
                      LEFT JOIN departments ON users.dept_id = departments.id")->fetchAll();
?>

<h3>Manage Users (ተጠቃሚዎች ማስተዳደሪያ)</h3>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card p-3 shadow-sm">
            <h5>Register New User</h5>
            <form method="POST">
                <div class="mb-2">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Role</label>
                    <select name="role" class="form-select" required>
                        <option value="Department Manager">Department Manager</option>
                        <option value="Shift Leader">Shift Leader</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Employee">Employee</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Department</label>
                    <select name="dept_id" class="form-select">
                        <option value="">Select Department</option>
                        <?php foreach($depts as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo $d['dept_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-success w-100">Register User</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <table class="table table-hover bg-white border">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo $u['full_name']; ?></td>
                    <td><?php echo $u['email']; ?></td>
                    <td><span class="badge bg-info text-dark"><?php echo $u['role']; ?></span></td>
                    <td><?php echo $u['dept_name'] ?? 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>