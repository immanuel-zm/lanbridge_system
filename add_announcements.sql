-- ============================================================
-- LANBRIDGE COLLEGE KPI — add_announcements.sql
-- Run this in phpMyAdmin on the lanbridge_kpi database
-- ============================================================

CREATE TABLE IF NOT EXISTS announcements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    posted_by       INT NOT NULL,
    title           VARCHAR(255) NOT NULL,
    body            TEXT NOT NULL,
    audience        ENUM('all','departments') NOT NULL DEFAULT 'all',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcement_departments (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id     INT NOT NULL,
    department_id       INT NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id)   REFERENCES departments(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_ann_dept (announcement_id, department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verify
SELECT 'announcements table created' AS status;
SELECT 'announcement_departments table created' AS status;
