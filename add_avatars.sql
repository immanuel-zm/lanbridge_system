-- ============================================================
-- LANBRIDGE COLLEGE KPI — add_avatars.sql
-- Run this in phpMyAdmin on the lanbridge_kpi database
-- ============================================================

-- Add avatar column to users table
ALTER TABLE users 
ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL 
AFTER position;

-- Verify
SELECT id, first_name, last_name, avatar FROM users LIMIT 5;
