<?php
// Temporary test version - bypass session check
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Debug: Home Page Test</h1>";

if (isset($_SESSION['user_role'])) {
    echo "<p>Logged in as: " . $_SESSION['user_role'] . "</p>";
    echo "<p><a href='auth/logout.php'>Logout</a></p>";
} else {
    echo "<p>Not logged in</p>";
}

echo "<p><a href='index.php'>Back to normal home page</a></p>";

// Test database connection
try {
    require_once 'includes/db.php';
    echo "<p style='color: green;'>Database connected successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>