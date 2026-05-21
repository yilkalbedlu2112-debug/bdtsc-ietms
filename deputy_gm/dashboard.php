<?php
session_start();
require_once '../includes/db.php';
/** @var PDO $pdo */

// የደህንነት ማረጋገጫ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Deputy General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. DGM ለሌላ ሰው የሰጠው ንቁ ውክልና ካለ መረጃውን ማምጣት
$active_del_stmt = $pdo->prepare("SELECT d.*, u.full_name FROM delegations d 
                                 JOIN users u ON d.delegated_to = u.id 
                                 WHERE d.delegated_by = ? AND d.status = 'Active' LIMIT 1");
$active_del_stmt->execute([$user_id]);
$current_delegation = $active_del_stmt->fetch();

// 2. ከ General Manager ለዚህ DGM የተሰጠ የውክልና መረጃ (Notification)
$gm_notif_stmt = $pdo->prepare("SELECT d.*, u.full_name as gm_name FROM delegations d 
                               JOIN users u ON d.delegated_by = u.id 
                               WHERE d.delegated_to = ? AND u.user_role = 'General Manager' 
                               AND d.status = 'Active' LIMIT 1");
$gm_notif_stmt->execute([$user_id]);
$gm_delegation_info = $gm_notif_stmt->fetch();

include '../includes/header_glass.php';
?>
<?php
// ከ GM ለዚህ DGM የተሰጠ 'Active' ውክልና ካለ መፈለግ
$gm_check = $pdo->prepare("
    SELECT d.*, u.full_name as gm_name 
    FROM delegations d 
    JOIN users u ON d.delegated_by = u.id 
    WHERE d.delegated_to = ? 
    AND u.user_role = 'General Manager' 
    AND d.status = 'Active' 
    ORDER BY d.created_at DESC LIMIT 1
");
$gm_check->execute([$_SESSION['user_id']]);
$gm_delegation = $gm_check->fetch();
?>

<?php if ($gm_delegation): ?>
<div class="container-fluid mt-3">
    <div class="alert shadow-lg border-0 animate__animated animate__pulse animate__infinite" 
         style="background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d); color: white; border-radius: 15px;">
        <div class="d-flex align-items-center p-3">
            <div class="flex-shrink-0">
                <i class="bi bi-shield-fill-check fs-1 text-warning me-3"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold">Acting General Manager Mode Activated</h5>
                <p class="mb-0 opacity-90">
                    በዋና ስራ አስኪያጅ <strong><?php echo htmlspecialchars($gm_delegation['gm_name']); ?></strong> ሙሉ ውክልና ተሰጥቶዎታል። 
                    <br><small class="text-white-50">ማሳሰቢያ፡ <?php echo htmlspecialchars($gm_delegation['remark']); ?></small>
                </p>
            </div>
            <div class="ms-auto text-end">
                <span class="badge bg-white text-dark rounded-pill px-3">Active Now</span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid py-4">
    <?php if ($gm_delegation_info): ?>
    <div class="alert shadow-sm border-0 mb-4 animate__animated animate__fadeInDown" 
         style="background: linear-gradient(90deg, #1e3c72, #2a5298); color: white; border-radius: 15px;">
        <div class="d-flex align-items-center p-2">
            <i class="bi bi-patch-check-fill text-warning fs-2 me-3"></i>
            <div>
                <h6 class="mb-0 fw-bold">General Manager Authority Active</h6>
                <small class="opacity-75 text-white">Delegated by: <?php echo htmlspecialchars($gm_delegation_info['gm_name']); ?> | Remark: <?php echo htmlspecialchars($gm_delegation_info['remark']); ?></small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-eye-fill text-primary me-2"></i>DGM Overwatch</h2>
            <p class="text-muted mb-0">Production & Technique Live Monitoring</p>
        </div>
        
        <div class="text-end">
            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#dgmDelegateModal">
                <i class="bi bi-person-gear me-2"></i>Delegate Authority
            </button>

            <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse ps-4 pe-4 d-none" id="emergencyAlert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="emergencyCount">0</span> Active Emergencies
            </span>
            <div class="small text-muted mt-2">Last Sync: <span id="lastSync"><?php echo date('H:i:s'); ?></span></div>
        </div>
    </div>

    <?php if (isset($current_delegation) && $current_delegation): ?>
    <div class="alert bg-primary bg-opacity-10 border-primary text-primary rounded-pill py-2 px-4 mb-4 d-inline-block shadow-sm">
        <i class="bi bi-info-circle-fill me-2"></i>
        Authority currently delegated to: <strong><?php echo htmlspecialchars($current_delegation['full_name']); ?></strong>
        <span class="mx-2">|</span>
        <small class="text-muted"><?php echo htmlspecialchars($current_delegation['remark']); ?></small>
        <button onclick="cancelDgmDelegation(<?php echo $current_delegation['id']; ?>)" class="btn btn-sm btn-danger rounded-pill ms-3 px-3 py-0 shadow-sm border-0" style="font-size: 0.75rem;">Cancel</button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-4 gap-2" id="dgmTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="production-tab" data-bs-toggle="pill" data-bs-target="#production-view" type="button" role="tab"><i class="bi bi-graph-up me-2"></i>View A: Production KPIs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="technique-tab" data-bs-toggle="pill" data-bs-target="#technique-view" type="button" role="tab"><i class="bi bi-wrench-adjustable me-2"></i>View B: Technique Oversight</button>
        </li>
    </ul>

    <div class="tab-content" id="dgmTabsContent">
        <div class="tab-pane fade show active" id="production-view" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card glass-card border-0 shadow-sm p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-bar-chart-fill text-success me-2"></i>Production Output vs Technical Issues</h5>
                        <div style="height: 350px;">
                            <canvas id="productionKpiChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="technique-view" role="tabpanel">
            <div class="card glass-card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Live Maintenance Stream (Engineering)</h6>
                    <div class="spinner-grow spinner-grow-sm text-info" role="status" id="liveIndicator">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="border-0">Priority</th>
                                <th class="border-0">Asset / Machine</th>
                                <th class="border-0">Department</th>
                                <th class="border-0">Technician</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Registered</th>
                            </tr>
                        </thead>
                        <tbody id="liveTasksBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dgmDelegateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dgmDelegateForm">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-primary text-white border-0 py-3" style="border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Delegate DGM Authority</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="delegate_authority">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Select Production Manager</label>
                        <select name="delegate_to" class="form-select border-0 bg-light py-2" required>
                            <option value="">-- Select Production Manager --</option>
                            <?php
                            $sql = "SELECT u.id, u.full_name, d.dept_name FROM users u JOIN departments d ON u.dept_id = d.id 
                                    WHERE u.user_role = 'Department Manager' AND d.dept_name IN ('Spinning Department', 'Weaving Department', 'Processing Department', 'Garment Department')
                                    ORDER BY u.full_name ASC";
                            $stmt = $pdo->query($sql);
                            while ($m = $stmt->fetch()) {
                                $clean_dept = str_replace(' Department', '', $m['dept_name']);
                                echo "<option value='{$m['id']}'>{$m['full_name']} - ({$clean_dept} Manager)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Reason for Delegation</label>
                        <textarea name="delegation_notes" class="form-control border-0 bg-light" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">Confirm Delegation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let kpiChart;
let lastEmergencyCount = 0;

// 🛠️ የድምፅ ፋይል ፓዝ አስተማማኝ እንዲሆን ከ root ጀምሮ ተስተካክሏል
const alertSound = new Audio('../assets/sounds/emergency.mp3'); 

function initChart() {
    const ctx = document.getElementById('productionKpiChart').getContext('2d');
    kpiChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Spinning', 'Weaving', 'Processing', 'Garment'],
            datasets: [
                { label: 'Total Issues', backgroundColor: 'rgba(54, 162, 235, 0.5)', data: [0, 0, 0, 0] },
                { label: 'Resolved', backgroundColor: 'rgba(75, 192, 192, 0.6)', data: [0, 0, 0, 0] }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });
}

function fetchLiveOverwatchData() {
    fetch('fetch_live_data_ajax.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            document.getElementById('lastSync').textContent = data.timestamp;
            const alertBadge = document.getElementById('emergencyAlert');
            
            // አዲስ የአደጋ ጊዜ ስራዎች መኖር አለመኖራቸውን መፈተሽ
            const currentEmergencyCount = parseInt(data.emergency_count) || 0;
            
            if(currentEmergencyCount > 0) {
                alertBadge.classList.remove('d-none');
                document.getElementById('emergencyCount').textContent = currentEmergencyCount;
                
                // 🔊 አዲስ አደጋ ሲከሰት ብቻ ድምፅ እንዲያሰማ ማድረግ (Auto-play Restriction መከላከያ)
                if (currentEmergencyCount > lastEmergencyCount) {
                    alertSound.play().catch(e => console.log("Audio play blocked until user interacts with the page."));
                }
            } else { 
                alertBadge.classList.add('d-none'); 
            }
            lastEmergencyCount = currentEmergencyCount;

            const rTotals = []; const rCompleted = [];
            ['Spinning', 'Weaving', 'Processing', 'Garment'].forEach(dept => {
                if(data.stats[dept]) { rTotals.push(data.stats[dept].total); rCompleted.push(data.stats[dept].completed); }
                else { rTotals.push(0); rCompleted.push(0); }
            });
            kpiChart.data.datasets[0].data = rTotals;
            kpiChart.data.datasets[1].data = rCompleted;
            kpiChart.update();

            let tbody = '';
            data.tasks.forEach(task => {
                let pBadge = task.priority === 'Emergency' ? '<span class="badge bg-danger rounded-pill">Emergency</span>' : '<span class="badge bg-secondary rounded-pill">'+task.priority+'</span>';
                tbody += `<tr><td>${pBadge}</td><td>${task.machine_name}</td><td>${task.dept_name}</td><td>${task.technician || '---'}</td><td>${task.status}</td><td>${task.created_at}</td></tr>`;
            });
            document.getElementById('liveTasksBody').innerHTML = tbody;
        });
}

// Delegation Form AJAX
document.getElementById('dgmDelegateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('process_delegation.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => {
        if(data.success) { alert("Delegation confirmed!"); location.reload(); }
        else { alert("Error: " + data.message); }
    })
    .catch(err => alert("System error. Check process_delegation.php"));
});

// Cancel Delegation AJAX
function cancelDgmDelegation(id) {
    if(!confirm("Reclaim authority?")) return;
    const fd = new FormData();
    fd.append('action', 'cancel_delegation');
    fd.append('delegation_id', id);
    fetch('process_delegation.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => { if(data.success) location.reload(); });
}

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    fetchLiveOverwatchData();
    setInterval(fetchLiveOverwatchData, 10000); 
});
</script>

<style>
.animate-pulse { animation: pulseRed 2s infinite; }
@keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }
.glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border-radius: 20px; }
</style>

<?php include '../includes/footer_glass.php'; ?>