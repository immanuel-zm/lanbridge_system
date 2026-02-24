<?php
// ============================================================
// LANBRIDGE COLLEGE KPI — debug.php
// ⚠️  DELETE THIS FILE AFTER SETUP IS COMPLETE ⚠️
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error   = '';

// Fix all passwords to Admin@1234
if (isset($_POST['fix_passwords'])) {
    try {
        $db   = getDB();
        $hash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);
        $db->exec("UPDATE users SET password_hash = '$hash'");
        $message = '✅ All user passwords have been set to: <strong>Admin@1234</strong>';
    } catch (Exception $e) {
        $error = '❌ Error: ' . $e->getMessage();
    }
}

// Test DB connection
$dbStatus = '';
try {
    $db = getDB();
    $row = $db->query("SELECT COUNT(*) AS cnt FROM users")->fetch();
    $dbStatus = "✅ Connected to <strong>" . DB_NAME . "</strong> — {$row['cnt']} user(s) found";
} catch (Exception $e) {
    $dbStatus = "❌ Connection failed: " . $e->getMessage();
}

// Get all users
$users = [];
try {
    $db    = getDB();
    $users = $db->query(
        "SELECT u.employee_id, u.first_name, u.last_name, u.email, u.is_active,
                r.name AS role_name, d.name AS dept_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         LEFT JOIN departments d ON u.department_id = d.id
         ORDER BY r.level, u.first_name"
    )->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Debug — Lanbridge KPI</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #0d0f14; color: #f0ede8; padding: 40px 20px; }
  .container { max-width: 860px; margin: 0 auto; }
  h1 { color: #c9a84c; font-size: 24px; margin-bottom: 6px; }
  .warning { background: rgba(232,85,106,0.15); border: 1px solid rgba(232,85,106,0.4); border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; color: #e8556a; font-size: 14px; }
  .card { background: #1f2435; border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
  .card h2 { font-size: 15px; color: #9ba3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
  .status { font-size: 14px; padding: 12px 16px; border-radius: 8px; background: #141720; }
  .success { background: rgba(45,212,160,0.12); border: 1px solid rgba(45,212,160,0.3); color: #2dd4a0; }
  .danger  { background: rgba(232,85,106,0.12); border: 1px solid rgba(232,85,106,0.3); color: #e8556a; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th { text-align: left; padding: 10px 12px; color: #5c6478; text-transform: uppercase; font-size: 11px; border-bottom: 1px solid rgba(255,255,255,0.07); }
  td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
  tr:hover td { background: rgba(255,255,255,0.03); }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
  .badge-green { background: rgba(45,212,160,0.12); color: #2dd4a0; border: 1px solid rgba(45,212,160,0.3); }
  .badge-blue  { background: rgba(91,156,246,0.12); color: #5b9cf6; border: 1px solid rgba(91,156,246,0.3); }
  .badge-orange{ background: rgba(245,166,35,0.12); color: #f5a623; border: 1px solid rgba(245,166,35,0.3); }
  .badge-muted { background: rgba(255,255,255,0.07); color: #9ba3b8; border: 1px solid rgba(255,255,255,0.1); }
  button { background: #c9a84c; color: #0d0f14; border: none; padding: 11px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; }
  button:hover { background: #e0bb6a; }
  .btn-danger { background: rgba(232,85,106,0.15); color: #e8556a; border: 1px solid rgba(232,85,106,0.4); }
  .btn-danger:hover { background: rgba(232,85,106,0.25); }
  .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
  .msg.ok  { background: rgba(45,212,160,0.12); border:1px solid rgba(45,212,160,0.3); color:#2dd4a0; }
  .msg.err { background: rgba(232,85,106,0.12); border:1px solid rgba(232,85,106,0.3); color:#e8556a; }
  .links { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
  .link-btn { display: inline-block; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; background: rgba(201,168,76,0.12); color: #c9a84c; border: 1px solid rgba(201,168,76,0.3); }
  .link-btn:hover { background: rgba(201,168,76,0.22); }
</style>
</head>
<body>
<div class="container">
  <h1>⚙️ Lanbridge KPI — Setup Debug Tool</h1>
  <p style="color:#9ba3b8;margin-bottom:20px;font-size:14px;">Use this page to verify your installation. <strong style="color:#e8556a;">Delete debug.php after setup!</strong></p>

  <div class="warning">
    ⚠️ <strong>SECURITY WARNING:</strong> This file is for setup only. Delete <code>debug.php</code> from your server after you have confirmed the system is working.
  </div>

  <?php if ($message): ?>
    <div class="msg ok"><?= $message ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="msg err"><?= $error ?></div>
  <?php endif; ?>

  <!-- DB Status -->
  <div class="card">
    <h2>📡 Database Connection</h2>
    <div class="status <?= str_contains($dbStatus, '✅') ? 'success' : 'danger' ?>">
      <?= $dbStatus ?>
    </div>
    <p style="color:#5c6478;font-size:12px;margin-top:10px;">
      Host: <strong style="color:#9ba3b8;"><?= DB_HOST ?></strong> &nbsp;|&nbsp;
      Database: <strong style="color:#9ba3b8;"><?= DB_NAME ?></strong> &nbsp;|&nbsp;
      User: <strong style="color:#9ba3b8;"><?= DB_USER ?></strong> &nbsp;|&nbsp;
      Environment: <strong style="color:#c9a84c;"><?= IS_LOCAL ? 'LOCAL' : 'LIVE' ?></strong>
    </p>
  </div>

  <!-- Fix Passwords -->
  <div class="card">
    <h2>🔑 Fix Passwords</h2>
    <p style="color:#9ba3b8;font-size:14px;margin-bottom:16px;">
      If you cannot log in, click below to reset ALL user passwords to <code style="color:#c9a84c;">Admin@1234</code>
    </p>
    <form method="POST">
      <button type="submit" name="fix_passwords">🔧 Set All Passwords to Admin@1234</button>
    </form>
  </div>

  <!-- Users Table -->
  <div class="card">
    <h2>👥 User Accounts (<?= count($users) ?>)</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:#5c6478;"><?= sanitize($u['employee_id']) ?></td>
          <td><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></td>
          <td style="color:#9ba3b8;"><?= sanitize($u['email']) ?></td>
          <td>
            <span class="badge <?= match($u['role_name']) {
              'CEO' => 'badge-green',
              'Vice Principal' => 'badge-blue',
              'Department Head' => 'badge-orange',
              default => 'badge-muted'
            } ?>"><?= sanitize($u['role_name']) ?></span>
          </td>
          <td style="color:#9ba3b8;"><?= sanitize($u['dept_name'] ?? '—') ?></td>
          <td>
            <span class="badge <?= $u['is_active'] ? 'badge-green' : 'badge-muted' ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Quick Links -->
  <div class="card">
    <h2>🔗 Quick Links</h2>
    <div class="links">
      <a href="<?= SITE_URL ?>/login.php" class="link-btn">🔐 Login Page</a>
      <a href="<?= SITE_URL ?>/portals/ceo_dashboard.php" class="link-btn">📊 CEO Dashboard</a>
      <a href="<?= SITE_URL ?>/portals/staff_dashboard.php" class="link-btn">👤 Staff Dashboard</a>
    </div>
    <p style="color:#5c6478;font-size:12px;margin-top:16px;">
      Default credentials: <code style="color:#c9a84c;">ceo@lanbridgecollegezambia.com</code> / <code style="color:#c9a84c;">Admin@1234</code>
    </p>
  </div>

  <p style="color:#5c6478;font-size:12px;text-align:center;margin-top:20px;">
    PHP <?= PHP_VERSION ?> &nbsp;|&nbsp; <?= date('Y-m-d H:i:s') ?> &nbsp;|&nbsp; <?= IS_LOCAL ? 'localhost' : $_SERVER['SERVER_NAME'] ?>
  </p>
</div>
</body>
</html>