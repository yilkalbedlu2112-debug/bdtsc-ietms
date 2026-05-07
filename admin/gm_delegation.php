<?php
session_start();
require_once '../includes/db.php';

// 1. መግቢያ ፈቃድ (GM ብቻ መሆኑን ማረጋገጥ)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/header_glass.php';

// 2. ንቁ ውክልና ካለ መፈለግ
$stmt_active = $pdo->prepare("SELECT d.*, u.full_name, u.user_role 
                               FROM delegations d 
                               JOIN users u ON d.delegated_to = u.id 
                               WHERE d.delegated_by = ? AND d.status = 'Active' 
                               LIMIT 1");
$stmt_active->execute([$_SESSION['user_id']]);
$active_delegation = $stmt_active->fetch();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-shield-shaded me-2"></i>GM Authority Delegation</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form id="delegationForm">
                        <input type="hidden" name="action" value="delegate_authority">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Deputy General Manager</label>
                            <select name="delegate_to" class="form-select form-select-lg border-0 bg-light" required>
                                <option value="">-- Select Deputy --</option>
                                <?php
                                // Deputy General Manager የሚል ሮል ያላቸውን ብቻ ያመጣል
                                $sql = "SELECT id, full_name, user_role FROM users 
                                        WHERE user_role = 'Deputy General Manager' 
                                        AND status = 'Active' 
                                        AND id != ? 
                                        ORDER BY full_name ASC";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$_SESSION['user_id']]);
                                
                                while($row = $stmt->fetch()) {
                                    echo "<option value='{$row['id']}'>{$row['full_name']} (Deputy General Manager)</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Delegation Reason (Remark)</label>
                            <textarea name="delegation_notes" class="form-control border-0 bg-light" rows="3" placeholder="Reason for delegation..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2 rounded-pill shadow-sm">
                            Confirm Deputy Delegation
                        </button>
                    </form>

                </div>
            </div>

            <?php if ($active_delegation): ?>
            <div class="mt-4 p-3 bg-white rounded shadow-sm border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 text-muted small">Currently Delegated to:</p>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($active_delegation['full_name']); ?></h6>
                    </div>
                    <button onclick="cancelDelegation(<?php echo $active_delegation['id']; ?>)" class="btn btn-sm btn-outline-danger rounded-pill">
                        Cancel Delegation
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer_glass.php'; ?>