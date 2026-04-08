<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.*, d.dept_name, d.dept_type FROM users u LEFT JOIN departments d ON u.dept_id = d.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-person-lines-fill me-2 text-primary"></i> My Profile</h3>
        <div class="text-muted fw-medium"><?php echo date('D, M d, Y'); ?></div>
    </div>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm glass-card p-4 h-100">
                <div class="text-center mb-4">
                    <img src="../assets/images/user.png" class="rounded-circle shadow" width="120" height="120" style="object-fit: cover; border: 4px solid var(--bg-gray);">
                    <h4 class="fw-bold mt-3"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <span class="badge bg-soft-primary text-primary"><?php echo htmlspecialchars($user['role']); ?></span>
                </div>
                
                <hr class="opacity-25 my-4">
                
                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Username</label>
                    <div class="fw-medium p-2 bg-light rounded bg-opacity-50">
                        <i class="bi bi-person me-2"></i><?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Department</label>
                    <div class="fw-medium p-2 bg-light rounded bg-opacity-50">
                        <i class="bi bi-building me-2"></i><?php echo htmlspecialchars($user['dept_name'] ?? 'None'); ?> 
                        <span class="text-muted small">(<?php echo htmlspecialchars($user['dept_type'] ?? 'N/A'); ?>)</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted fw-bold text-uppercase">Email</label>
                    <div class="fw-medium p-2 bg-light rounded bg-opacity-50">
                        <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($user['email'] ?? 'Not provided'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security / Password Update -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm glass-card p-4 h-100">
                <h5 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-shield-lock me-2 text-warning"></i> Password Management</h5>
                
                <form id="passwordForm">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-key"></i></span>
                            <input type="password" id="current_password" name="current_password" class="form-control border-0 bg-light" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-lock"></i></span>
                            <input type="password" id="new_password" name="new_password" class="form-control border-0 bg-light" required minlength="6">
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-medium">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control border-0 bg-light" required minlength="6">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill" id="btnUpdate">
                            <i class="bi bi-check-circle me-1"></i> Update Security
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const current = document.getElementById('current_password').value;
    const newPass = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;

    if (newPass !== confirm) {
        Swal.fire({
            icon: 'error',
            title: 'Mismatch',
            text: 'New Password and Confirm Password do not match.',
            background: 'rgba(255, 255, 255, 0.9)',
            backdrop: 'rgba(0,0,0,0.4)'
        });
        return;
    }

    const btn = document.getElementById('btnUpdate');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    btn.disabled = true;

    let formData = new FormData();
    formData.append('current_password', current);
    formData.append('new_password', newPass);

    fetch('update_password_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Security';
        btn.disabled = false;
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Your password has been updated securely.',
                background: 'rgba(255, 255, 255, 0.95)',
                timer: 2000,
                showConfirmButton: false
            });
            document.getElementById('passwordForm').reset();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: data.error || 'Unable to update password.',
                background: 'rgba(255, 255, 255, 0.95)'
            });
        }
    })
    .catch(err => {
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Security';
        btn.disabled = false;
        Swal.fire({
            icon: 'error',
            title: 'Critical Error',
            text: 'Communication with server failed.'
        });
        console.error(err);
    });
});
</script>

<?php include '../includes/footer_glass.php'; ?>
