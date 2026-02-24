-- ============================================================
-- LANBRIDGE COLLEGE KPI SYSTEM — DATABASE
-- Version 2.0
-- Run this on a fresh database called: lanbridge_kpi
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- TABLE: roles
-- ============================================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `level` INT NOT NULL COMMENT '1=CEO, 2=VP, 3=DeptHead, 4=Staff',
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `slug`, `level`, `description`) VALUES
('CEO',             'ceo',            1, 'Chief Executive Officer — Full system access'),
('Vice Principal',  'vice_principal', 2, 'Vice Principal — Academic monitoring and approvals'),
('Department Head', 'dept_head',      3, 'Department Head — Own department monitoring'),
('Staff Member',    'staff',          4, 'Staff Member — Daily reporting only');

-- ============================================================
-- TABLE: departments
-- ============================================================
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`name`, `code`, `description`) VALUES
('Academic Affairs',        'ACAD',  'Handles all academic programs, curriculum, and faculty coordination'),
('Administration',          'ADMIN', 'Manages administrative operations and institutional support'),
('Finance',                 'FIN',   'Financial management, budgeting, and accounting'),
('Information Technology',  'IT',    'IT infrastructure, systems, and digital services'),
('Student Affairs',         'STU',   'Student welfare, activities, and support services'),
('Library',                 'LIB',   'Library resources, research support, and information services');

-- ============================================================
-- TABLE: users
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(30) NOT NULL UNIQUE,
    `first_name` VARCHAR(80) NOT NULL,
    `last_name` VARCHAR(80) NOT NULL,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `department_id` INT DEFAULT NULL,
    `supervisor_id` INT DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `position` VARCHAR(100) DEFAULT NULL,
    `join_date` DATE DEFAULT NULL,
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
    `password_reset_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_expires` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `last_login_attempt` DATETIME DEFAULT NULL,
    `locked_until` DATETIME DEFAULT NULL,
    `force_password_change` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default users (password: Admin@1234)
-- Hash generated with: password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12])
INSERT INTO `users` (`employee_id`, `first_name`, `last_name`, `email`, `password_hash`, `role_id`, `department_id`, `position`, `join_date`, `is_active`, `force_password_change`) VALUES
('LC-001', 'Chief',    'Executive',  'ceo@lanbridgecollegezambia.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 'Chief Executive Officer', '2020-01-01', 1, 0),
('LC-002', 'Vice',     'Principal',  'vp@lanbridgecollegezambia.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 'Vice Principal',          '2020-01-01', 1, 0),
('LC-003', 'IT',       'Head',       'it@lanbridgecollegezambia.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 4, 'IT Department Head',      '2020-01-01', 1, 0),
('LC-004', 'Jane',     'Mwanza',     'jane@lanbridgecollegezambia.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1, 'Lecturer',                '2021-03-15', 1, 0),
('LC-005', 'Moses',    'Banda',      'moses@lanbridgecollegezambia.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',4, 4, 'IT Technician',          '2021-06-01', 1, 0);

-- ============================================================
-- TABLE: kpi_categories
-- ============================================================
DROP TABLE IF EXISTS `kpi_categories`;
CREATE TABLE `kpi_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `department_id` INT DEFAULT NULL,
    `is_global` TINYINT(1) DEFAULT 0,
    `max_score` DECIMAL(5,2) DEFAULT 100.00,
    `weight` DECIMAL(5,2) DEFAULT 1.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `kpi_categories` (`name`, `description`, `department_id`, `is_global`, `max_score`, `weight`) VALUES
('Teaching & Instruction',      'Lesson delivery, student engagement, curriculum coverage',  1, 0, 100, 1.5),
('Student Assessment',          'Marking, grading, feedback to students',                   1, 0, 100, 1.2),
('Curriculum Development',      'Lesson planning, curriculum updates, resource creation',   1, 0, 100, 1.0),
('Administrative Tasks',        'Filing, reporting, meetings, correspondence',               NULL, 1, 100, 0.8),
('IT Support & Maintenance',    'System maintenance, user support, infrastructure',         4, 0, 100, 1.0),
('Student Affairs Activities',  'Student counseling, events, welfare activities',           5, 0, 100, 1.0),
('Library Services',            'Cataloging, research assistance, resource management',     6, 0, 100, 1.0),
('Professional Development',    'Training, workshops, seminars attended or delivered',      NULL, 1, 100, 0.7),
('Community & Outreach',        'Community engagement, external partnerships',              NULL, 1, 100, 0.6),
('Financial Operations',        'Budget processing, financial records, reconciliation',     3, 0, 100, 1.0);

-- ============================================================
-- TABLE: reports (daily reports)
-- ============================================================
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `report_date` DATE NOT NULL,
    `tasks_completed` TEXT NOT NULL,
    `key_metrics` VARCHAR(255) DEFAULT NULL,
    `challenges` TEXT DEFAULT NULL,
    `tomorrow_plan` TEXT DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `approval_comment` TEXT DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_daily_report` (`user_id`, `report_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample reports for demo
INSERT INTO `reports` (`user_id`, `report_date`, `tasks_completed`, `key_metrics`, `challenges`, `tomorrow_plan`, `status`, `approved_by`) VALUES
(4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Delivered 3 lectures on Introduction to Computing. Marked 40 student assignments. Updated lesson plan for next week.', 'Taught 3 lessons, marked 40 papers, 2 meetings attended', 'Projector malfunction in Room 4 caused delay', 'Continue marking, prepare Friday quiz', 'approved', 3),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Conducted student consultation sessions. Prepared exam questions for mid-term. Attended department meeting.', 'Consulted 12 students, 1 exam paper drafted, 1 meeting', 'None', 'Submit exam paper to HOD', 'pending', NULL),
(5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Resolved 5 network issues. Updated antivirus on 20 workstations. Configured new printer in admin block.', 'Resolved 5 tickets, updated 20 machines, 1 printer configured', 'One server had connectivity issues, escalated to vendor', 'Follow up with vendor on server issue', 'approved', 3),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Installed software on 8 new computers. Trained 3 staff on new email system. Backed up server data.', 'Configured 8 PCs, trained 3 staff, 1 server backup', 'None significant', 'Complete email migration for remaining staff', 'pending', NULL);

-- ============================================================
-- TABLE: kpi_submissions
-- ============================================================
DROP TABLE IF EXISTS `kpi_submissions`;
CREATE TABLE `kpi_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `submission_date` DATE NOT NULL,
    `task_description` TEXT NOT NULL,
    `quantity_completed` DECIMAL(10,2) DEFAULT 0,
    `quality_score` DECIMAL(5,2) DEFAULT NULL,
    `time_spent_hours` DECIMAL(5,2) DEFAULT NULL,
    `supporting_notes` TEXT DEFAULT NULL,
    `status` ENUM('pending','approved','rejected','revision_requested') DEFAULT 'pending',
    `reviewed_by` INT DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `reviewer_notes` TEXT DEFAULT NULL,
    `is_locked` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_daily_submission` (`user_id`, `category_id`, `submission_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `kpi_categories`(`id`),
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample KPI submissions
INSERT INTO `kpi_submissions` (`user_id`, `category_id`, `submission_date`, `task_description`, `quantity_completed`, `quality_score`, `time_spent_hours`, `status`, `reviewed_by`, `reviewed_at`, `is_locked`) VALUES
(4, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Taught 3 sessions of Intro to Computing, ICT Fundamentals, and Database Systems.', 3, 88, 6, 'approved', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(4, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Marked 35 assignment scripts for ICT class. Provided written feedback on each.', 35, 90, 4, 'approved', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(4, 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Attended faculty meeting. Compiled weekly progress report. Filed student attendance.', 3, 80, 2.5, 'approved', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Delivered 2 lectures and conducted lab practical session.', 3, 85, 5, 'pending', NULL, NULL, 0),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Resolved 5 network support tickets. Replaced faulty switch in server room.', 5, 92, 7, 'approved', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(5, 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Updated IT assets register. Attended IT committee meeting. Drafted maintenance report.', 3, 78, 3, 'approved', 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Installed and configured 8 new workstations. Updated network documentation.', 8, 88, 6, 'pending', NULL, NULL, 0),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Taught Database Systems and Web Development. Administered quiz.', 2, 86, 4.5, 'pending', NULL, NULL, 0),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Performed server backup. Trained 3 staff on new email system.', 4, 91, 5, 'pending', NULL, NULL, 0);

-- ============================================================
-- TABLE: notifications
-- ============================================================
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','success','warning','danger') DEFAULT 'info',
    `is_read` TINYINT(1) DEFAULT 0,
    `link` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `link`) VALUES
(4, 'Report Approved', 'Your KPI report for Teaching & Instruction on 3 days ago has been approved. Great work!', 'success', 0, '/kpi/portals/my_submissions.php'),
(4, 'Report Approved', 'Your KPI report for Student Assessment has been approved by IT Head.', 'success', 0, '/kpi/portals/my_submissions.php'),
(4, 'Reminder', 'Don\'t forget to submit your daily KPI report before end of day.', 'warning', 1, '/kpi/portals/submit_kpi.php'),
(5, 'Report Approved', 'Your IT Support & Maintenance report has been approved. Keep it up!', 'success', 0, '/kpi/portals/my_submissions.php'),
(3, 'New Submission', 'Jane Mwanza has submitted a new daily report pending your review.', 'info', 0, '/kpi/portals/head_approvals.php'),
(3, 'New Submission', 'Moses Banda has submitted a new KPI report pending your review.', 'info', 0, '/kpi/portals/head_approvals.php'),
(1, 'System Ready', 'The Lanbridge College KPI System has been successfully deployed and is operational.', 'success', 0, NULL),
(2, 'Pending Reviews', 'There are 4 reports pending review in the system.', 'warning', 0, '/kpi/portals/vp_approvals.php');

-- ============================================================
-- TABLE: password_history
-- ============================================================
DROP TABLE IF EXISTS `password_history`;
CREATE TABLE `password_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: login_attempts
-- ============================================================
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(180) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `success` TINYINT(1) DEFAULT 0,
    `user_agent` TEXT,
    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_activity_log
-- ============================================================
DROP TABLE IF EXISTS `user_activity_log`;
CREATE TABLE `user_activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_sessions
-- ============================================================
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `session_token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: security_config
-- ============================================================
DROP TABLE IF EXISTS `security_config`;
CREATE TABLE `security_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `security_config` (`setting_key`, `setting_value`, `description`) VALUES
('password_min_length',    '8',   'Minimum password character length'),
('require_uppercase',      '1',   'Require at least one uppercase letter'),
('require_lowercase',      '1',   'Require at least one lowercase letter'),
('require_numbers',        '1',   'Require at least one number'),
('require_special',        '1',   'Require at least one special character'),
('password_expiry_days',   '90',  'Days before password expires'),
('password_history_count', '5',   'Number of previous passwords that cannot be reused'),
('max_login_attempts',     '5',   'Max failed logins before lockout'),
('lockout_duration_mins',  '30',  'Lockout duration in minutes'),
('session_timeout_mins',   '120', 'Session inactivity timeout in minutes');

-- ============================================================
-- TABLE: email_queue
-- ============================================================
DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE `email_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `to_email` VARCHAR(180) NOT NULL,
    `to_name` VARCHAR(150),
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('pending','sent','failed') DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: audit_log
-- ============================================================
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(80),
    `record_id` INT,
    `old_value` JSON,
    `new_value` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample audit entries
INSERT INTO `audit_log` (`user_id`, `action`, `table_name`, `record_id`, `ip_address`) VALUES
(1, 'LOGIN',           'users', 1, '127.0.0.1'),
(4, 'REPORT_SUBMIT',   'reports', 1, '127.0.0.1'),
(3, 'REPORT_APPROVED', 'reports', 1, '127.0.0.1'),
(5, 'REPORT_SUBMIT',   'kpi_submissions', 5, '127.0.0.1'),
(1, 'USER_CREATED',    'users', 4, '127.0.0.1');

-- ============================================================
-- TABLE: system_settings
-- ============================================================
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name',            'Lanbridge College KPI System', 'System name shown in header'),
('site_url',             'http://localhost/kpi',         'Base URL of the system'),
('timezone',             'Africa/Lusaka',                'System timezone'),
('report_deadline_hour', '17',                           'Hour (24h) by which daily reports must be submitted'),
('academic_year',        '2025',                         'Current academic year'),
('maintenance_mode',     '0',                            '1=maintenance mode on, 0=off');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DEFAULT LOGIN CREDENTIALS
-- ============================================================
-- ALL USERS — Password: Admin@1234
-- NOTE: The hash above '$2y$12$92IXUNpkjO0rOQ5byMi...' is the
--       standard test hash for 'password' (Laravel default).
--       You MUST regenerate real hashes. Run this in PHP:
--       echo password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost'=>12]);
--       Then UPDATE users SET password_hash='<new_hash>' WHERE id=1;
--
-- OR — After importing, visit: http://localhost/kpi/debug.php
-- That page will auto-fix all passwords to Admin@1234
-- ============================================================
