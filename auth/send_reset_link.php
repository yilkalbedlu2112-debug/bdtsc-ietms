<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer ፋይሎች ያሉበትን አድራሻ በትክክል ማመላከትህን አረጋግጥ
require '../vendor/autoload.php'; 
require_once '../includes/db.php';

if (isset($_POST['reset_request'])) {
    $email = trim($_POST['email']);

    // 1. ኢሜይሉ በዳታቤዝ ውስጥ መኖሩን ማረጋገጥ
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. ለደህንነት ሲባል ሚስጥራዊ Token እና የሚያበቃበትን ጊዜ (Expiry) መፍጠር
        $token = bin2hex(random_bytes(32)); // ረጅም ሚስጥራዊ ጽሁፍ
        $expires = date("U") + 1800; // ለ30 ደቂቃ (1800 ሰከንድ) ብቻ የሚሰራ

        $expires = date("Y-m-d H:i:s", strtotime('+30 minutes'));
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
        $update->execute([$token, $expires, $email]);

        // 4. የ PHPMailer ቅንብር
        $mail = new PHPMailer(true);

        try {
            // የሰርቨር ቅንብር
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yilkal893@gmail.com'; // ያንተ የ Gmail አድራሻ
            $mail->Password   = 'ftdobdvldnojvzlr';   // ከጎግል ያወጣኸው 16 ዲጂት App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // ተቀባይና ላኪ
            $mail->setFrom('yilkal893@gmail.com', 'BDTSC-IETMS Support');
            $mail->addAddress($email);

            // የሊንኩ አድራሻ (ይህ ሊንክ ወደ 3ኛው ፋይል ይወስደዋል)
            // ፋይሉ በ htdocs/bdtsc-ietms/auth/ ውስጥ ስላለ ሊንኩ እንዲህ መሆን አለበት
$reset_link = "http://localhost/bdtsc-ietms/auth/reset_password.php?token=" . $token;

            // የኢሜይሉ ይዘት (Content)
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - BDTSC';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #28687F;'>BDTSC-IETMS</h2>
                    <p>ሰላም፣ የይለፍ ቃልዎን ለመቀየር ጥያቄ አቅርበዋል።</p>
                    <p>የይለፍ ቃልዎን ለመቀየር ከታች ያለውን ሰማያዊ በተን ይጫኑ። ይህ ሊንክ ለ30 ደቂቃ ብቻ ያገለግላል።</p>
                    <a href='$reset_link' style='display: inline-block; padding: 10px 20px; background-color: #28687F; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>ፓስወርድ ቀይር</a>
                    <p style='margin-top: 20px; font-size: 12px; color: #777;'>ይህንን ጥያቄ እርስዎ ካላቀረቡ፣ እባክዎ ይህንን ኢሜይል ችላ ይበሉት።</p>
                </div>";

            $mail->send();
            header("Location: forgot_password.php?success=የመቀየሪያ ሊንክ ወደ ኢሜይልዎ ተልኳል። እባክዎ ኢንቦክስዎን ቼክ ያድርጉ።");
            exit();

        } catch (Exception $e) {
            header("Location: forgot_password.php?error=ኢሜይል መላክ አልተቻለም። ስህተት፦ {$mail->ErrorInfo}");
            exit();
        }
    } else {
        header("Location: forgot_password.php?error=ይህ ኢሜይል በሲስተሙ ውስጥ አልተገኘም።");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}