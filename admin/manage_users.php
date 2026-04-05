<?php 
require_once '../includes/db.php';
include '../includes/admin_header.php';

// 1. አዲስ ተጠቃሚ ለመመዝገብ
if (isset($_POST['add_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email']; // ዳታቤዝህ ላይ email ስለሚል
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = $_POST['role'];
    $dept_id = !empty($_POST['dept_id']) ? $_POST['dept_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, dept_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$full_name, $email, $password, $role, $dept_id])) {
        echo "<div class='alert alert-success mt-3'>ተጠቃሚው በትክክል ተመዝግቧል!</div>";
    }
}

// 2. ለፎርሙ እንዲሆኑ ዲፓርትመንቶችን ማምጣት
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

// 3. የተመዘገቡ ተጠቃሚዎችን ከነ ዲፓርትመንት ስማቸው ማምጣት
// ማሳሰቢያ፡ በዳታቤዝህ username የሚል አምድ ስለሌለ እዚህም email ተጠቅመናል
$users = $pdo->query("SELECT u.*, d.dept_name FROM users u 
                      LEFT JOIN departments d ON u.dept_id = d.id 
                      ORDER BY u.id DESC")->fetchAll();
?>

<div class="container-fluid mt-4">
    <h2 class="mb-4 text-dark"><i class="bi bi-people-fill"></i> Manage Users (ተጠቃሚዎች ማስተዳደሪያ)</h2>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm p-4 border-0 rounded-3">
                <h5 class="card-title mb-4">Add New User</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-secondary">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="ሙሉ ስም ያስገቡ">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Email (የተጠቃሚ ስም)</label>
                        <input type="email" name="email" class="form-control" required placeholder="example@bdtsc.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="ሚስጥር ቁጥር">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary">Role (ስልጣን)</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- ስልጣን ይምረጡ --</option>
                            <option value="General Manager text-danger">General Manager (ጀነራል ማናጀር)</option>
                            <option value="Department Manager">Department Manager (ዲፓርትመንት ማናጀር)</option>
                            <option value="Shift Leader">Shift Leader (የፈረቃ መሪ)</option>
                            <option value="Supervisor">Supervisor (ሱፐርቫይዘር)</option>
                            <option value="Employee">Employee (ሰራተኛ)</option>
                            <option value="Technician">Technician (ቴክኒሻን)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary">Department (የስራ ክፍል)</label>
                        <select name="dept_id" class="form-select">
                            <option value="">Select Department (አማራጭ)</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary w-100 py-2 shadow-sm">Register User</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="mb-0">የተመዘገቡ ተጠቃሚዎች ዝርዝር</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email/Username</th>
                                <th>Role</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo ($u['role'] == 'General Manager') ? 'bg-danger' : 'bg-info text-dark'; ?>">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-secondary small">
                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($u['dept_name'] ?? 'N/A'); ?>
                                    </span>
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

<?php include '../includes/admin_footer.php'; ?>