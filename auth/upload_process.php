<?php
session_start();
require_once '../includes/db.php';

if (isset($_POST['submit']) && isset($_FILES['profile_image'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_image'];

    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png');

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 2097152) { // 2MB limit
                // ፋይሉ በ user ID ስም እንዲቀመጥ (user_1.jpg እንዲሆን)
                $newFileName = "user_" . $user_id . "." . $fileExt;
                $fileDestination = '../assets/images/' . $newFileName;

                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    // ዳታቤዝ ማዘመን
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $stmt->execute([$newFileName, $user_id]);

                    // ሴሽኑን ማዘመን (ወዲያውኑ እንዲቀየር)
                    $_SESSION['profile_pic'] = $newFileName;

                    header("Location: profile.php?msg=Profile picture updated!");
                    exit();
                }
            } else { header("Location: profile.php?msg=File is too big!"); }
        } else { header("Location: profile.php?msg=Error uploading file!"); }
    } else { header("Location: profile.php?msg=Invalid file type!"); }
}