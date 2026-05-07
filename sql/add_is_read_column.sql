-- Migration: Add is_read column to notifications table
-- Required for proper notification read/unread tracking across all roles

ALTER TABLE `notifications`
ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`;

-- Create index for faster unread notification queries
CREATE INDEX IF NOT EXISTS `idx_notif_user_read` ON `notifications` (`user_id`, `is_read`);
