<?php
// bdtsc-ietms/includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'General Manager') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BDTSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background-color: #28687F; color: white; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover { background-color: #1e4f61; }
        .main-content { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="p-3 text-center border-bottom">
                <h4>BDTSC IETMS</h4>
            </div>
            <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="manage_departments.php"><i class="bi bi-building"></i> Departments</a>
            <a href="manage_users.php"><i class="bi bi-people"></i> Manage Users</a>
            <a href="reports.php"><i class="bi bi-graph-up"></i> Reports</a>
            <hr>
            <a href="../auth/logout.php" class="text-warning"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
        <div class="col-md-10 p-4 main-content"></div>