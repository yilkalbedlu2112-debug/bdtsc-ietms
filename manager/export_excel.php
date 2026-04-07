<?php
session_start();
require_once '../includes/db.php';

if (isset($_POST['export_excel'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $report_type = $_POST['report_type'];

    // 1. ዳታውን ከዳታቤዝ ማምጣት
    $sql = "SELECT * FROM maintenance_requests WHERE DATE(created_at) BETWEEN ? AND ?";
    if ($report_type === 'completed') {
        $sql .= " AND status = 'Completed'";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $tasks = $stmt->fetchAll();

    // 2. ለብሮውዘሩ ፋይሉ Excel መሆኑን መንገር
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=BDTSC_Report_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // 3. የ Excel ሰንጠረዥ ይዘት (HTML Table)
    echo '<table border="1">';
    echo '<tr><th colspan="5" style="font-size:20px;">Bahir Dar Textile Share Company (BDTSC)</th></tr>';
    echo '<tr><th colspan="5">Maintenance Task Report (' . $start_date . ' to ' . $end_date . ')</th></tr>';
    echo '<tr>
            <th style="background-color:#f2f2f2;">Machine Name</th>
            <th style="background-color:#f2f2f2;">Task Type</th>
            <th style="background-color:#f2f2f2;">Priority</th>
            <th style="background-color:#f2f2f2;">Status</th>
            <th style="background-color:#f2f2f2;">Created At</th>
          </tr>';

    foreach ($tasks as $task) {
        echo '<tr>';
        echo '<td>' . $task['machine_name'] . '</td>';
        echo '<td>' . $task['task_type'] . '</td>';
        echo '<td>' . $task['priority'] . '</td>';
        echo '<td>' . $task['status'] . '</td>';
        echo '<td>' . $task['created_at'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?>