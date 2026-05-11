<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/db.php';
/** @var PDO $pdo */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$type = $_GET['type'] ?? 'audit';

// 1. ዳታውን ማዘጋጀት
if ($type === 'users') {
    $fileName = "Users_List";
    $title = "የሰራተኞች ዝርዝር";
    $query = "SELECT full_name, email, user_role, status FROM users";
    $headers = ['Full Name / ሙሉ ስም', 'Email', 'Role', 'Status'];
} elseif ($type === 'reports') {
    $fileName = "Maintenance_Report";
    $title = "የጥገና ሪፖርት";
    $query = "SELECT created_at, status, description FROM maintenance_requests";
    $headers = ['Date / ቀን', 'Status', 'Description / ዝርዝር'];
} else {
    $fileName = "Audit_Logs";
    $title = "የሲስተም ኦዲት ታሪክ";
    $query = "SELECT l.created_at, u.full_name, l.action, l.details FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id";
    $headers = ['Time', 'User', 'Action', 'Details'];
}

$stmt = $pdo->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. አዲስ Spreadsheet መፍጠር
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 3. ርዕሶችን (Headers) ማስገባት
$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '1', $header);
    // ርዕሶቹን ቦልድ (Bold) ማድረግ
    $sheet->getStyle($column . '1')->getFont()->setBold(true);
    $column++;
}

// 4. ዳታውን ከሁለተኛው ረድፍ ጀምሮ ማስገባት
$rowNumber = 2;
foreach ($data as $row) {
    $column = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue($column . $rowNumber, $value);
        $column++;
    }
    $rowNumber++;
}

// 5. የኮለምኖቹን ስፋት እንደ ጽሁፉ እንዲያስተካክል (Auto-size)
foreach (range('A', $column) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 6. ወደ ብሮውዘር ዳውንሎድ እንዲሆን መላክ
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '_' . date('Ymd') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;