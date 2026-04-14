<?php
require '../includes/db.php';

if (isset($_POST['update_btn'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. ሁለቱም ፓስወርዶች መመሳሰላቸውን ማረጋገጥ
    if ($new_password !== $confirm_password) {
        header("Location: reset_password.php?token=$token&error=ፓስወርዶቹ አይመሳሰሉም!");
        exit();
    }

    // 2. Token ትክክል መሆኑን፣ ጊዜው እንዳላለፈ እና በአስተዳዳሪ መጽደቁን በድጋሚ ማረጋገጥ
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW() AND reset_approved = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // 3. አዲሱን ፓስወርድ በደህንነት መንገድ (Hash) ማመስጠር
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 4. ፓስወርዱን መቀየር እና Token-ኑን ማጥፋት (ድጋሚ እንዳይሠራ)
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL, reset_approved = 0 WHERE reset_token = ?");
        $success = $update->execute([$hashed_password, $token]);

        if ($success) {
            header("Location: login.php?success=የይለፍ ቃልዎ በተሳካ ሁኔታ ተቀይሯል። አሁን መግባት ይችላሉ።");
        } else {
            header("Location: reset_password.php?token=$token&error=ስህተት ተፈጥሯል፣ እባክዎ ድጋሚ ይሞክሩ።");
        }
    } else {
        header("Location: forgot_password.php?error=የጊዜ ገደቡ አልፏል ወይም ሊንኩ የተሳሳተ ነው።");
    }
} else {
    header("Location: login.php");
}