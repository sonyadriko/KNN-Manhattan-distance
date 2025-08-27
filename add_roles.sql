-- Add role column to users table
ALTER TABLE `users` ADD COLUMN `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER `password`;

-- Update existing admin user to have admin role
UPDATE `users` SET `role` = 'admin' WHERE `username` = 'admin';

-- Create index on role for better performance
ALTER TABLE `users` ADD INDEX `idx_role` (`role`);