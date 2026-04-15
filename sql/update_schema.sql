-- bdtsc_db update schema
-- Ensuring roles and dept types match the new use-case architecture

ALTER TABLE departments ADD COLUMN dept_type ENUM('Production', 'Support') NOT NULL DEFAULT 'Support';

-- Add standard predefined departments if they don't exist
INSERT IGNORE INTO departments (dept_name, dept_type) VALUES 
('Spinning', 'Production'),
('Weaving', 'Production'),
('Processing', 'Production'),
('Garment', 'Production'),
('Engineering', 'Support'),
('HR', 'Support');

-- Modify users table to handle the specific roles
ALTER TABLE users MODIFY COLUMN user_role ENUM(
    'General Manager',
    'Production and Technique Deputy General Manager',
    'Department Manager',
    'Shift Leader',
    'Supervisor',
    'Technician',
    'Employee',
    'Admin'
) NOT NULL DEFAULT 'Employee';

-- Add a default General Manager if not exists (Password: gm123)
-- Hash generated from password_hash('gm123', PASSWORD_BCRYPT)
INSERT IGNORE INTO users (full_name, email, password, user_role) 
VALUES ('Main GM', 'gm@bdtsc.com', '$2y$10$wE9qjK0O5tqgK8C3p5M/M.xZJ/D0b0wE0O0F8K5uR2T7O/3nQzEHu', 'General Manager');
