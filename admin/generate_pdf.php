<?php
// 1. መጀመሪያ autoload.php-ን መጥራት (መንገዱ ትክክል መሆኑን አረጋግጥ)
require_once __DIR__ . '/../vendor/autoload.php';

// mPDF ክላሶችን መጥራት
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

// ዳታቤዝ ኮኔክሽን
require_once '../includes/db.php';

$type = $_GET['type'] ?? 'audit';

// 2. ዳታውን ማዘጋጀት (የቀድሞው ኮድህ እዚህ ይግባ)
if ($type === 'users') {
    $title = "የሰራተኞች ዝርዝር - Staff Directory";
    $query = "SELECT full_name, email, user_role, status FROM users ORDER BY full_name ASC";
    $headers = ['ሙሉ ስም', 'ኢሜይል', 'ሚና', 'ሁኔታ'];
} elseif ($type === 'reports') {
    $title = "የጥገና ጥያቄዎች ሪፖርት - Maintenance Report";
    $query = "SELECT created_at, status, description FROM maintenance_requests ORDER BY created_at DESC";
    $headers = ['ቀን', 'ሁኔታ', 'ዝርዝር መግለጫ'];
} else {
    $title = "የሲስተም ኦዲት ታሪክ - Audit Trail";
    $query = "SELECT l.created_at, u.full_name, l.action, l.details FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC";
    $headers = ['ጊዜ', 'ተጠቃሚ', 'ድርጊት', 'ዝርዝር'];
}

$stmt = $pdo->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. mPDF-ን ማስነሳት
try {
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'autoScriptToLang' => true, // አማርኛን እንዲለይ
        'autoLangToFont' => true    // ፎንቱን እንዲያስተካክል
    ]);

    // 4. HTML ግንባታ
    $html = '
    <div style="font-family: sans-serif;">
        <h2 style="text-align:center; color:#333; border-bottom: 2px solid #333; padding-bottom: 10px;">' . $title . '</h2>
        <table border="1" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #ffffff; color: #fff;">';
                foreach ($headers as $h) { $html .= '<th style="padding:10px;">' . $h . '</th>'; }
    $html .= '  </tr>
            </thead>
            <tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td style="padding:8px; border: 1px solid #ccc;">' . htmlspecialchars($cell ?? '---') . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    // 5. ማመንጨት
    $mpdf->WriteHTML($html);
    $mpdf->Output($type . "_report.pdf", Destination::INLINE);

} catch (\Exception $e) {
    echo "ስህተት፦ " . $e->getMessage();
}