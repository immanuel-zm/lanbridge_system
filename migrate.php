<?php
// ============================================================
// migrate.php — Lanbridge College KPI System — Phase 2
// Upload to: kpi/migrate.php  then open in browser
// DELETE immediately after running successfully
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$db  = getDB();
$log = [];
$errs = [];

function q(PDO $db, string $label, string $sql, array &$log, array &$errs): void {
    try {
        $db->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        $errs[] = "❌ $label — " . $e->getMessage();
    }
}

function tableExists(PDO $db, string $t): bool {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $s->execute([$t]);
    return (int)$s->fetchColumn() > 0;
}

function colExists(PDO $db, string $t, string $c): bool {
    $s = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
    $s->execute([$t, $c]);
    return (int)$s->fetchColumn() > 0;
}

function addCol(PDO $db, string $t, string $c, string $def, array &$log, array &$errs): void {
    if (colExists($db, $t, $c)) { $log[] = "⏭ $t.$c already exists"; return; }
    q($db, "Add $t.$c", "ALTER TABLE `$t` ADD COLUMN `$c` $def", $log, $errs);
}

function createTable(PDO $db, string $name, string $sql, array &$log, array &$errs): void {
    if (tableExists($db, $name)) { $log[] = "⏭ Table $name already exists"; return; }
    q($db, "Create $name", $sql, $log, $errs);
}

// ════════════════════════════════════════════════
// STEP 1: CREATE all missing tables
// ════════════════════════════════════════════════

createTable($db, 'tasks', "
CREATE TABLE tasks (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  title              VARCHAR(255) NOT NULL,
  description        TEXT NOT NULL,
  requesting_user_id INT NOT NULL,
  requesting_dept_id INT NULL,
  assigned_dept_id   INT NULL,
  assigned_user_id   INT NULL,
  priority           ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  deadline           DATE NULL,
  status             ENUM('open','in_progress','pending_approval','completed','cancelled','pending','overdue') NOT NULL DEFAULT 'open',
  completion_proof   TEXT NULL,
  completion_note    TEXT NULL,
  completed_at       DATETIME NULL,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NULL,
  FOREIGN KEY (requesting_user_id) REFERENCES users(id),
  FOREIGN KEY (requesting_dept_id) REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_dept_id)   REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_user_id)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'task_comments', "
CREATE TABLE task_comments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  task_id    INT NOT NULL,
  user_id    INT NOT NULL,
  comment    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'student_fees', "
CREATE TABLE student_fees (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  student_name  VARCHAR(255) NOT NULL,
  student_id    VARCHAR(50)  NOT NULL,
  programme     VARCHAR(255) NULL,
  academic_year VARCHAR(20)  NOT NULL,
  semester      TINYINT      NOT NULL DEFAULT 1,
  fee_type      VARCHAR(100) NOT NULL,
  amount_due    DECIMAL(12,2) NOT NULL,
  amount_paid   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  balance       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  due_date      DATE NULL,
  status        ENUM('unpaid','partial','paid','waived','overdue') NOT NULL DEFAULT 'unpaid',
  created_by    INT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'transactions', "
CREATE TABLE transactions (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  reference_no     VARCHAR(60) UNIQUE NOT NULL,
  type             ENUM('income','expense','transfer','refund') NOT NULL DEFAULT 'income',
  category         VARCHAR(100) NOT NULL,
  description      TEXT NOT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  payment_method   VARCHAR(50) NULL,
  receipt_no       VARCHAR(100) NULL,
  student_fee_id   INT NULL,
  recorded_by      INT NOT NULL,
  approved_by      INT NULL,
  transaction_date DATE NOT NULL,
  status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reversal_reason  TEXT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recorded_by)    REFERENCES users(id),
  FOREIGN KEY (approved_by)    REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'departmental_budget', "
CREATE TABLE departmental_budget (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  department_id    INT NOT NULL,
  fiscal_year      VARCHAR(10) NOT NULL,
  allocated_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  approved_by      INT NULL,
  notes            TEXT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL,
  UNIQUE KEY uq_dept_year (department_id, fiscal_year),
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (approved_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'expenditure', "
CREATE TABLE expenditure (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  department_id    INT NOT NULL,
  fiscal_year      VARCHAR(10) NOT NULL,
  category         VARCHAR(100) NOT NULL,
  description      TEXT NOT NULL,
  amount           DECIMAL(12,2) NOT NULL,
  recorded_by      INT NOT NULL,
  approved_by      INT NULL,
  status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  expenditure_date DATE NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (recorded_by)   REFERENCES users(id),
  FOREIGN KEY (approved_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'procurement_requests', "
CREATE TABLE procurement_requests (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  request_no       VARCHAR(60) UNIQUE NOT NULL,
  requesting_dept  INT NOT NULL,
  requesting_user  INT NOT NULL,
  item_name        VARCHAR(255) NOT NULL,
  item_description TEXT NULL,
  quantity         DECIMAL(10,2) NOT NULL DEFAULT 1,
  unit             VARCHAR(50) NULL,
  estimated_cost   DECIMAL(12,2) NOT NULL,
  vendor_name      VARCHAR(255) NULL,
  vendor_contact   VARCHAR(255) NULL,
  urgency          ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  status           ENUM('draft','submitted','finance_review','approved','rejected','ordered','received') NOT NULL DEFAULT 'draft',
  finance_notes    TEXT NULL,
  approved_by      INT NULL,
  approved_at      DATETIME NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL,
  FOREIGN KEY (requesting_dept) REFERENCES departments(id),
  FOREIGN KEY (requesting_user) REFERENCES users(id),
  FOREIGN KEY (approved_by)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'payroll', "
CREATE TABLE payroll (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  pay_period     VARCHAR(20) NOT NULL,
  basic_salary   DECIMAL(12,2) NOT NULL,
  allowances     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  deductions     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  tax_amount     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  net_salary     DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(50) NULL,
  payment_ref    VARCHAR(100) NULL,
  status         ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft',
  paid_at        DATETIME NULL,
  prepared_by    INT NOT NULL,
  approved_by    INT NULL,
  notes          TEXT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payroll_user_period (user_id, pay_period),
  FOREIGN KEY (user_id)     REFERENCES users(id),
  FOREIGN KEY (prepared_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'financial_audit_logs', "
CREATE TABLE financial_audit_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  action      VARCHAR(100) NOT NULL,
  table_name  VARCHAR(100) NOT NULL,
  record_id   INT NULL,
  old_values  TEXT NULL,
  new_values  TEXT NULL,
  ip_address  VARCHAR(45) NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'it_tickets', "
CREATE TABLE it_tickets (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  ticket_no        VARCHAR(30) UNIQUE NOT NULL,
  submitted_by     INT NOT NULL,
  dept_id          INT NOT NULL,
  assigned_to      INT NULL,
  category         ENUM('hardware','software','network','access','email','system','other') NOT NULL DEFAULT 'other',
  priority         ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  subject          VARCHAR(255) NOT NULL,
  description      TEXT NOT NULL,
  status           ENUM('open','in_progress','pending_user','resolved','closed','cancelled') NOT NULL DEFAULT 'open',
  resolution_notes TEXT NULL,
  sla_deadline     DATETIME NULL,
  opened_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at      DATETIME NULL,
  closed_at        DATETIME NULL,
  updated_at       DATETIME NULL,
  FOREIGN KEY (submitted_by) REFERENCES users(id),
  FOREIGN KEY (dept_id)      REFERENCES departments(id),
  FOREIGN KEY (assigned_to)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'it_ticket_comments', "
CREATE TABLE it_ticket_comments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id   INT NOT NULL,
  user_id     INT NOT NULL,
  comment     TEXT NOT NULL,
  is_internal TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES it_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)   REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'it_ticket_activity_log', "
CREATE TABLE it_ticket_activity_log (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id    INT NOT NULL,
  action       VARCHAR(100) NOT NULL,
  performed_by INT NOT NULL,
  note         TEXT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id)    REFERENCES it_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (performed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'it_assets', "
CREATE TABLE it_assets (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  asset_tag        VARCHAR(50) UNIQUE NOT NULL,
  asset_type       ENUM('laptop','desktop','printer','server','switch','projector','phone','tablet','other') NOT NULL DEFAULT 'other',
  make             VARCHAR(100) NULL,
  model            VARCHAR(100) NULL,
  serial_number    VARCHAR(100) NULL,
  purchase_date    DATE NULL,
  purchase_cost    DECIMAL(12,2) NULL,
  warranty_expiry  DATE NULL,
  assigned_to      INT NULL,
  department_id    INT NULL,
  location         VARCHAR(255) NULL,
  condition_status ENUM('new','good','fair','poor','decommissioned') NOT NULL DEFAULT 'good',
  notes            TEXT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NULL,
  FOREIGN KEY (assigned_to)   REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'activity_feed', "
CREATE TABLE activity_feed (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NULL,
  department_id INT NULL,
  action_type   VARCHAR(100) NOT NULL,
  description   VARCHAR(500) NOT NULL,
  icon          VARCHAR(30) NOT NULL DEFAULT 'activity',
  color         VARCHAR(20) NOT NULL DEFAULT 'info',
  link          VARCHAR(500) NULL,
  is_public     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'ai_insights', "
CREATE TABLE ai_insights (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  insight_type   VARCHAR(100) NOT NULL,
  department_id  INT NULL,
  user_id        INT NULL,
  title          VARCHAR(255) NOT NULL,
  description    TEXT NOT NULL,
  severity       ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
  confidence_pct TINYINT NOT NULL DEFAULT 0,
  is_reviewed    TINYINT(1) NOT NULL DEFAULT 0,
  reviewed_by    INT NULL,
  reviewed_at    DATETIME NULL,
  review_note    TEXT NULL,
  source_data    TEXT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by)   REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'announcements', "
CREATE TABLE announcements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  posted_by  INT NOT NULL,
  title      VARCHAR(255) NOT NULL,
  body       TEXT NOT NULL,
  audience   ENUM('all','departments') NOT NULL DEFAULT 'all',
  is_pinned  TINYINT(1) NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'announcement_departments', "
CREATE TABLE announcement_departments (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT NOT NULL,
  department_id   INT NOT NULL,
  FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
  FOREIGN KEY (department_id)   REFERENCES departments(id)   ON DELETE CASCADE,
  UNIQUE KEY uq_ann_dept (announcement_id, department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'performance_scorecards', "
CREATE TABLE performance_scorecards (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'kpi_targets', "
CREATE TABLE kpi_targets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

createTable($db, 'fraud_flags', "
CREATE TABLE fraud_flags (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", $log, $errs);

// ════════════════════════════════════════════════
// STEP 2: Patch columns on existing tables
// ════════════════════════════════════════════════

addCol($db, 'tasks',              'completion_note', 'TEXT NULL',          $log, $errs);
addCol($db, 'student_fees',       'balance',         'DECIMAL(12,2) NOT NULL DEFAULT 0.00', $log, $errs);
addCol($db, 'student_fees',       'updated_at',      'DATETIME NULL',      $log, $errs);
addCol($db, 'ai_insights',        'reviewed_by',     'INT NULL',           $log, $errs);
addCol($db, 'ai_insights',        'reviewed_at',     'DATETIME NULL',      $log, $errs);
addCol($db, 'ai_insights',        'review_note',     'TEXT NULL',          $log, $errs);
addCol($db, 'ai_insights',        'source_data',     'TEXT NULL',          $log, $errs);
addCol($db, 'notifications',      'priority',        "ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium'", $log, $errs);
addCol($db, 'notifications',      'department_id',   'INT NULL',           $log, $errs);
addCol($db, 'notifications',      'action_url',      'VARCHAR(500) NULL',  $log, $errs);
addCol($db, 'notifications',      'expires_at',      'DATETIME NULL',      $log, $errs);
addCol($db, 'user_activity_log',  'department_id',   'INT NULL',           $log, $errs);
addCol($db, 'user_activity_log',  'old_value',       'TEXT NULL',          $log, $errs);
addCol($db, 'user_activity_log',  'new_value',       'TEXT NULL',          $log, $errs);
addCol($db, 'user_activity_log',  'record_table',    'VARCHAR(100) NULL',  $log, $errs);
addCol($db, 'user_activity_log',  'record_id',       'INT NULL',           $log, $errs);
addCol($db, 'it_tickets',         'updated_at',         'DATETIME NULL',                         $log, $errs);
addCol($db, 'it_tickets',         'attachment_path',    'VARCHAR(500) NULL',                     $log, $errs);
addCol($db, 'it_tickets',         'escalated',          'TINYINT(1) NOT NULL DEFAULT 0',         $log, $errs);
addCol($db, 'it_tickets',         'escalation_note',    'TEXT NULL',                             $log, $errs);
addCol($db, 'it_tickets',         'satisfaction_rating','TINYINT NULL',                          $log, $errs);
addCol($db, 'it_ticket_comments', 'attachment_path',    'VARCHAR(500) NULL',                     $log, $errs);

// Recalculate balance
q($db, 'Recalculate student_fees.balance',
  "UPDATE student_fees SET balance = amount_due - amount_paid", $log, $errs);

// ════════════════════════════════════════════════
// STEP 3: Roles, Departments, Settings
// ════════════════════════════════════════════════

$roleStmt = $db->prepare("INSERT IGNORE INTO roles (name, slug, level, description) VALUES (?,?,?,?)");
foreach ([
    ['Principal',       'principal',       2, 'College Principal'],
    ['Finance Admin',   'finance_admin',   3, 'Full finance module access'],
    ['Finance Officer', 'finance_officer', 4, 'Handles transactions and fees'],
    ['Bursar',          'bursar',          3, 'Oversees all financial operations'],
    ['Auditor',         'auditor',         3, 'Read-only financial access'],
    ['IT Admin',        'it_admin',        3, 'Full IT module access'],
    ['IT Officer',      'it_officer',      4, 'Handles tickets and assets'],
] as $r) { $roleStmt->execute($r); }
$log[] = "✅ Roles inserted/verified";

$deptStmt = $db->prepare("INSERT IGNORE INTO departments (name, code, description) VALUES (?,?,?)");
foreach ([
    ['Finance & Accounts',     'FIN', 'Fee collection, payroll, budgeting'],
    ['Information Technology', 'IT',  'Systems, networks, helpdesk'],
] as $d) { $deptStmt->execute($d); }
$log[] = "✅ Departments inserted/verified";

$setStmt = $db->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?,?,?)");
foreach ([
    ['sla_critical_hours',     '2',     'SLA hours for critical tickets'],
    ['sla_high_hours',         '8',     'SLA hours for high priority tickets'],
    ['sla_medium_hours',       '24',    'SLA hours for medium tickets'],
    ['sla_low_hours',          '72',    'SLA hours for low priority tickets'],
    ['finance_fiscal_year',    '2025',  'Current fiscal year'],
    ['poll_interval_ms',       '30000', 'Polling interval ms'],
    ['ai_insights_enabled',    '1',     'Enable AI pattern detection'],
    ['fraud_threshold_days',   '3',     'Flag identical reports after N days'],
    ['weekend_submissions',    '0',     'Block weekend submissions'],
    ['scorecard_auto_generate','1',     'Auto-generate monthly scorecards'],
] as $s) { $setStmt->execute($s); }
$log[] = "✅ System settings inserted/verified";

// ════════════════════════════════════════════════
// Final counts
// ════════════════════════════════════════════════
$totalTables = (int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
$totalRoles  = (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
$totalDepts  = (int)$db->query("SELECT COUNT(*) FROM departments")->fetchColumn();

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lanbridge — Phase 2 Migration</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:monospace;background:#0d0f14;color:#e8eaf0;padding:40px 24px}
  h1{color:#c9a84c;font-size:22px;margin-bottom:6px}
  .sub{color:#9ba3b8;font-size:13px;margin-bottom:28px}
  .stats{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap}
  .stat{background:#141720;border:1px solid #2a2f45;border-radius:10px;padding:16px 24px;text-align:center}
  .stat-num{font-size:28px;font-weight:800;color:#c9a84c}
  .stat-label{font-size:12px;color:#9ba3b8;margin-top:4px}
  .section{background:#141720;border:1px solid #2a2f45;border-radius:10px;padding:20px;margin-bottom:20px}
  .section h2{font-size:14px;margin-bottom:14px;color:#c9a84c}
  .line{font-size:13px;padding:4px 0;border-bottom:1px solid #1a1e2e;line-height:1.6}
  .ok{background:#141720;border:1px solid rgba(45,212,160,.4);border-radius:10px;padding:16px 20px;margin-bottom:20px;color:#2dd4a0;font-size:15px;font-weight:bold}
  .err-box{background:rgba(232,85,106,.08);border:1px solid rgba(232,85,106,.4);border-radius:10px;padding:20px;margin-bottom:20px}
  .err-box h2{color:#e8556a;font-size:14px;margin-bottom:12px}
  .err-line{color:#e8556a;font-size:13px;padding:3px 0}
  .del{background:rgba(232,85,106,.15);border:2px solid #e8556a;border-radius:10px;padding:20px;margin-top:24px;color:#e8556a;font-size:14px;line-height:1.8}
</style>
</head>
<body>
<h1>🔧 Lanbridge KPI — Phase 2 Migration</h1>
<p class="sub">Run at: <?= date('Y-m-d H:i:s') ?></p>

<div class="stats">
  <div class="stat"><div class="stat-num"><?= $totalTables ?></div><div class="stat-label">Total Tables</div></div>
  <div class="stat"><div class="stat-num"><?= $totalRoles ?></div><div class="stat-label">Roles</div></div>
  <div class="stat"><div class="stat-num"><?= $totalDepts ?></div><div class="stat-label">Departments</div></div>
  <div class="stat"><div class="stat-num" style="color:<?= empty($errs) ? '#2dd4a0' : '#e8556a' ?>"><?= count($errs) ?></div><div class="stat-label">Errors</div></div>
</div>

<?php if (empty($errs)): ?>
<div class="ok">✅ Migration completed with zero errors! All tables created and patched successfully.</div>
<?php else: ?>
<div class="err-box">
  <h2>❌ <?= count($errs) ?> Error(s)</h2>
  <?php foreach ($errs as $e): ?><div class="err-line"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="section">
  <h2>Migration Log — <?= count($log) ?> steps</h2>
  <?php foreach ($log as $line): ?><div class="line"><?= htmlspecialchars($line) ?></div><?php endforeach; ?>
</div>

<div class="del">
  ⚠️ <strong>DELETE THIS FILE NOW</strong><br>
  This file has no authentication and must be deleted after use.<br>
  Delete <code>kpi/migrate.php</code> from your server via cPanel or FTP.
</div>
</body>
</html>
