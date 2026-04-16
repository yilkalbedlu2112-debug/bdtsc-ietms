-- ================================================================
-- BDTSC IETMS: Complete Schema Correction & Upgrade Migration
-- Run this once against bdtsc_db in phpMyAdmin
-- Safe to run on the existing database — uses ALTER/MODIFY only
-- ================================================================

-- ---------------------------------------------------------------
-- ISSUE 1: maintenance_requests.priority ENUM mismatch
-- DB has:  'Low','Medium','High','Urgent'
-- PHP uses: 'Normal','High','Emergency','Urgent'
-- Fix:  Add 'Normal' and 'Emergency', rename default to 'Normal'
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    MODIFY COLUMN `priority`
        ENUM('Normal','Low','Medium','High','Urgent','Emergency')
        NOT NULL DEFAULT 'Normal';

-- ---------------------------------------------------------------
-- ISSUE 2: maintenance_requests.status ENUM mismatch
-- DB has:  'Pending Approval','Approved','Rejected','Assigned','In Progress','Completed'
-- PHP uses: 'Pending','In Progress','Completed','Rejected','Assigned','Approved'
-- Fix:  Add 'Pending' as a valid value, keep all other values
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    MODIFY COLUMN `status`
        ENUM('Pending','Pending Approval','Approved','Rejected',
             'Assigned','In Progress','Completed')
        NOT NULL DEFAULT 'Pending';

-- Back-fill old rows: blank status → Pending
UPDATE `maintenance_requests` SET `status` = 'Pending' WHERE `status` = '' OR `status` IS NULL;

-- ---------------------------------------------------------------
-- ISSUE 3: maintenance_requests.task_type ENUM too narrow
-- DB has:  'Production','Maintenance','Administrative','Quality'
-- PHP uses: many more types (Breakdown, Safety, Planning, HR, etc.)
-- Fix:  Expand to VARCHAR so new types can be added freely
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    MODIFY COLUMN `task_type`
        VARCHAR(50) NOT NULL DEFAULT 'Maintenance';

-- ---------------------------------------------------------------
-- ISSUE 4: Missing cross-departmental columns (from our migration)
-- These are the columns added by departmental_requests_migration.sql
-- Using ADD COLUMN IF NOT EXISTS for safety (MariaDB 10.3+)
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    ADD COLUMN IF NOT EXISTS `sender_dept_id`
        INT NULL DEFAULT NULL
        COMMENT 'Department that sent the request'
        AFTER `dept_id`,

    ADD COLUMN IF NOT EXISTS `receiver_dept_id`
        INT NULL DEFAULT NULL
        COMMENT 'Department that should handle the request'
        AFTER `sender_dept_id`,

    ADD COLUMN IF NOT EXISTS `request_type`
        ENUM('Repair','Manpower','Resource','Legal',
             'Maintenance','Administrative','Other')
        NOT NULL DEFAULT 'Maintenance'
        COMMENT 'Cross-dept request category'
        AFTER `task_type`,

    ADD COLUMN IF NOT EXISTS `is_read_by_receiver`
        TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Notification flag: 0=unread by receiver dept'
        AFTER `receiver_dept_id`;

-- Back-fill sender_dept_id from existing dept_id
UPDATE `maintenance_requests`
SET `sender_dept_id` = `dept_id`
WHERE `sender_dept_id` IS NULL;

-- Back-fill receiver_dept_id for maintenance rows → Engineering (id=16)
UPDATE `maintenance_requests`
SET `receiver_dept_id` = 16
WHERE `receiver_dept_id` IS NULL AND `task_type` = 'Maintenance';

-- ---------------------------------------------------------------
-- ISSUE 5: Missing `title` column
-- mgr_ajax.php line 48 inserts into `title` column for admin depts
-- but the column does not exist (PHP falls back to machine_name)
-- Fix: Add `title` as an alias/alternative to machine_name
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Subject title for administrative tasks (alias of machine_name)'
        AFTER `machine_name`;

-- ---------------------------------------------------------------
-- ISSUE 6: notifications table has no `user_role` column
-- mgr_ajax.php line 79:
--   INSERT INTO notifications (user_role, message, type) VALUES (...)
-- The table only has user_id — this INSERT would fail silently.
-- Fix: Add user_role column; make user_id nullable for role-based notifs
-- ---------------------------------------------------------------
ALTER TABLE `notifications`
    ADD COLUMN IF NOT EXISTS `user_role` VARCHAR(50) NULL DEFAULT NULL
        COMMENT 'Role-based target (alternative to user_id)'
        AFTER `user_id`;

ALTER TABLE `notifications`
    MODIFY COLUMN `user_id` INT NULL DEFAULT NULL;

-- ---------------------------------------------------------------
-- ISSUE 7: audit_logs has TWO timestamp columns doing the same thing
-- `created_at` and `timestamp` are both DEFAULT current_timestamp()
-- Fix: Keep `timestamp` (used by audit_logs.php), alias created_at
-- No data change needed — just document. Drop created_at if desired:
-- ALTER TABLE `audit_logs` DROP COLUMN `created_at`;
-- (Commented out — safe to run manually after verifying no PHP uses it)
-- ---------------------------------------------------------------

-- ---------------------------------------------------------------
-- ISSUE 8: Missing Engineering Manager user
-- user_id 16 (SENTAYEHU ESHETU) at dept_id 16 (Engineering) has
-- role 'Department Manager' — but the PHP checks for 'Engineering Manager'
-- Fix: Update this user's role to 'Engineering Manager'
-- ---------------------------------------------------------------
UPDATE `users`
SET `user_role` = 'Engineering Manager'
WHERE `id` = 16 AND `dept_id` = 16;
-- (dept_id 16 = Engineering department — safe to update)

-- ---------------------------------------------------------------
-- ISSUE 9: Missing indexes for the new cross-dept columns
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    ADD INDEX IF NOT EXISTS `idx_sender_dept`   (`sender_dept_id`),
    ADD INDEX IF NOT EXISTS `idx_receiver_dept` (`receiver_dept_id`),
    ADD INDEX IF NOT EXISTS `idx_request_type`  (`request_type`),
    ADD INDEX IF NOT EXISTS `idx_status`        (`status`);

-- ---------------------------------------------------------------
-- ISSUE 10: maintenance_requests FK on dept_id is missing
-- dept_id references departments but has no FK constraint
-- ---------------------------------------------------------------
ALTER TABLE `maintenance_requests`
    ADD CONSTRAINT IF NOT EXISTS `fk_mr_dept`
        FOREIGN KEY (`dept_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT IF NOT EXISTS `fk_mr_sender_dept`
        FOREIGN KEY (`sender_dept_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT IF NOT EXISTS `fk_mr_receiver_dept`
        FOREIGN KEY (`receiver_dept_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT IF NOT EXISTS `fk_mr_assigned_to`
        FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- ---------------------------------------------------------------
-- FROM update_schema.sql: dept_type column
-- departments table already has dept_type (VARCHAR) in the live DB.
-- This ensures the column exists; no-op if already present.
-- ---------------------------------------------------------------
ALTER TABLE `departments`
    ADD COLUMN IF NOT EXISTS `dept_type` VARCHAR(50) NOT NULL DEFAULT 'Support';

-- ---------------------------------------------------------------
-- FROM update_schema.sql: user_role column
-- The original update_schema.sql locked user_role into a hard ENUM
-- which BREAKS 'Engineering Manager', 'Deputy General Manager', etc.
-- Fix: keep user_role as VARCHAR(50) (already correct in live DB).
-- Do NOT run the ENUM ALTER from update_schema.sql.
-- Ensure all roles used in the system are documented here:
--
--   General Manager | Deputy General Manager | Engineering Manager
--   Department Manager | Shift Leader | Supervisor
--   Technician | Officer | Clerk | Employee | Admin
-- ---------------------------------------------------------------
-- (No ALTER needed — VARCHAR(50) is already the correct column type)

-- ---------------------------------------------------------------
-- FROM update_schema.sql: seed departments that may be missing
-- Uses INSERT IGNORE so existing rows are never touched.
-- ---------------------------------------------------------------
INSERT IGNORE INTO `departments` (`dept_name`, `dept_type`) VALUES
('Spinning Department',   'Production'),
('Weaving Department',    'Production'),
('Processing Department', 'Production'),
('Garment Department',    'Production'),
('Engineering',           'Support'),
('Human Resource (HR)',   'Admin');

-- ---------------------------------------------------------------
-- Verification queries — run these after to confirm success
-- ---------------------------------------------------------------
SELECT 'maintenance_requests columns:' AS check_point;
SHOW COLUMNS FROM `maintenance_requests`;

SELECT 'Users with Engineering Manager role:' AS check_point;
SELECT id, full_name, user_role, dept_id FROM `users` WHERE user_role = 'Engineering Manager';

SELECT 'notifications table columns:' AS check_point;
SHOW COLUMNS FROM `notifications`;

SELECT 'departments list:' AS check_point;
SELECT id, dept_name, dept_type FROM `departments` ORDER BY dept_type, dept_name;

COMMIT;
