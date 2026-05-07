<?php
session_start();

// 1. የመግቢያ ፈቃድ ማረጋገጫ
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Department Manager', 'Engineering Manager'], true)) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. ዳታቤዝ ግንኙነት
require_once '../includes/db.php'; 

// 3. የዲፓርትመንት ዳታን ቀድሞ ማውጣት
$stmt_dept = $pdo->prepare("SELECT dept_name, dept_type FROM departments WHERE id = ?");
$stmt_dept->execute([$_SESSION['dept_id']]);
$dept_data = $stmt_dept->fetch();

$dept_name = $dept_data['dept_name'] ?? 'Unknown Dept';
$dept_type = $dept_data['dept_type'] ?? ''; 

// 4. ዲፓርትመንቱ Production መሆኑን እዚሁ መለየት (ለፎርሙ እንዲመች)
$is_production_dept = false;
$prod_keywords = ['Spinning', 'Weaving', 'Processing', 'Garment'];
foreach ($prod_keywords as $key) {
    if (stripos($dept_name, $key) !== false) {
        $is_production_dept = true;
        break;
    }
}

// 5. header_glass.php ን መጥራት
include '../includes/header_glass.php'; 

// fallback ካልተገኙ (ደህንነት)
if (!isset($roles)) { $roles = ['Employee']; }

// 6. ንቁ ውክልና መፈለግ (በፎቶው ባየነው መሰረት 'Active' በትልቅ 'A')
$stmt_active = $pdo->prepare("SELECT d.*, u.full_name, u.user_role 
                               FROM delegations d 
                               JOIN users u ON d.delegated_to = u.id 
                               WHERE d.delegated_by = ? AND d.status = 'Active' 
                               ORDER BY d.created_at DESC LIMIT 1");
$stmt_active->execute([$_SESSION['user_id']]);
$active_delegation = $stmt_active->fetch();
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Left Side: Delegation Form -->
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-primary text-white py-3" style="border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Delegate Authority</h5>
                </div>
                <div class="card-body p-4">
                    <?php
                    // 1. Identify if the department is Production based on its name
                    $is_production_dept = false;
                    $prod_keywords = ['Spinning', 'Weaving', 'Processing', 'Garment'];
                    
                    foreach ($prod_keywords as $key) {
                        if (stripos($dept_name, $key) !== false) {
                            $is_production_dept = true;
                            break;
                        }
                    }

                    // 2. Set delegation targets based on department type
                    if ($is_production_dept) {
                        $delegation_targets = ['Shift Leader', 'Supervisor'];
                        $label_text = "Delegate (Supervisor / Shift Leader)";
                        $placeholder_text = "Select Supervisor / Shift Leader";
                    } else {
                        $delegation_targets = $roles; // From header_glass.php
                        $label_text = "Delegate (Staff / Officer)";
                        $placeholder_text = "Select Employee";
                    }
                    ?>

                    <form id="delegationForm">
                        <input type="hidden" name="action" value="delegate_authority">
                        
                        <div class="alert alert-light border-start border-4 border-info small mb-4">
                            <i class="bi bi-info-circle-fill text-info me-2"></i>
                            You are delegating authority for the <strong><?php echo htmlspecialchars($dept_name); ?></strong>.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted"><?php echo $label_text; ?></label>
                            <select name="delegate_to" class="form-select bg-light border-0 py-2" required>
                                <option value=""><?php echo $placeholder_text; ?></option>
                                <?php
                                if (!empty($delegation_targets)) {
                                    $placeholders = implode(',', array_fill(0, count($delegation_targets), '?'));
                                    
                                    $sql = "SELECT id, full_name, user_role FROM users 
                                            WHERE dept_id = ? 
                                            AND user_role IN ($placeholders) 
                                            AND status = 'Active' 
                                            AND id != ?
                                            ORDER BY full_name ASC";
                                    
                                    $stmt = $pdo->prepare($sql);
                                    $params = array_merge([$_SESSION['dept_id']], $delegation_targets, [$_SESSION['user_id']]);
                                    $stmt->execute($params);
                                    
                                    $count = 0;
                                    while($row = $stmt->fetch()) {
                                        $count++;
                                        echo "<option value='{$row['id']}'>{$row['full_name']} - ({$row['user_role']})</option>";
                                    }

                                    if ($count == 0) {
                                        echo "<option disabled>No eligible " . implode('/', $delegation_targets) . " found.</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Reason / Delegation Notes</label>
                            <textarea name="delegation_notes" class="form-control bg-light border-0" rows="3" placeholder="e.g., Annual leave, Business trip..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm mt-2">
                            <i class="bi bi-check-all me-1"></i> Confirm Delegation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Active Delegation Status -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-white py-3 border-0">
                     <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Active Delegation</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <?php if ($active_delegation): ?>
                        <div class="py-2">
                            <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                <i class="bi bi-person-badge fs-2"></i>
                            </div>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($active_delegation['full_name']); ?></h4>
                            <p class="badge bg-light text-primary mb-3"><?php echo htmlspecialchars($active_delegation['user_role']); ?></p>
                            
                            <div class="d-block mb-4">
                                <span class="badge bg-success-soft text-success px-3 py-2 rounded-pill">
                                    <i class="bi bi-clock-history me-1"></i> 
                                    Active Since: <?php echo date('M d, Y', strtotime($active_delegation['created_at'])); ?>
                                </span>
                            </div>
                            
                            <div class="bg-light p-3 rounded-3 mb-4 text-start border-start border-3 border-primary">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 10px;">Reason for Delegation:</small>
                                <span class="text-dark small"><?php echo htmlspecialchars($active_delegation['remark'] ?? 'No reason provided'); ?></span>
                            </div>

                            <button onclick="cancelDelegation(<?php echo $active_delegation['id']; ?>)" class="btn btn-outline-danger rounded-pill px-5">
                                <i class="bi bi-x-circle me-1"></i> Cancel Delegation
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="py-5">
                            <i class="bi bi-shield-slash text-light fs-1 mb-3 d-block" style="font-size: 4rem !important;"></i>
                            <p class="text-muted">No active delegation is currently in place.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('delegationForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const formData = new FormData(this);
    // ፋይሉ በዚሁ ፎልደር (manager) ውስጥ ስለሚገኝ አድራሻውን አስተካክለናል
    fetch('process_delegation.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Delegation confirmed successfully.',
                timer: 2000
            }).then(() => location.reload());
        } else {
            Swal.fire('Error!', data.message || 'Something went wrong', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error!', 'Could not connect to server.', 'error');
    });
});

function cancelDelegation(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to cancel this delegation.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Cancel it!',
        cancelButtonText: 'Back'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'cancel_delegation');
            formData.append('delegation_id', id);

            // አድራሻው 'process_delegation.php' ብቻ መሆኑን አረጋግጥ (ከዘለልክበት ገጽ አንጻር)
            fetch('process_delegation.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Cancelled!', 'Delegation has been removed.', 'success')
                    .then(() => location.reload());
                } else {
                    // እዚህ ጋር ዳታቤዙ የሚመልሰውን ትክክለኛ ስህተት ያሳየሃል
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Server connection failed.', 'error');
            });
        }
    });
}
</script>