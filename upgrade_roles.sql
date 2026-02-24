-- ============================================================
-- LANBRIDGE COLLEGE KPI — upgrade_roles.sql
-- Run this on your existing lanbridge_kpi database
-- Adds new roles, departments, and restructures hierarchy
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Step 1: Restructure existing role levels ──────────────────
-- CEO stays at 1
-- Principal becomes 2 (new)
-- VP moves from 2 → 3
-- Dept Head moves from 3 → 6
-- Staff moves from 4 → 7

UPDATE roles SET level = 3 WHERE slug = 'vice_principal';
UPDATE roles SET level = 6 WHERE slug = 'dept_head';
UPDATE roles SET level = 7 WHERE slug = 'staff';

-- ── Step 2: Insert new roles ──────────────────────────────────
INSERT INTO roles (name, slug, level, description) VALUES
('Principal',                'principal',         2, 'Principal — College-wide oversight, reports to CEO'),
('Corporate Affairs Manager','corp_affairs',       4, 'Manages corporate affairs, partnerships, and external relations'),
('Admissions Officer',       'admissions_officer', 4, 'Manages student admissions and enrollment'),
('Student Affairs Officer',  'student_affairs',    5, 'Manages student welfare, activities, and support services'),
('Marketing Officer',        'marketing_officer',  4, 'Manages marketing, communications, and brand');

-- ── Step 3: Add new departments ──────────────────────────────
INSERT INTO departments (name, code, description) VALUES
('Corporate Affairs', 'CORP',  'Corporate partnerships, external relations, and stakeholder engagement'),
('Admissions',        'ADM',   'Student admissions, enrollment management, and prospective student support'),
('Marketing',         'MKT',   'Marketing, communications, brand management, and public relations');

-- ── Step 4: Add demo users for new roles ─────────────────────
-- Password will be fixed by debug.php (Admin@1234)
INSERT INTO users (employee_id, first_name, last_name, email, password_hash, role_id, department_id, position, join_date, is_active, force_password_change) VALUES
('LC-006', 'The',    'Principal',  'principal@lanbridgecollegezambia.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM roles WHERE slug='principal'), 1, 'Principal', '2020-01-01', 1, 0),

('LC-007', 'Corporate', 'Manager', 'corporate@lanbridgecollegezambia.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM roles WHERE slug='corp_affairs'),
  (SELECT id FROM departments WHERE code='CORP'), 'Corporate Affairs Manager', '2021-01-01', 1, 0),

('LC-008', 'Admissions', 'Officer', 'admissions@lanbridgecollegezambia.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM roles WHERE slug='admissions_officer'),
  (SELECT id FROM departments WHERE code='ADM'), 'Admissions Officer', '2021-01-01', 1, 0),

('LC-009', 'Student', 'Affairs', 'studentaffairs@lanbridgecollegezambia.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM roles WHERE slug='student_affairs'),
  (SELECT id FROM departments WHERE code='STU'), 'Student Affairs Officer', '2021-01-01', 1, 0),

('LC-010', 'Marketing', 'Officer', 'marketing@lanbridgecollegezambia.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  (SELECT id FROM roles WHERE slug='marketing_officer'),
  (SELECT id FROM departments WHERE code='MKT'), 'Marketing Officer', '2021-01-01', 1, 0);

-- ── Step 5: Add KPI categories for new departments ────────────
INSERT INTO kpi_categories (name, description, department_id, is_global, max_score, weight) VALUES
('Corporate Partnerships',    'Managing partnerships, MOUs, and stakeholder relations',
  (SELECT id FROM departments WHERE code='CORP'), 0, 100, 1.2),
('External Communications',   'Press releases, public statements, corporate correspondence',
  (SELECT id FROM departments WHERE code='CORP'), 0, 100, 1.0),
('Student Admissions',        'Processing applications, interviews, enrollment confirmation',
  (SELECT id FROM departments WHERE code='ADM'),  0, 100, 1.3),
('Prospective Student Outreach','School visits, open days, prospectus distribution',
  (SELECT id FROM departments WHERE code='ADM'),  0, 100, 1.0),
('Marketing Campaigns',       'Campaign planning, execution, and performance tracking',
  (SELECT id FROM departments WHERE code='MKT'),  0, 100, 1.2),
('Digital & Social Media',    'Social media management, website updates, online presence',
  (SELECT id FROM departments WHERE code='MKT'),  0, 100, 1.0),
('Student Welfare Programs',  'Counseling sessions, welfare activities, student support',
  (SELECT id FROM departments WHERE code='STU'),  0, 100, 1.0);

SET FOREIGN_KEY_CHECKS = 1;

-- ── Verify ────────────────────────────────────────────────────
SELECT id, name, slug, level FROM roles ORDER BY level;
SELECT id, name, code FROM departments ORDER BY name;
