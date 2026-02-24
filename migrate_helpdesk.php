<?php
/**
 * IT Help Desk — Database Migration
 * Upload to kpi/ root, run once in browser, then DELETE.
 * URL: http://localhost/kpi/migrate_helpdesk.php
 */

// ── Direct DB connection (no includes needed) ─────────────────
$host    = 'localhost';
$dbname  = 'lanbridge_kpi';
$user    = 'root';   // Change to your DB username on live server
$pass    = '';       // Change to your DB password on live server

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('<pre style="color:red;font-family:sans-serif;padding:20px;">
<strong>❌ Database connection failed</strong>
' . htmlspecialchars($e->getMessage()) . '

Fix: Edit migrate_helpdesk.php lines 9–12 and set the correct DB credentials.
</pre>');
}

$ok  = [];
$err = [];

function step(PDO $db, string $label, string $sql, array &$ok, array &$err): void {
    try {
        $db->exec($sql);
        $ok[] = $label;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // "Duplicate column" or "already exists" = already done, not an error
        if (strpos($msg, 'Duplicate column') !== false ||
            strpos($msg, 'already exists')   !== false ||
            $e->getCode() === '42S21') {
            $ok[] = $label . ' (already existed — skipped)';
        } else {
            $err[] = $label . ': ' . $msg;
        }
    }
}

// ═══════════════════════════════════════════════════
// 1. CREATE it_ticket_activity_log
// ═══════════════════════════════════════════════════
step($db, 'Create table: it_ticket_activity_log',
    "CREATE TABLE IF NOT EXISTS it_ticket_activity_log (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id    INT NOT NULL,
        action       VARCHAR(100) NOT NULL,
        performed_by INT NOT NULL,
        note         TEXT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id)    REFERENCES it_tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (performed_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
$ok, $err);

// ═══════════════════════════════════════════════════
// 2. ADD COLUMNS to it_tickets
// ═══════════════════════════════════════════════════
step($db, 'it_tickets → add column: attachment_path',
    "ALTER TABLE it_tickets ADD COLUMN attachment_path VARCHAR(500) NULL",
$ok, $err);

step($db, 'it_tickets → add column: escalated',
    "ALTER TABLE it_tickets ADD COLUMN escalated TINYINT(1) NOT NULL DEFAULT 0",
$ok, $err);

step($db, 'it_tickets → add column: escalation_note',
    "ALTER TABLE it_tickets ADD COLUMN escalation_note TEXT NULL",
$ok, $err);

step($db, 'it_tickets → add column: satisfaction_rating',
    "ALTER TABLE it_tickets ADD COLUMN satisfaction_rating TINYINT NULL",
$ok, $err);

step($db, 'it_tickets → add column: updated_at',
    "ALTER TABLE it_tickets ADD COLUMN updated_at DATETIME NULL",
$ok, $err);

// ═══════════════════════════════════════════════════
// 3. PATCH category ENUM to include 'system'
// ═══════════════════════════════════════════════════
step($db, "it_tickets → add 'system' to category ENUM",
    "ALTER TABLE it_tickets MODIFY COLUMN category
     ENUM('hardware','software','network','access','email','system','other')
     NOT NULL DEFAULT 'other'",
$ok, $err);

// ═══════════════════════════════════════════════════
// 4. ADD COLUMN to it_ticket_comments
// ═══════════════════════════════════════════════════
step($db, 'it_ticket_comments → add column: attachment_path',
    "ALTER TABLE it_ticket_comments ADD COLUMN attachment_path VARCHAR(500) NULL",
$ok, $err);

// ═══════════════════════════════════════════════════
// 5. CREATE uploads directory
// ═══════════════════════════════════════════════════
$uploadDir = __DIR__ . '/assets/uploads/helpdesk/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
    $ok[] = is_dir($uploadDir)
        ? 'Created directory: assets/uploads/helpdesk/'
        : 'WARNING: Could not create assets/uploads/helpdesk/ — create it manually via cPanel File Manager';
} else {
    $ok[] = 'Directory already exists: assets/uploads/helpdesk/';
}

// ═══════════════════════════════════════════════════
// 6. VERIFY everything looks right
// ═══════════════════════════════════════════════════
$verify = [];
$tables = ['it_tickets', 'it_ticket_comments', 'it_ticket_activity_log'];
foreach ($tables as $t) {
    $exists = $db->query("SHOW TABLES LIKE '$t'")->rowCount() > 0;
    $verify[] = ['label' => "Table: $t", 'pass' => $exists];
}

$colChecks = [
    ['it_tickets',             'attachment_path'],
    ['it_tickets',             'escalated'],
    ['it_tickets',             'escalation_note'],
    ['it_tickets',             'satisfaction_rating'],
    ['it_ticket_comments',     'attachment_path'],
    ['it_ticket_activity_log', 'ticket_id'],
    ['it_ticket_activity_log', 'action'],
];
foreach ($colChecks as [$t, $c]) {
    try {
        $db->query("SELECT $c FROM $t LIMIT 1");
        $verify[] = ['label' => "Column: $t.$c", 'pass' => true];
    } catch (PDOException $e) {
        $verify[] = ['label' => "Column: $t.$c", 'pass' => false];
    }
}

$allGood = empty($err) && !in_array(false, array_column($verify, 'pass'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>IT Help Desk Migration</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
       background: #0f1318; color: #e2e8f0; padding: 40px 20px; }
.wrap { max-width: 680px; margin: 0 auto; }
h1 { color: #c9a84c; font-size: 22px; margin-bottom: 6px; }
.sub { color: #9ba3b8; font-size: 13px; margin-bottom: 28px; }
h2 { font-size: 12px; font-weight: 700; letter-spacing: .08em;
     color: #9ba3b8; text-transform: uppercase; margin: 24px 0 10px; }
.item { padding: 8px 14px; margin: 4px 0; border-radius: 6px;
        font-size: 13px; display: flex; align-items: center; gap: 10px; }
.item.pass  { background: rgba(45,212,160,.08); }
.item.fail  { background: rgba(232,85,106,.12); color: #fc8181; }
.item.skip  { background: rgba(245,166,35,.06); color: #f5a623; }
.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot.pass  { background: #2dd4a0; }
.dot.fail  { background: #e8556a; }
.dot.skip  { background: #f5a623; }
.summary { margin-top: 24px; padding: 16px 20px; border-radius: 8px;
           font-size: 15px; font-weight: 700; }
.summary.ok  { background: rgba(45,212,160,.1); border: 1px solid rgba(45,212,160,.25); color: #2dd4a0; }
.summary.bad { background: rgba(232,85,106,.1); border: 1px solid rgba(232,85,106,.25); color: #fc8181; }
.warn { margin-top: 24px; padding: 16px 20px; border-radius: 8px;
        background: rgba(232,85,106,.1); border: 1px solid rgba(232,85,106,.3);
        font-size: 13px; color: #fca5a5; line-height: 1.8; }
.warn strong { color: #e8556a; }
.steps { margin-top: 20px; background: #1a2035; border-radius: 8px;
         padding: 16px 20px; font-size: 13px; line-height: 2; color: #9ba3b8; }
.steps strong { color: #e2e8f0; }
.btn { display: inline-block; margin-top: 20px; padding: 11px 24px;
       background: #c9a84c; color: #000; border-radius: 6px;
       text-decoration: none; font-weight: 700; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>🎫 IT Help Desk — Database Migration</h1>
  <p class="sub">Run once, then delete this file from your server.</p>

  <h2>Migration Steps</h2>
  <?php foreach ($ok as $line):
    $skip = strpos($line,'already existed') !== false || strpos($line,'already exists') !== false;
  ?>
  <div class="item <?= $skip ? 'skip' : 'pass' ?>">
    <div class="dot <?= $skip ? 'skip' : 'pass' ?>"></div>
    <?= htmlspecialchars($line) ?>
  </div>
  <?php endforeach; ?>

  <?php if (!empty($err)): ?>
  <h2>Errors</h2>
  <?php foreach ($err as $line): ?>
  <div class="item fail"><div class="dot fail"></div><?= htmlspecialchars($line) ?></div>
  <?php endforeach; ?>
  <?php endif; ?>

  <h2>Verification</h2>
  <?php foreach ($verify as $v): ?>
  <div class="item <?= $v['pass'] ? 'pass' : 'fail' ?>">
    <div class="dot <?= $v['pass'] ? 'pass' : 'fail' ?>"></div>
    <?= htmlspecialchars($v['label']) ?> — <?= $v['pass'] ? 'OK' : 'MISSING' ?>
  </div>
  <?php endforeach; ?>

  <?php if ($allGood): ?>
  <div class="summary ok">✅ Migration complete — IT Help Desk database is ready!</div>
  <?php else: ?>
  <div class="summary bad">⚠ Some items failed — check errors above</div>
  <?php endif; ?>

  <div class="warn">
    <strong>⚠ IMPORTANT — Delete this file now:</strong><br>
    This file has no password protection. Delete it immediately after running.<br>
    File location: <code>C:\xampp\htdocs\kpi\migrate_helpdesk.php</code>
  </div>

  <div class="steps">
    <strong>Next steps after deleting this file:</strong><br>
    1. Upload <strong>header.php</strong> → <code>kpi/includes/header.php</code><br>
    2. Upload <strong>helpdesk.php</strong> → <code>kpi/portals/helpdesk.php</code><br>
    3. Upload <strong>it_tickets.php</strong> → <code>kpi/portals/it_tickets.php</code><br>
    4. Upload <strong>it_dashboard.php</strong> → <code>kpi/portals/it_dashboard.php</code><br>
    5. Every user will see <strong>IT Help Desk</strong> in their sidebar
  </div>

  <?php if ($allGood): ?>
  <a href="portals/helpdesk.php" class="btn">Open IT Help Desk →</a>
  <?php endif; ?>
</div>
</body>
</html>
