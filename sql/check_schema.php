<?php
require_once __DIR__ . '/../includes/db.php';
$r = $pdo->query('DESCRIBE maintenance_requests');
while ($c = $r->fetch(PDO::FETCH_ASSOC)) {
    echo $c['Field'] . ' | ' . $c['Type'] . ' | ' . ($c['Default'] ?? 'NULL') . PHP_EOL;
}
echo "---STATUSES---" . PHP_EOL;
$s = $pdo->query("SELECT DISTINCT status FROM maintenance_requests ORDER BY status");
while ($row = $s->fetch(PDO::FETCH_ASSOC)) {
    echo $row['status'] . PHP_EOL;
}
