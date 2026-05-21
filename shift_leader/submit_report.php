<?php
/**
 * Industrial Employee Task Management System (IETMS)
 * Daily Shift Summary Report — structured form with 10 sections.
 * Expected Graduation: July 2026
 */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// የደህንነት ማረጋገጫ (Authorization) - Shift Leader መሆኑን ማረጋገጥ
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Shift Leader') {
    header('Location: ../auth/login.php'); 
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$user_id     = (int) $_SESSION['user_id'];
$dept_id     = isset($_SESSION['dept_id']) ? (int) $_SESSION['dept_id'] : 0;
$leader_name = (string) ($_SESSION['full_name'] ?? 'Shift Leader');
$dept_name   = trim((string) ($_SESSION['dept_name'] ?? ''));
$user_role   = (string) ($_SESSION['user_role'] ?? 'Shift Leader');

// የዲፓርትመንት ስም ከሌለ ከዳታቤዝ መፈለግ
if ($dept_name === '' && $dept_id > 0) {
    $dn = $pdo->prepare('SELECT dept_name FROM departments WHERE id = ?');
    $dn->execute([$dept_id]);
    $dept_name = (string) ($dn->fetchColumn() ?: '');
}

// የ notifications ሰንጠረዥ 'is_read' ኮለምን እንዳለው በዳይናሚክ ማረጋገጥ
$notifHasIsRead = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'is_read'"
)->fetchColumn() > 0;

// የሪፖርት ተቀባዮችን (ሱፐርቫይዘር እና ማናጀሮችን) መለየት
$supervisors = []; 
$managers = [];
if ($dept_id > 0) {
    $rec = $pdo->prepare(
        "SELECT id, full_name, user_role FROM users
         WHERE status='Active' AND dept_id=? AND user_role IN ('Supervisor','Department Manager','Production Manager','Engineering Manager')
         ORDER BY user_role, full_name"
    );
    $rec->execute([$dept_id]);
    foreach ($rec->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['user_role'] === 'Supervisor') {
            $supervisors[] = $r; 
        } else {
            $managers[] = $r;
        }
    }
}

$success_msg = null; 
$error_msg = null;

// ፎርሙ POST ሲደረግ የሚሰራበት ክፍል
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $send_to = $_POST['send_to'] ?? [];
    $p = $_POST;

    if (empty($send_to)) { 
        $error_msg = 'ቢያንስ አንድ ተቀባይ ይምረጡ።'; 
    } elseif ($dept_id <= 0) { 
        $error_msg = 'ዲፓርትመንት ያስፈልጋል።'; 
    } else {
        try {
            // ACID መርህን ለመጠበቅ Transaction መጀመር
            $pdo->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($send_to), '?'));
            $vs = $pdo->prepare(
                "SELECT id, full_name, user_role FROM users WHERE id IN ({$placeholders}) AND dept_id=? AND status='Active'"
            );
            $params = array_map('intval', $send_to); 
            $params[] = $dept_id;
            $vs->execute($params);
            $valid = $vs->fetchAll(PDO::FETCH_ASSOC);

            if (empty($valid)) { 
                throw new RuntimeException('ተቀባይ አልተገኘም።'); 
            }

            // ሪፖርቱን በተዋቀረ መልኩ ማዘጋጀት (Structured Report Text)
            $shift_label = match($p['shift'] ?? '') {
                'morning' => 'ጥዋት', 'afternoon' => 'ከሰዓት', 'night' => 'ሌሊት', default => $p['shift'] ?? ''
            };
            $perf_label = match($p['performance'] ?? '') {
                'full' => 'ተሟላ', 'partial' => 'ከፊል ተሟላ', 'not' => 'አልተሟላ', default => ''
            };
            $quality_label = match($p['quality'] ?? '') {
                'good' => 'ጥሩ', 'medium' => 'መካከለኛ', 'low' => 'ዝቅተኛ', default => ''
            };
            $machine_label = ($p['machine_status'] ?? '') === 'normal' ? 'መደበኛ ሁኔታ' : 'ችግር አለ';

            $report = "📋 Daily Shift Summary Report\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "ስም: {$leader_name}\n"
                . "መደብ: {$user_role}\n"
                . "ዲፓርትመንት: {$dept_name}\n"
                . "ቀን: " . ($p['report_date'] ?? date('Y-m-d')) . "\n"
                . "ሽፍት: {$shift_label}\n\n"
                . "📝 የቀን ስራ ማጠቃለያ:\n" . trim($p['work_summary'] ?? '') . "\n"
                . "የተመረቱ እቃዎች: " . trim($p['production_output'] ?? '') . "\n\n"
                . "📊 የስራ አፈጻጸም: {$perf_label}\n"
                . ($perf_label !== 'ተሟላ' && !empty($p['perf_reason']) ? "ምክንያት: " . trim($p['perf_reason']) . "\n" : "")
                . "\n⚠️ ችግሮች:\n" . trim($p['issues'] ?? 'የለም') . "\n"
                . "የተወሰዱ እርምጃዎች: " . trim($p['actions_taken'] ?? 'የለም') . "\n\n"
                . "🔍 የጥራት ሁኔታ: {$quality_label}\n"
                . (!empty($p['quality_issues']) ? "ጉዳዮች: " . trim($p['quality_issues']) . "\n" : "")
                . "\n🔧 የማሽን ሁኔታ: {$machine_label}\n"
                . (!empty($p['machine_details']) ? "ዝርዝር: " . trim($p['machine_details']) . "\n" : "")
                . "\n📋 ለቀጣይ ሽፍት መመሪያ:\n" . trim($p['handover'] ?? 'የለም') . "\n"
                . (!empty($p['extra_comments']) ? "\n💬 ተጨማሪ: " . trim($p['extra_comments']) . "\n" : "");

            // Insert the structured report into `daily_reports` (if table exists in DB)
            $report_date = $p['report_date'] ?? date('Y-m-d');
            $total_tasks = 0; $completed_tasks = 0; $blocked_tasks = 0;
            try {
                $cstmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND DATE(created_at) = ?");
                $cstmt->execute([$dept_id, $report_date]);
                $total_tasks = (int) $cstmt->fetchColumn();

                $cstmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Completed' AND DATE(created_at) = ?");
                $cstmt->execute([$dept_id, $report_date]);
                $completed_tasks = (int) $cstmt->fetchColumn();

                $cstmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE dept_id = ? AND status = 'Blocked' AND DATE(created_at) = ?");
                $cstmt->execute([$dept_id, $report_date]);
                $blocked_tasks = (int) $cstmt->fetchColumn();

                $rin = $pdo->prepare("INSERT INTO daily_reports (dept_id, created_by, shift_type, report_summary, total_tasks, completed_tasks, blocked_tasks) VALUES (?,?,?,?,?,?,?)");
                $rin->execute([$dept_id, $user_id, $p['shift'] ?? '', $report, $total_tasks, $completed_tasks, $blocked_tasks]);
                $daily_report_id = (int) $pdo->lastInsertId();
                // Append a small DR reference so downstream feedback can be associated
                $report .= "\n\n[DRID:{$daily_report_id}]";
            } catch (Throwable $e) {
                // non-fatal — continue but log the issue
                Database::log_action($pdo, $user_id, 'DAILY_REPORT_SAVE_FAILED', 'Failed saving daily_reports: ' . $e->getMessage());
                $daily_report_id = 0;
            }

            // እንደ ሰንጠረዡ አወቃቀር ማሳወቂያን ማስገባት
            if ($notifHasIsRead) {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type, is_read) VALUES (?,?,?,'daily_shift_summary',0)");
            } else {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, dept_id, message, type) VALUES (?,?,?,'daily_shift_summary')");
            }

            $names = [];
            foreach ($valid as $r) {
                $ins->execute([(int)$r['id'], $dept_id, $report]);
                $names[] = $r['full_name'].' ('.$r['user_role'].')';
            }

            $snippet = mb_substr($report, 0, 250) . '...';
            
            // ✅ መስተካከያ፦ በምዕራፍ አራት ዶክመንት መሠረት የ Static Method አጠራርን መጠቀም
            Database::log_action($pdo, $user_id, 'SHIFT_REPORT_SUBMIT', "Report sent to: " . implode(', ', $names) . ". Excerpt: {$snippet}");
            // Audit: REPORT_SUBMITTED (link to daily_reports record if available)
            if (class_exists('Database') && method_exists('Database', 'log_system_activity')) {
                $details = sprintf('daily_report_id=%d; dept_id=%d; shift=%s; sent_to=%s', $daily_report_id, $dept_id, $p['shift'] ?? '', implode(',', array_map(fn($x)=>$x['id'],$valid)));
                Database::log_system_activity($pdo, $user_id, 'REPORT_SUBMITTED', $details);
            }

            $pdo->commit();
            $success_msg = 'ሪፖርቱ ተልኳል ለ: ' . implode(', ', $names);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header_glass.php';
$dept_display = $dept_name !== '' ? $dept_name : 'Department #' . $dept_id;
$p = $_POST; // ስህተት ቢኖር ፎርሙ ላይ የሞላው ዳታ እንዳይጠፋ ማቆያ
?>

<div class="container-fluid py-4" style="max-width: 900px;">
    <div class="mb-3">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h1 class="h4 fw-bold mb-0"><i class="bi bi-file-earmark-text me-2"></i>Daily Shift Summary Report Form</h1>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="post" action="">
    
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">1</span>የሪፖርት መረጃ (Report Information)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ስም (Employee Name)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($leader_name, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">መደብ (Position)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ዲፓርትመንት (Department)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($dept_display, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ቀን (Date)</label>
                    <input type="date" name="report_date" class="form-control" value="<?php echo htmlspecialchars($p['report_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ሽፍት (Shift)</label>
                    <div class="d-flex gap-3 mt-1">
                        <?php foreach (['morning'=>'ጥዋት','afternoon'=>'ከሰዓት','night'=>'ሌሊት'] as $val=>$lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="shift" value="<?php echo $val; ?>" id="shift_<?php echo $val; ?>"
                                <?php echo (($p['shift'] ?? '') === $val) ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="shift_<?php echo $val; ?>"><?php echo $lbl; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">2</span>ተቀባዮች (Recipients)</div>
        <div class="card-body">
            <?php if (empty($managers) && empty($supervisors)): ?>
                <div class="alert alert-warning py-2 mb-0">ማናጀር ወይም ሱፐርቫይዘር አልተገኘም።</div>
            <?php ?>
            <div class="row g-2">
                <?php foreach ($managers as $m): ?>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_to[]" value="<?php echo (int)$m['id']; ?>" id="r_<?php echo (int)$m['id']; ?>"
                            <?php echo isset($p['send_to']) && in_array((string)$m['id'], $p['send_to']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="r_<?php echo (int)$m['id']; ?>">
                            <i class="bi bi-briefcase text-primary me-1"></i><?php echo htmlspecialchars($m['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($m['user_role'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($supervisors as $s): ?>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_to[]" value="<?php echo (int)$s['id']; ?>" id="r_<?php echo (int)$s['id']; ?>"
                            <?php echo isset($p['send_to']) && in_array((string)$s['id'], $p['send_to']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="r_<?php echo (int)$s['id']; ?>">
                            <i class="bi bi-person-badge text-success me-1"></i><?php echo htmlspecialchars($s['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            <span class="badge bg-light text-dark">Supervisor</span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('input[name=\'send_to[]\']').forEach(c=>c.checked=true)"><i class="bi bi-check-all me-1"></i>ሁሉንም ምረጥ</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('input[name=\'send_to[]\']').forEach(c=>c.checked=false)"><i class="bi bi-x-circle me-1"></i>አጥፋ</button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">3</span>የቀን ስራ ማጠቃለያ (Daily Work Summary)</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label small fw-bold">የተከናወኑ ዋና ስራዎች</label>
                <textarea name="work_summary" class="form-control" rows="4" required placeholder="ዛሬ የተከናወኑ ዋና ስራዎችን ይዘርዝሩ..."><?php echo htmlspecialchars($p['work_summary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div>
                <label class="form-label small fw-bold">የተመረቱ እቃዎች (Production Output)</label>
                <textarea name="production_output" class="form-control" rows="2" placeholder="ብዛት፣ አይነት፣ ወዘተ..."><?php echo htmlspecialchars($p['production_output'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">4</span>የስራ አፈጻጸም (Performance Status)</div>
        <div class="card-body">
            <label class="form-label small fw-bold">የዕቅድ እና የእውነት ንጥረ ነገር</label>
            <div class="d-flex gap-3 mb-3">
                <?php foreach (['full'=>'ተሟላ','partial'=>'ከፊል ተሟላ','not'=>'አልተሟላ'] as $v=>$l): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="performance" value="<?php echo $v; ?>" id="perf_<?php echo $v; ?>"
                        <?php echo (($p['performance'] ?? '') === $v) ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="perf_<?php echo $v; ?>"><?php echo $l; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <label class="form-label small fw-bold">ምክንያት (ከሆነ)</label>
            <textarea name="perf_reason" class="form-control" rows="2" placeholder="ካልተሟላ ምክንያቱን ይግለጹ..."><?php echo htmlspecialchars($p['perf_reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-warning text-dark me-2">5</span>የተፈጠሩ ችግሮች (Issues Encountered)</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label small fw-bold">የተፈጠሩ ችግሮች</label>
                <textarea name="issues" class="form-control" rows="3" placeholder="ችግር ከሌለ 'የለም' ብለው ይጻፉ..."><?php echo htmlspecialchars($p['issues'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div>
                <label class="form-label small fw-bold">የተወሰዱ እርምጃዎች</label>
                <textarea name="actions_taken" class="form-control" rows="2" placeholder="ችግሩን ለመፍታት ምን ተደረገ?"><?php echo htmlspecialchars($p['actions_taken'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">6</span>የጥራት ሁኔታ (Quality Status)</div>
        <div class="card-body">
            <label class="form-label small fw-bold">የጥራት ግምገማ</label>
            <div class="d-flex gap-3 mb-3">
                <?php foreach (['good'=>'ጥሩ','medium'=>'መካከለኛ','low'=>'ዝቅተኛ'] as $v=>$l): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="quality" value="<?php echo $v; ?>" id="q_<?php echo $v; ?>"
                        <?php echo (($p['quality'] ?? '') === $v) ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="q_<?php echo $v; ?>"><?php echo $l; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <label class="form-label small fw-bold">የተገኙ እንክብካቤ ጉዳዮች</label>
            <textarea name="quality_issues" class="form-control" rows="2" placeholder="የጥራት ችግር ካለ ይግለጹ..."><?php echo htmlspecialchars($p['quality_issues'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-danger me-2">7</span>የማሽን ሁኔታ (Machine Status)</div>
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="machine_status" value="normal" id="m_ok"
                        <?php echo (($p['machine_status'] ?? '') === 'normal') ? 'checked' : ''; ?> required>
                    <label class="form-check-label" for="m_ok"><i class="bi bi-check-circle text-success me-1"></i>መደበኛ ሁኔታ</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="machine_status" value="problem" id="m_bad"
                        <?php echo (($p['machine_status'] ?? '') === 'problem') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="m_bad"><i class="bi bi-exclamation-triangle text-danger me-1"></i>ችግር አለ</label>
                </div>
            </div>
            <div id="machine_detail_box">
                <label class="form-label small fw-bold">ከሆነ ዝርዝር</label>
                <textarea name="machine_details" class="form-control" rows="2" placeholder="የማሽን ብልሽት ዝርዝር..."><?php echo htmlspecialchars($p['machine_details'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-primary me-2">8</span>ለቀጣይ ሽፍት መመሪያ (Handover Notes)</div>
        <div class="card-body">
            <textarea name="handover" class="form-control" rows="3" placeholder="ለሚቀጥለው ሽፍት ተተኪ ሰራተኛ የሚያስፈልጉ መረጃዎች..."><?php echo htmlspecialchars($p['handover'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-secondary me-2">9</span>ተጨማሪ አስተያየት (Additional Comments)</div>
        <div class="card-body">
            <textarea name="extra_comments" class="form-control" rows="2" placeholder="ተጨማሪ ማስታወሻ ካለ..."><?php echo htmlspecialchars($p['extra_comments'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2"><span class="badge bg-dark me-2">10</span>ፊርማ (Signature)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ሰራተኛ ፊርማ</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($leader_name, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    <div class="form-text">ዲጂታል ፊርማ — በስርዓቱ ይመዘገባል</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">ቀን</label>
                    <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i'); ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" name="submit_report" value="1" class="btn btn-primary btn-lg w-100 mb-4 shadow"
        <?php echo (empty($managers) && empty($supervisors)) ? 'disabled' : ''; ?>>
        <i class="bi bi-send-fill me-2"></i>ሪፖርት ላክ / Submit Report
    </button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer_glass.php'; ?>