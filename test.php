<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    require_once 'includes/db.php';
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    echo "<p>Connected to: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Testing Home Page Logic</h2>";

// Test session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role'])) {
    echo "<p>Current user role: " . $_SESSION['role'] . "</p>";
    echo "<p>Should redirect to dashboard...</p>";
} else {
    echo "<p>No user logged in - should show home page</p>";
}

echo "<p><a href='index.php'>Back to Home Page</a></p>";
?>