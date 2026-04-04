<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// ማናጀር መሆኑን ማረጋገጫ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Department Manager') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .navbar { background-color: #28687F; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">BDTSC | Manager Panel</a>
        <div class="navbar-nav ms-auto">
            <span class="nav-link text-white">Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a class="nav-link btn btn-danger btn-sm text-white ms-3" href="../auth/logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container"></div>