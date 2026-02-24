-- ============================================================
-- LANBRIDGE COLLEGE KPI — phase2_final.sql
-- Compatible with MySQL 5.7 and 8.0
-- Uses stored procedures to safely add columns without errors
-- ============================================================
-- HOW TO RUN:
--   phpMyAdmin → select lanbridge_kpi → SQL tab
--   Paste this ENTIRE file → click Go
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Helper: safely add a column if it does not exist ─────────
-- We use a procedure because ADD COLUMN IF NOT EXISTS
-- is only available in MySQL 8.0.3+. This works on all versions.

DROP PROCEDURE IF EXISTS add_col;

DELIMITER ;;
CREATE PROCEDURE add_col(
  IN tbl  VARCHAR(100),
  IN col  VARCHAR(100),
  IN def  TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name   = tbl
      AND column_name  = col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END;;
DELIMITER ;

-- ── Helper: safely create a table from a file ─────────────────
-- (We just use CREATE TABLE IF NOT EXISTS for genuinely new tables)

-- ════════════════════════════════════════════════════════════
-- SECTION 1: Patch EXISTING tables — add missing columns
-- ════════════════════════════════════════════════════════════

-- tasks
CALL add_col('tasks', 'completion_note', 'TEXT NULL AFTER completion_proof');

-- student_fees
CALL add_col('student_fees', 'balance', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER amount_paid');
CALL add_col('student_fees', 'updated_at', 'DATETIME NULL');
UPDATE student_fees SET balance = amount_due - amount_paid WHERE 1=1;

-- ai_insights
CALL add_col('ai_insights', 'reviewed_by', 'INT NULL');
CALL add_col('ai_insights', 'reviewed_at', 'DATETIME NULL');
CALL add_col('ai_insights', 'review_note', 'TEXT NULL');
CALL add_col('ai_insights', 'source_data', 'TEXT NULL');

-- notifications
CALL add_col('notifications', 'priority',      "ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium'");
CALL add_col('notifications', 'department_id', 'INT NULL');
CALL add_col('notifications', 'action_url',    'VARCHAR(500) NULL');
CALL add_col('notifications', 'expires_at',    'DATETIME NULL');

-- user_activity_log
CALL add_col('user_activity_log', 'department_id', 'INT NULL');
CALL add_col('user_activity_log', 'old_value',     'TEXT NULL');
CALL add_col('user_activity_log', 'new_value',     'TEXT NULL');
CALL add_col('user_activity_log', 'record_table',  'VARCHAR(100) NULL');
CALL add_col('user_activity_log', 'record_id',     'INT NULL');

-- it_tickets
CALL add_col('it_tickets', 'updated_at', 'DATETIME NULL');

-- ════════════════════════════════════════════════════════════
-- SECTION 2: Widen tasks status ENUM
-- Safe: existing rows keep their values, we only ADD new values
-- ════════════════════════════════════════════════════════════

ALTER TABLE tasks
  MODIFY COLUMN status ENUM(
    'open','in_progress','pending_approval','completed','cancelled',
    'pending','overdue'
  ) NOT NULL DEFAULT 'open';

-- ════════════════════════════════════════════════════════════
-- SECTION 3: CREATE genuinely NEW tables
-- These did NOT exist in v2_expansion.sql
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS performance_scorecards (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  user_id           INT NOT NULL,
  department_id     INT NULL,
  period            VARCHAR(10) NOT NULL,
  report_score      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  kpi_score         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  approval_rate     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  consistency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  overall_score     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  rank_in_dept      INT NULL,
  rank_overall      INT NULL,
  generated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sc_user_period (user_id, period),
  FOREIGN KEY (user_id)       REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kpi_targets (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  department_id        INT NOT NULL,
  period               VARCHAR(10) NOT NULL,
  target_reports       INT NOT NULL DEFAULT 0,
  target_kpis          INT NOT NULL DEFAULT 0,
  target_approval_rate DECIMAL(5,2) NOT NULL DEFAULT 80.00,
  set_by               INT NOT NULL,
  created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kt_dept_period (department_id, period),
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (set_by)        REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fraud_flags (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  flag_type     VARCHAR(100) NOT NULL,
  entity_type   VARCHAR(50)  NOT NULL,
  entity_id     INT NOT NULL,
  user_id       INT NULL,
  department_id INT NULL,
  description   TEXT NOT NULL,
  severity      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  is_resolved   TINYINT(1) NOT NULL DEFAULT 0,
  resolved_by   INT NULL,
  resolved_at   DATETIME NULL,
  resolution    TEXT NULL,
  ai_confidence TINYINT NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (resolved_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ════════════════════════════════════════════════════════════
-- SECTION 4: Roles, Departments, Settings
-- INSERT IGNORE silently skips rows that already exist
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO roles (name, slug, level, description) VALUES
  ('Principal',       'principal',       2, 'College Principal — institution-wide authority'),
  ('Finance Admin',   'finance_admin',   3, 'Full finance module access'),
  ('Finance Officer', 'finance_officer', 4, 'Handles transactions, invoicing, fee collection'),
  ('Bursar',          'bursar',          3, 'Oversees all financial operations'),
  ('Auditor',         'auditor',         3, 'Read-only access to financial and audit data'),
  ('IT Admin',        'it_admin',        3, 'Full IT module — assets, tickets, monitoring'),
  ('IT Officer',      'it_officer',      4, 'Handles tickets, asset assignments, user support');

INSERT IGNORE INTO departments (name, code, description) VALUES
  ('Finance & Accounts',     'FIN', 'Fee collection, payroll, budgeting and financial reporting'),
  ('Information Technology', 'IT',  'Systems, networks, helpdesk and digital infrastructure');

INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
  ('sla_critical_hours',     '2',     'SLA deadline hours for critical IT tickets'),
  ('sla_high_hours',         '8',     'SLA deadline hours for high priority tickets'),
  ('sla_medium_hours',       '24',    'SLA deadline hours for medium priority tickets'),
  ('sla_low_hours',          '72',    'SLA deadline hours for low priority tickets'),
  ('finance_fiscal_year',    '2025',  'Current fiscal year'),
  ('poll_interval_ms',       '30000', 'Real-time polling interval in milliseconds'),
  ('ai_insights_enabled',    '1',     'Enable AI-style pattern detection'),
  ('fraud_threshold_days',   '3',     'Flag users with identical reports for N days'),
  ('weekend_submissions',    '0',     'Block weekend submissions (0=block, 1=allow)'),
  ('scorecard_auto_generate','1',     'Auto-generate monthly performance scorecards');

-- ════════════════════════════════════════════════════════════
-- SECTION 5: Cleanup procedure (good practice)
-- ════════════════════════════════════════════════════════════

DROP PROCEDURE IF EXISTS add_col;

SET foreign_key_checks = 1;

-- ── Final confirmation ────────────────────────────────────────
SELECT 'phase2_final.sql complete — all done!' AS status;

SELECT
  (SELECT COUNT(*) FROM information_schema.tables
   WHERE table_schema = DATABASE()
     AND table_type = 'BASE TABLE')  AS total_tables,
  (SELECT COUNT(*) FROM roles)       AS total_roles,
  (SELECT COUNT(*) FROM departments) AS total_departments;
