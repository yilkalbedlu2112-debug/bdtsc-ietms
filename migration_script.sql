

USE bdtsc_db;


ALTER TABLE `maintenance_requests`
    MODIFY COLUMN `priority` ENUM('Normal','Low','Medium','High','Urgent','Emergency') NOT NULL DEFAULT 'Normal',
    MODIFY COLUMN `status` ENUM('Pending','Pending Approval','Approved','Rejected','Assigned','In Progress','Completed') NOT NULL DEFAULT 'Pending',
    MODIFY COLUMN `task_type` VARCHAR(50) NOT NULL DEFAULT 'Maintenance';


CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL DEFAULT NULL,
    `user_role` VARCHAR(50) NULL DEFAULT NULL,
    `dept_id` INT(11) NULL DEFAULT NULL,
    `role_target` VARCHAR(50) NULL DEFAULT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL DEFAULT NULL,
    `type` VARCHAR(50) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `dept_id` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------
-- 5. Create feedback_logs table (missing from schema)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `dept_id` INT(11) NOT NULL,
    `task_id` INT(11) NULL DEFAULT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('Pending','Reviewed','Resolved') NOT NULL DEFAULT 'Pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `dept_id` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------
-- 6. Create daily_reports table (missing from schema)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `dept_id` INT(11) NOT NULL,
    `created_by` INT(11) NOT NULL,
    `shift_type` VARCHAR(20) NOT NULL,
    `report_summary` TEXT NOT NULL,
    `total_tasks` INT(11) NOT NULL DEFAULT 0,
    `completed_tasks` INT(11) NOT NULL DEFAULT 0,
    `blocked_tasks` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `dept_id` (`dept_id`),
    KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


UPDATE `maintenance_requests`
SET `sender_dept_id` = `dept_id`
WHERE `sender_dept_id` IS NULL;

UPDATE `maintenance_requests`
SET `receiver_dept_id` = 16  -- Engineering dept
WHERE `receiver_dept_id` IS NULL AND `task_type` = 'Maintenance';

-- ---------------------------------------------------------------
-- 9. Update default users for testing
-- Ensure Admin and Manager roles exist
-- ---------------------------------------------------------------
-- Update General Manager
UPDATE `users`
SET `user_role` = 'General Manager', `status` = 'Active'
WHERE `email` = 'yilkalbedlu1993@gmail.com';

-- Update Deputy General Manager
UPDATE `users`
SET `user_role` = 'Deputy General Manager', `status` = 'Active'
WHERE `email` = 'yilkalbedlu2112@gmail.com';

-- Update Engineering Manager
UPDATE `users`
SET `user_role` = 'Engineering Manager', `status` = 'Active'
WHERE `email` = 'beyenege845@gmail.com';

-- Insert default Admin if not exists
INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `user_role`, `status`)
VALUES ('System Admin', 'admin@bdtsc.et', '$2y$10$hashedpassword', 'Admin', 'Active');

-- Insert default Department Manager if not exists
INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `user_role`, `dept_id`, `status`)
VALUES ('Test Manager', 'manager@bdtsc.et', '$2y$10$hashedpassword', 'Department Manager', 1, 'Active');


COMMIT;