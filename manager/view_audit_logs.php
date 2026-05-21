<?php
// High-level Audit Logs Viewer for IETMS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authorization: allow Manager/Admin roles
$user_role = (string) ($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || !(stripos($user_role, 'Manager') !== false || in_array($user_role, ['Administrator','Admin','General Manager','Production Manager','Engineering Manager']))) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Filters (sanitized) ---
$action_type = trim((string) ($_GET['action_type'] ?? ''));
$filter_user_id = isset($_GET['user_id']) && ctype_digit((string)$_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$start_date = trim((string) ($_GET['start_date'] ?? ''));
$end_date = trim((string) ($_GET['end_date'] ?? ''));
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 25;

$where = [];
$params = [];

if ($action_type !== '') {
    $where[] = 'al.action = ?';
    $params[] = $action_type;
}
if ($filter_user_id > 0) {
    $where[] = 'al.user_id = ?';
    $params[] = $filter_user_id;
}
// validate dates (expecting YYYY-MM-DD)
$startTs = null; $endTs = null;
if ($start_date !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $start_date);
    if ($d) { $start_date = $d->format('Y-m-d'); $startTs = $start_date . ' 00:00:00'; }
}
if ($end_date !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $end_date);
    if ($d) { $end_date = $d->format('Y-m-d'); $endTs = $end_date . ' 23:59:59'; }
}
if ($startTs && $endTs) {
    $where[] = 'al.created_at BETWEEN ? AND ?';
    $params[] = $startTs;
    $params[] = $endTs;
} elseif ($startTs) {
    $where[] = 'al.created_at >= ?';
    $params[] = $startTs;
} elseif ($endTs) {
    $where[] = 'al.created_at <= ?';
    $params[] = $endTs;
}

$whereSql = '';
if (!empty($where)) { $whereSql = 'WHERE ' . implode(' AND ', $where); }

// Fetch distinct action types for the filter dropdown
$actionTypes = [];
try {
    $atStmt = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
    $actionTypes = $atStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    // ignore — show empty dropdown
}

// Count total for pagination
$total = 0;
try {
    $countSql = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.id {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
} catch (Throwable $e) {
    $total = 0;
}

$pages = (int) max(1, ceil($total / $perPage));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $perPage;

// Fetch page rows
$rows = [];
try {
    $sql = "SELECT al.id, al.user_id, al.action, al.details, al.ip_address, al.created_at, u.full_name, u.user_role
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereSql}
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $execParams = $params;
    $execParams[] = $perPage;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

function badge_for_action(string $action): string {
    $a = strtoupper($action);
    // default badge
    $class = 'bg-secondary text-white';
    if (strpos($a, 'APPROV') !== false || strpos($a, 'ACCEPT') !== false || strpos($a, 'SUBMIT') !== false) {
        $class = 'bg-success text-white';
    } elseif (strpos($a, 'REJECT') !== false || strpos($a, 'FAIL') !== false) {
        $class = 'bg-danger text-white';
    } elseif (strpos($a, 'PASSWORD') !== false || strpos($a, 'RESET') !== false) {
        $class = 'bg-warning text-dark';
    } elseif (strpos($a, 'LOGIN') !== false || strpos($a, 'LOGOUT') !== false) {
        $class = 'bg-info text-white';
    } elseif (strpos($a, 'TASK_') !== false || strpos($a, 'TASK') !== false) {
        $class = 'bg-primary text-white';
    }
    return $class;
}

include __DIR__ . '/../includes/header_glass.php';
?>

<style>
.glass-card { background: rgba(255,255,255,0.55); backdrop-filter: blur(8px) saturate(120%); border: 1px solid rgba(255,255,255,0.35); }
.table-wrap { max-height: 70vh; overflow: auto; }
</style>

<div class="container-fluid py-4">
    <div class="card glass-card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="card-title mb-3">System Audit Logs</h4>
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Action Type</label>
                    <select name="action_type" class="form-select form-select-sm">
                        <option value="">-- All Actions --</option>
                        <?php foreach ($actionTypes as $at): ?>
                            <option value="<?php echo htmlspecialchars($at, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($action_type === $at) ? 'selected' : ''; ?>><?php echo htmlspecialchars($at, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">User ID</label>
                    <input type="number" name="user_id" min="1" class="form-control form-control-sm" value="<?php echo $filter_user_id ? (int)$filter_user_id : ''; ?>" placeholder="User ID">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-1 text-end">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
            <div class="mt-3 small text-muted">Showing <?php echo count($rows); ?> of <?php echo $total; ?> records. Page <?php echo $page; ?> / <?php echo $pages; ?></div>
        </div>
    </div>

    <div class="card glass-card shadow-sm">
        <div class="card-body table-wrap">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>#</th>
                        <th>When</th>
                        <th>Actor</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No audit logs found for these filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td><?php echo $r['full_name'] ? htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8') : ('User #' . ((int)$r['user_id'])); ?></td>
                                <td><?php echo $r['user_role'] ? htmlspecialchars($r['user_role'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td><span class="badge <?php echo badge_for_action((string)$r['action']); ?>"><?php echo htmlspecialchars((string)$r['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td style="max-width:360px; white-space:pre-wrap; word-break:break-word;"><?php echo nl2br(htmlspecialchars((string)$r['details'], ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><?php echo htmlspecialchars((string)$r['ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="small text-muted">Page <?php echo $page; ?> of <?php echo $pages; ?> — <?php echo $perPage; ?> per page</div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $q = $_GET; // keep other query params
                    for ($p = 1; $p <= $pages; $p++):
                        $q['page'] = $p;
                        $link = '?' . http_build_query($q);
                    ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>"><a class="page-link" href="<?php echo $link; ?>"><?php echo $p; ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>
