<?php
session_start();
require_once '../includes/db.php';

// የደህንነት ማረጋገጫ፡ Deputy General Manager ብቻ እንዲገባ
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Deputy General Manager') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ንቁ የሆነ ውክልና ካለ መረጃውን ማምጣት (ለማሳያ ያህል)
$active_del_stmt = $pdo->prepare("SELECT d.*, u.full_name FROM delegations d 
                                 JOIN users u ON d.delegated_to = u.id 
                                 WHERE d.delegated_by = ? AND d.status = 'Active' LIMIT 1");
$active_del_stmt->execute([$user_id]);
$current_delegation = $active_del_stmt->fetch();

include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-eye-fill text-primary me-2"></i>DGM Overwatch</h2>
            <p class="text-muted mb-0">Production & Technique Live Monitoring</p>
        </div>
        
        <div class="text-end">
            <!-- Delegation Button -->
            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#dgmDelegateModal">
                <i class="bi bi-person-gear me-2"></i>Delegate Authority
            </button>

            <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse ps-4 pe-4 d-none" id="emergencyAlert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="emergencyCount">0</span> Active Emergencies
            </span>
            <div class="small text-muted mt-2">Last Sync: <span id="lastSync"><?php echo date('H:i:s'); ?></span></div>
        </div>
    </div>

    <!-- Active Delegation Status Alert -->
    <?php if (isset($current_delegation) && $current_delegation): ?>
    <div class="alert bg-primary bg-opacity-10 border-primary text-primary rounded-pill py-2 px-4 mb-4 d-inline-block shadow-sm">
        <i class="bi bi-info-circle-fill me-2"></i>
        Authority currently delegated to: <strong><?php echo htmlspecialchars($current_delegation['full_name']); ?></strong>
        <span class="mx-2">|</span>
        <small class="text-muted"><?php echo htmlspecialchars($current_delegation['remark']); ?></small>
    </div>
    <?php endif; ?>

    <!-- Dual-View Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="dgmTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="production-tab" data-bs-toggle="pill" data-bs-target="#production-view" type="button" role="tab"><i class="bi bi-graph-up me-2"></i>View A: Production KPIs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="technique-tab" data-bs-toggle="pill" data-bs-target="#technique-view" type="button" role="tab"><i class="bi bi-wrench-adjustable me-2"></i>View B: Technique Oversight</button>
        </li>
    </ul>

    <div class="tab-content" id="dgmTabsContent">
        <!-- View A: Production KPIs -->
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

        <!-- View B: Technique Oversight -->
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
                        <tbody id="liveTasksBody">
                            <!-- Data injected via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delegation (ትክክለኛው የPHP ሎጂክ የተጨመረበት) -->
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
                    
                    <div class="alert alert-info bg-opacity-10 border-info small mb-4 text-primary">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        እባክዎ ስልጣንዎን በጊዜያዊነት ለማጋራት ከሚከተሉት አራት የፕሮዳክሽን ማናጀሮች አንዱን ይምረጡ።
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Select Production Manager</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-person-badge"></i></span>
                            <select name="delegate_to" class="form-select border-0 bg-light py-2" required>
    <option value="">-- Select Production Manager --</option>
    <?php
    // u.user_role አሁን 'Department Manager' የሚለውን እንዲፈልግ ተደርጓል
    $sql = "SELECT u.id, u.full_name, d.dept_name 
            FROM users u 
            JOIN departments d ON u.dept_id = d.id 
            WHERE u.user_role = 'Department Manager' 
            AND (
                d.dept_name = 'Spinning Department' OR 
                d.dept_name = 'Weaving Department' OR 
                d.dept_name = 'Processing Department' OR 
                d.dept_name = 'Garment Department'
            ) 
            ORDER BY u.full_name ASC";
    
    $stmt = $pdo->query($sql);
    $managers = $stmt->fetchAll();

    if (count($managers) > 0) {
        foreach ($managers as $m) {
            // "Department" የሚለውን ቃል ለዕይታ ቀንሰን እናሳየው
            $clean_dept = str_replace(' Department', '', $m['dept_name']);
            echo "<option value='{$m['id']}'>{$m['full_name']} - ({$clean_dept} Manager)</option>";
        }
    } else {
        // አሁንም ካልመጣ የሚከተሉትን ነጥቦች በዳታቤዝህ አረጋግጥ
        echo "<option value='' disabled>No 'Department Manager' found in Production.</option>";
    }
    ?>
</select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Reason for Delegation</label>
                        <textarea name="delegation_notes" class="form-control border-0 bg-light" rows="3" placeholder="Write the reason or duration here..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">
                        <i class="bi bi-check-circle me-1"></i> Confirm Delegation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let kpiChart;
let lastEmergencyCount = 0;
const alertSound = new Audio('../assets/sounds/emergency.mp3'); 

function initChart() {
    const ctx = document.getElementById('productionKpiChart').getContext('2d');
    kpiChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Spinning', 'Weaving', 'Processing', 'Garment'],
            datasets: [
                {
                    label: 'Total Issues',
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    data: [0, 0, 0, 0]
                },
                {
                    label: 'Resolved',
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    data: [0, 0, 0, 0]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
}

function playAlertSafely() {
    let playPromise = alertSound.play();
    if (playPromise !== undefined) {
        playPromise.then(_ => {}).catch(error => {
            console.warn("Audio play prevented:", error.message);
        });
    }
}

function getPriorityBadge(priority) {
    if(priority === 'Emergency') return '<span class="badge bg-danger rounded-pill px-3 py-1 animate-pulse">Emergency</span>';
    if(priority === 'High') return '<span class="badge bg-warning text-dark rounded-pill px-3 py-1">High</span>';
    return `<span class="badge bg-secondary rounded-pill px-3 py-1">${priority}</span>`;
}

function getStatusBadge(status) {
    if(status === 'Completed') return '<span class="badge bg-success rounded-pill px-3 py-1">Completed</span>';
    if(status === 'In-Progress') return '<span class="badge bg-info text-dark rounded-pill px-3 py-1">In Progress</span>';
    return `<span class="badge bg-light text-dark border rounded-pill px-3 py-1">${status}</span>`;
}

function fetchLiveOverwatchData() {
    fetch('fetch_live_data_ajax.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            document.getElementById('lastSync').textContent = data.timestamp;

            const alertBadge = document.getElementById('emergencyAlert');
            if(data.emergency_count > 0) {
                alertBadge.classList.remove('d-none');
                document.getElementById('emergencyCount').textContent = data.emergency_count;
                if(data.emergency_count > lastEmergencyCount) {
                    playAlertSafely();
                }
            } else {
                alertBadge.classList.add('d-none');
            }
            lastEmergencyCount = data.emergency_count;

            const rTotals = [];
            const rCompleted = [];
            ['Spinning', 'Weaving', 'Processing', 'Garment'].forEach(dept => {
                if(data.stats[dept]) {
                    rTotals.push(data.stats[dept].total);
                    rCompleted.push(data.stats[dept].completed);
                } else {
                    rTotals.push(0); rCompleted.push(0);
                }
            });
            kpiChart.data.datasets[0].data = rTotals;
            kpiChart.data.datasets[1].data = rCompleted;
            kpiChart.update();

            let tbody = '';
            if(data.tasks.length === 0) {
                tbody = '<tr><td colspan="6" class="text-center py-4 text-muted">No live operations.</td></tr>';
            } else {
                data.tasks.forEach(task => {
                    let emergencyRow = (task.priority === 'Emergency' && task.status !== 'Completed') ? 'table-danger fw-bold' : '';
                    tbody += `
                        <tr class="${emergencyRow}">
                            <td>${getPriorityBadge(task.priority)}</td>
                            <td><i class="bi bi-gear-wide-connected me-2"></i>${task.machine_name}</td>
                            <td>${task.dept_name}</td>
                            <td>${task.technician || '<em>Unassigned</em>'}</td>
                            <td>${getStatusBadge(task.status)}</td>
                            <td class="small text-muted">${task.created_at}</td>
                        </tr>`;
                });
            }
            document.getElementById('liveTasksBody').innerHTML = tbody;
        })
        .catch(err => console.error("Sync Error:", err));
}

// Delegation Form AJAX (አዲስ የተጨመረ)
document.getElementById('dgmDelegateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('dgm_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            alert("Delegation confirmed!");
            location.reload();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => alert("System error. Please check dgm_controller.php"));
});

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    fetchLiveOverwatchData();
    setInterval(fetchLiveOverwatchData, 10000); 
});
</script>

<style>
@keyframes pulseRed {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.animate-pulse { animation: pulseRed 2s infinite; }
.glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border-radius: 20px; }
.nav-pills .nav-link.active { background-color: var(--bs-primary); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3); }
</style>

<?php include '../includes/footer_glass.php'; ?>