<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Deputy General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/header_glass.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-dark fw-bold"><i class="bi bi-eye-fill text-primary me-2"></i>DGM Overwatch</h2>
            <p class="text-muted mb-0">Production & Technique Live Monitoring</p>
        </div>
        <div class="text-end">
            <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse ps-4 pe-4 d-none" id="emergencyAlert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="emergencyCount">0</span> Active Emergencies
            </span>
            <div class="small text-muted mt-2">Last Sync: <span id="lastSync">--:--:--</span></div>
        </div>
    </div>

    <!-- Dual-View Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="dgmTabs" user_role="tablist">
        <li class="nav-item" user_role="presentation">
            <button class="nav-link active rounded-pill px-4" id="production-tab" data-bs-toggle="pill" data-bs-target="#production-view" type="button" user_role="tab"><i class="bi bi-graph-up me-2"></i>View A: Production KPIs</button>
        </li>
        <li class="nav-item" user_role="presentation">
            <button class="nav-link rounded-pill px-4" id="technique-tab" data-bs-toggle="pill" data-bs-target="#technique-view" type="button" user_role="tab"><i class="bi bi-wrench-adjustable me-2"></i>View B: Technique Oversight</button>
        </li>
    </ul>

    <div class="tab-content" id="dgmTabsContent">
        <!-- View A: Production -->
        <div class="tab-pane fade show active" id="production-view" user_role="tabpanel">
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card glass-card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-bar-chart-fill text-success me-2"></i>Production Output vs Technical Issues</h5>
                <div style="height: 300px;">
                    <canvas id="productionKpiChart"></canvas>
                </div>
            </div>
        </div>
    </div>

        </div>

        <!-- View B: Technique -->
        <div class="tab-pane fade" id="technique-view" user_role="tabpanel">
            <div class="card glass-card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Live Maintenance Stream (Engineering)</h6>
            <div class="spinner-grow spinner-grow-sm text-info" user_role="status" id="liveIndicator">
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
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="spinner-border text-primary" user_role="status"></div>
                            <div class="mt-2">Initializing Secure Uplink...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let kpiChart;

function initChart() {
    const ctx = document.getElementById('productionKpiChart').getContext('2d');
    kpiChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Spinning', 'Weaving', 'Processing', 'Garment'],
            datasets: [
                {
                    label: 'Total Registered Issues',
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    data: [0, 0, 0, 0]
                },
                {
                    label: 'Resolved (Output Unblocked)',
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    data: [0, 0, 0, 0]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function getPriorityBadge(priority) {
    if(priority === 'Emergency') return '<span class="badge bg-danger rounded-pill px-3 py-1 animate-pulse"><i class="bi bi-tsunami"></i> Emergency</span>';
    if(priority === 'High') return '<span class="badge bg-warning text-dark rounded-pill px-3 py-1">High</span>';
    return `<span class="badge bg-secondary rounded-pill px-3 py-1">${priority}</span>`;
}

function getStatusBadge(status) {
    if(status === 'Completed') return '<span class="badge bg-success rounded-pill px-3 py-1"><i class="bi bi-check-circle"></i> Completed</span>';
    if(status === 'Blocked') return '<span class="badge bg-danger rounded-pill px-3 py-1"><i class="bi bi-x-octagon"></i> Blocked</span>';
    if(status === 'In-Progress') return '<span class="badge bg-info text-dark rounded-pill px-3 py-1"><i class="bi bi-arrow-repeat"></i> In Progress</span>';
    return `<span class="badge bg-light text-dark border rounded-pill px-3 py-1">${status}</span>`;
}

function fetchLiveOverwatchData() {
    fetch('fetch_live_data_ajax.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            // 1. Update Timestamp
            document.getElementById('lastSync').textContent = data.timestamp;

            // 2. Emergency Alert Logic
            const alertBadge = document.getElementById('emergencyAlert');
            if(data.emergency_count > 0) {
                alertBadge.classList.remove('d-none');
                document.getElementById('emergencyCount').textContent = data.emergency_count;
            } else {
                alertBadge.classList.add('d-none');
            }

            // 3. Update Chart
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

            // 4. Update Tasks Table
            let tbody = '';
            if(data.tasks.length === 0) {
                tbody = '<tr><td colspan="6" class="text-center py-4 text-muted">No live operations detected.</td></tr>';
            } else {
                data.tasks.forEach(task => {
                    let emergencyClass = task.priority === 'Emergency' && task.status !== 'Completed' ? 'bg-danger bg-opacity-10 fw-bold' : '';
                    tbody += `
                        <tr class="${emergencyClass}">
                            <td>${getPriorityBadge(task.priority)}</td>
                            <td><i class="bi bi-gear-wide-connected me-2 text-secondary"></i>${task.machine_name}</td>
                            <td>${task.dept_name || 'N/A'}</td>
                            <td>${task.technician || '<span class="text-muted small"><em>Unassigned</em></span>'}</td>
                            <td>${getStatusBadge(task.status)}</td>
                            <td class="small text-muted">${task.created_at}</td>
                        </tr>
                    `;
                });
            }
            document.getElementById('liveTasksBody').innerHTML = tbody;
        })
        .catch(err => console.error("Overwatch Uplink Failed:", err));
}

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    fetchLiveOverwatchData();
    setInterval(fetchLiveOverwatchData, 10000); // Poll every 10 seconds
});
</script>

<style>
/* Emergency Pulse Animation */
@keyframes pulseRed {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
.animate-pulse {
    animation: pulseRed 2s infinite;
}
</style>

<?php include '../includes/footer_glass.php'; ?>