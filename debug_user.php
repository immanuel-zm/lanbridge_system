<?php
/**
 * debug_user.php  — TEMPORARY diagnostic & fix tool
 * Upload to kpi/ root, visit once, then DELETE it immediately.
 * NO login required — delete after use!
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$db     = getDB();
$email  = 'mandona@lanbridgecollege.com';
$action = $_POST['action'] ?? '';

// ── Actions ───────────────────────────────────────────────────
$message = '';

if ($action === 'reset_password') {
    $newPass = 'Lanbridge@2024';
    $hash    = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST ?? 12]);
    $db->prepare("UPDATE users SET password_hash=?, force_password_change=1, is_active=1, locked_until=NULL, login_attempts=0 WHERE email=?")
       ->execute([$hash, $email]);
    $message = "✅ Password reset to: <strong>$newPass</strong> — account also activated and unlocked.";
}

if ($action === 'fix_role') {
    $roleId = (int)($_POST['role_id'] ?? 0);
    if ($roleId) {
        $db->prepare("UPDATE users SET role_id=? WHERE email=?")->execute([$roleId, $email]);
        $message = "✅ Role updated.";
    }
}

// ── Fetch user data ───────────────────────────────────────────
$user = $db->query(
    "SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level,
            d.name AS dept_name
     FROM users u
     LEFT JOIN roles r ON u.role_id=r.id
     LEFT JOIN departments d ON u.department_id=d.id
     WHERE u.email='$email'"
)->fetch();

// All roles
$roles = $db->query("SELECT id, name, slug, level FROM roles ORDER BY level, name")->fetchAll();

// Check login_attempts / lockout
$lockout = $db->query("SELECT * FROM login_attempts WHERE email='$email' ORDER BY attempt_time DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Diagnostics — <?= $email ?></title>
<style>
  body { font-family: monospace; background:#0d0f14; color:#e0e0e0; padding:30px; max-width:800px; margin:0 auto; }
  h2   { color:#c9a84c; border-bottom:1px solid #333; padding-bottom:8px; }
  table{ width:100%; border-collapse:collapse; margin-bottom:20px; }
  td,th{ padding:8px 12px; border:1px solid #2a2a3a; font-size:13px; text-align:left; }
  th   { background:#1a1c2a; color:#c9a84c; }
  .ok  { color:#2dd4a0; }
  .bad { color:#e8556a; font-weight:bold; }
  .warn{ color:#f5a623; }
  .btn { background:#c9a84c; color:#0d0f14; border:none; padding:10px 20px; cursor:pointer; font-size:13px; border-radius:4px; font-weight:700; }
  .btn-red { background:#e8556a; color:#fff; }
  .alert { padding:14px 18px; border-radius:6px; margin-bottom:20px; font-size:14px; background:#1a2a1a; border:1px solid #2dd4a0; }
  select,input { background:#1a1c2a; color:#e0e0e0; border:1px solid #333; padding:6px 10px; border-radius:4px; }
  .danger-box { background:#2a0a0a; border:1px solid #e8556a; border-radius:6px; padding:16px; margin-top:24px; }
</style>
</head>
<body>

<h2>🔍 User Diagnostics: <?= $email ?></h2>

<?php if ($message): ?>
<div class="alert"><?= $message ?></div>
<?php endif; ?>

<?php if (!$user): ?>
<div style="color:#e8556a;font-size:16px;padding:20px;background:#2a0a0a;border-radius:6px;">
  ❌ No user found with email: <strong><?= $email ?></strong><br><br>
  The account does not exist in the database. You need to create it via Manage Staff.
  <br><br>
  <strong>All users in the system:</strong>
  <table>
    <tr><th>Email</th><th>Role</th><th>Active</th></tr>
    <?php foreach ($db->query("SELECT email, r.slug, is_active FROM users u JOIN roles r ON u.role_id=r.id ORDER BY email")->fetchAll() as $u): ?>
    <tr>
      <td><?= $u['email'] ?></td>
      <td><?= $u['slug'] ?></td>
      <td class="<?= $u['is_active']?'ok':'bad' ?>"><?= $u['is_active']?'YES':'NO' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php else: ?>

<!-- User Details -->
<h2>Account Status</h2>
<table>
  <tr><th>Field</th><th>Value</th><th>Assessment</th></tr>
  <tr>
    <td>Email</td>
    <td><?= $user['email'] ?></td>
    <td class="ok">✓</td>
  </tr>
  <tr>
    <td>Name</td>
    <td><?= $user['first_name'] . ' ' . $user['last_name'] ?></td>
    <td class="ok">✓</td>
  </tr>
  <tr>
    <td>is_active</td>
    <td><?= $user['is_active'] ?></td>
    <td class="<?= $user['is_active']?'ok':'bad' ?>"><?= $user['is_active']?'✓ Active':'❌ INACTIVE — cannot login!' ?></td>
  </tr>
  <tr>
    <td>Role</td>
    <td><?= $user['role_name'] ?> (<?= $user['role_slug'] ?>, level <?= $user['role_level'] ?>)</td>
    <td class="<?= in_array($user['role_slug'],['finance_admin','bursar','finance_officer','auditor'])?'ok':'warn' ?>">
      <?= in_array($user['role_slug'],['finance_admin','bursar','finance_officer','auditor'])?'✓ Finance role':'⚠️ NOT a finance role — will not see finance dashboard!' ?>
    </td>
  </tr>
  <tr>
    <td>Department</td>
    <td><?= $user['dept_name'] ?? 'NONE' ?></td>
    <td class="<?= $user['dept_name']?'ok':'warn' ?>"><?= $user['dept_name']?'✓':'⚠️ No department assigned' ?></td>
  </tr>
  <tr>
    <td>password_hash</td>
    <td><?= $user['password_hash'] ? substr($user['password_hash'],0,20).'…' : 'NULL' ?></td>
    <td class="<?= $user['password_hash']?'ok':'bad' ?>"><?= $user['password_hash']?'✓ Hash exists':'❌ No password set!' ?></td>
  </tr>
  <tr>
    <td>force_password_change</td>
    <td><?= $user['force_password_change'] ?></td>
    <td class="<?= $user['force_password_change']?'warn':'ok' ?>"><?= $user['force_password_change']?'⚠️ Will redirect to change password on first login':'✓' ?></td>
  </tr>
  <tr>
    <td>login_attempts</td>
    <td><?= $user['login_attempts'] ?? 0 ?></td>
    <td class="<?= ($user['login_attempts']??0)>=5?'bad':'ok' ?>"><?= ($user['login_attempts']??0)>=5?'❌ TOO MANY ATTEMPTS':'✓' ?></td>
  </tr>
  <tr>
    <td>locked_until</td>
    <td><?= $user['locked_until'] ?? 'NULL' ?></td>
    <td class="<?= ($user['locked_until'] && strtotime($user['locked_until'])>time())?'bad':'ok' ?>">
      <?= ($user['locked_until'] && strtotime($user['locked_until'])>time()) ? '❌ ACCOUNT LOCKED until '.$user['locked_until'] : '✓ Not locked' ?>
    </td>
  </tr>
  <tr>
    <td>last_login</td>
    <td><?= $user['last_login'] ?? 'Never' ?></td>
    <td class="<?= $user['last_login']?'ok':'warn' ?>"><?= $user['last_login']?'✓':'⚠️ Never logged in' ?></td>
  </tr>
</table>

<!-- Diagnosis summary -->
<h2>🩺 Diagnosis</h2>
<?php
$problems = [];
if (!$user['is_active'])           $problems[] = "Account is INACTIVE. The user cannot log in.";
if (!$user['password_hash'])       $problems[] = "No password hash set. The account has no password.";
if (!in_array($user['role_slug'],['finance_admin','bursar','finance_officer','auditor','ceo','principal']))
                                    $problems[] = "Role '".$user['role_slug']."' is not a finance role. User will NOT be redirected to finance dashboard.";
if (($user['login_attempts']??0)>=5) $problems[] = "Account has ".$user['login_attempts']." failed attempts — may be locked.";
if ($user['locked_until'] && strtotime($user['locked_until'])>time())
                                    $problems[] = "Account is LOCKED until ".$user['locked_until'];
if (!$user['password_hash'] || strlen($user['password_hash']) < 30)
                                    $problems[] = "Password hash looks invalid or too short.";

if (empty($problems)):
?>
<div style="color:#2dd4a0;padding:14px;background:#0a2a1a;border-radius:6px;border:1px solid #2dd4a0;">
  ✅ Account looks healthy. If login still fails, try resetting the password below.
</div>
<?php else: ?>
<div style="padding:14px;background:#2a0a0a;border-radius:6px;border:1px solid #e8556a;">
  <?php foreach ($problems as $p): ?>
  <div class="bad" style="margin-bottom:6px;">❌ <?= $p ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Fix Actions -->
<h2>🔧 Fix Actions</h2>

<!-- Reset Password -->
<form method="POST" style="margin-bottom:16px;">
  <input type="hidden" name="action" value="reset_password">
  <button type="submit" class="btn" onclick="return confirm('Reset password and unlock account?')">
    🔑 Reset Password + Activate + Unlock Account
  </button>
  <span style="font-size:12px;color:#888;margin-left:10px;">Sets password to: Lanbridge@2024 and forces password change on next login</span>
</form>

<!-- Fix Role -->
<form method="POST" style="margin-bottom:16px;">
  <input type="hidden" name="action" value="fix_role">
  <select name="role_id">
    <?php foreach ($roles as $r): ?>
    <option value="<?= $r['id'] ?>" <?= $r['id']==$user['role_id']?'selected':'' ?>>
      <?= $r['name'] ?> (<?= $r['slug'] ?>, level <?= $r['level'] ?>)
    </option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn" style="margin-left:8px;" onclick="return confirm('Update role?')">Update Role</button>
</form>

<!-- Recent login attempts -->
<?php if ($lockout): ?>
<h2>Recent Login Attempts</h2>
<table>
  <tr><th>Time</th><th>IP</th><th>Success</th></tr>
  <?php foreach ($lockout as $l): ?>
  <tr>
    <td><?= $l["attempt_time"] ?></td>
    <td><?= $l['ip_address'] ?? '—' ?></td>
    <td class="<?= $l['success']?'ok':'bad' ?>"><?= $l['success']?'YES':'NO' ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<?php endif; ?>

<div class="danger-box">
  ⚠️ <strong>IMPORTANT:</strong> Delete this file immediately after use.<br>
  <code>C:\xampp\htdocs\kpi\debug_user.php</code>
</div>

</body>
</html>
