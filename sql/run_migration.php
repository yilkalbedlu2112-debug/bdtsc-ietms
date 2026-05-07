<?php
/**
 * One-time migration: add is_read column to notifications table.
 * Run this once, then delete the file.
 */
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0');
    echo "SUCCESS: Column 'is_read' added to notifications table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "INFO: Column 'is_read' already exists.\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
