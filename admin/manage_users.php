<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// 1. ዲፓርትመንቶችን ከዳታቤዝ አምጣ (ለ Dropdown)
$depts = $pdo->query("SELECT * FROM departments")->fetchAll();

// 2. አዲስ ተጠቃሚ ለመመዝገብ (ያለ password_hash)
if (isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // እዚህ ጋር ሀሽ ሳናደርግ እንዳለ እንወስደዋለን
    $role = $_POST['role'];
    $dept_id = $_POST['dept_id'];

    $sql = "INSERT INTO users (full_name, email, password, role, dept_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$full_name, $email, $password, $role, $dept_id])) {
        echo "<div class='alert alert-success shadow-sm'>ተጠቃሚው በተሳካ ሁኔታ ተመዝግቧል!</div>";
    }
}

// 3. የተመዘገቡ ተጠቃሚዎችን ከነ ዲፓርትመንታቸው ለማየት
$users = $pdo->query("SELECT users.*, departments.dept_name 
                      FROM users 
                      LEFT JOIN departments ON users.dept_id = departments.id 
                      ORDER BY users.id DESC")->fetchAll();
?>

<div class="container mt-4">
    <h3 class="mb-4">ተጠቃሚዎች ማስተዳደሪያ (Manage Users)</h3>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0">
                <h5 class="text-primary mb-3">አዲስ ተጠቃሚ መዝግብ</h5>
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">ሙሉ ስም</label>
                        <input type="text" name="full_name" class="form-control" placeholder="ለምሳሌ፡ ታደሰ በቀለ" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">ኢሜይል</label>
                        <input type="email" name="email" class="form-control" placeholder="tade@gmail.com" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">የይለፍ ቃል (Password)</label>
                        <input type="text" name="password" class="form-control" placeholder="ቀላል ፓስወርድ" required>
                        <small class="text-muted">ማሳሰቢያ፡ ፓስወርዱ እንዳለ ነው የሚመዘገበው።</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">የሥራ ድርሻ (Role)</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- ድርሻ ምረጥ --</option>
                            <option value="Department Manager">Department Manager</option>
                            <option value="Shift Leader">Shift Leader</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Employee">Employee</option>
                            <option value="Technician">Technician</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">ዲፓርትመንት</label>
                        <select name="dept_id" class="form-select" required>
                            <option value="">ዲፓርትመንት ምረጥ</option>
                            <?php foreach($depts as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo $d['dept_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-success w-100">መዝግብ</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ስም</th>
                                <th>ኢሜይል</th>
                                <th>ድርሻ</th>
                                <th>ዲፓርትመንት</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php 
                                        $badge = 'bg-info';
                                        if($u['role'] == 'Technician') $badge = 'bg-warning text-dark';
                                        if($u['role'] == 'Department Manager') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $u['role']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($u['dept_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>