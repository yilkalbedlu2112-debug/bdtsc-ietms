<?php
session_start();
require_once '../includes/db.php';

if (isset($_POST['login_btn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $password == $user['password']) {
        // ሴሽን መፍጠር
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        // ... ከሌሎቹ ሴሽኖች ጋር አብረህ ጨምረው
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['dept_id'] = $user['dept_id']; // ይህ መስመር መኖሩን አረጋግጥ!

        // እንደየ ሮሉ (Role) ወደ ተለያየ ፎልደር መላክ
        switch ($user['role']) {
            case 'General Manager':
                header("Location: ../admin/dashboard.php");
                break;
            case 'Department Manager':
                header("Location: ../manager/dashboard.php");
                break;
            case 'Shift Leader':
                header("Location: ../shift_leader/dashboard.php");
                break;
            case 'Supervisor':
                header("Location: ../supervisor/dashboard.php");
                break;
            case 'Employee':
                header("Location: ../employee/dashboard.php");
                break;
        }
    } else {
        echo "Invalid Email or Password!";
    }
}
?>