<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

// icon() is defined in header.php — redeclare here since we don't include it
if (!function_exists('icon')) {
    function icon(string $name, int $size = 16): string {
        $s = $size; $icons = [
            'lock'         => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'x'            => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            'check-square' => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        ];
        $svg = $icons[$name] ?? $icons['x'];
        return sprintf($svg, $s, $s);
    }
}

$user   = currentUser();
$forced = isset($_GET['forced']);
$error  = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif ($errors = validatePasswordStrength($new)) {
        $error = implode(' ', $errors);
    } elseif (isPasswordReused((int)$user['id'], $new)) {
        $error = 'You cannot reuse one of your last 5 passwords.';
    } else {
        updatePassword((int)$user['id'], $new);
        if ($forced) {
            header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'], $user));
        } else {
            setFlash('success','✅ Password changed successfully.');
            header('Location: ' . SITE_URL . '/change_password.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Change Password — Lanbridge KPI</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="login-page">
  <div class="login-card" style="max-width:480px;">
    <div class="login-logo"><img src="<?= SITE_URL ?>/assets/images/logo.jpg" alt="Lanbridge College" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>
    <h1 class="login-title">Change Password</h1>
    <?php if ($forced): ?>
    <div class="alert alert-warning" style="margin-bottom:20px;"><?= icon('lock',15) ?> You must change your password before continuing.</div>
    <?php else: ?>
    <p class="login-subtitle">Update your account password</p>
    <?php endif; ?>

    <?php if ($error): ?><div class="alert alert-danger"><?= icon('x',15) ?> <?= sanitize($error) ?></div><?php endif; ?>
    <?php $flash = getFlash(); if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div><?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <div class="input-wrap">
          <span class="input-icon"><?= icon('lock',15) ?></span>
          <input type="password" name="current_password" class="form-control" required placeholder="Your current password">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" id="newPass" class="form-control" required placeholder="Choose a strong password" oninput="checkStrength(this.value)">
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-label" id="strengthLabel">Enter a password</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
      </div>

      <!-- Requirements checklist -->
      <ul class="req-list">
        <li class="req-item" id="req-len"><?= icon('x',12) ?> At least 8 characters</li>
        <li class="req-item" id="req-upper"><?= icon('x',12) ?> One uppercase letter</li>
        <li class="req-item" id="req-lower"><?= icon('x',12) ?> One lowercase letter</li>
        <li class="req-item" id="req-num"><?= icon('x',12) ?> One number</li>
        <li class="req-item" id="req-special"><?= icon('x',12) ?> One special character</li>
      </ul>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px;"><?= icon('lock',15) ?> Update Password</button>
      <?php if (!$forced): ?>
      <a href="<?= SITE_URL ?>/portals/<?= getRoleDashboard($user['role_slug'], $user) ?>" class="btn btn-outline btn-full" style="margin-top:10px;">Cancel</a>
      <?php endif; ?>
    </form>

    <div class="login-footer">© <?= date('Y') ?> Lanbridge College</div>
  </div>
</div>
<script>
const checkIcon = `<?= icon('check-square',12) ?>`;
const xIcon     = `<?= icon('x',12) ?>`;
function checkStrength(val) {
  const reqs = {
    'req-len':     val.length >= 8,
    'req-upper':   /[A-Z]/.test(val),
    'req-lower':   /[a-z]/.test(val),
    'req-num':     /[0-9]/.test(val),
    'req-special': /[\W_]/.test(val),
  };
  let score = Object.values(reqs).filter(Boolean).length;
  Object.entries(reqs).forEach(([id,met]) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('met', met);
    el.innerHTML = (met ? checkIcon : xIcon) + ' ' + el.textContent.trim().replace(/^. /,'');
  });
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  const states = [
    {pct:0,  cls:'',       txt:'Enter a password'},
    {pct:20, cls:'weak',   txt:'Weak'},
    {pct:40, cls:'fair',   txt:'Fair'},
    {pct:70, cls:'good',   txt:'Good'},
    {pct:90, cls:'strong', txt:'Strong'},
  ];
  const s = score <= 0 ? states[0] : score === 1 ? states[1] : score === 2 ? states[2] : score <= 4 ? states[3] : states[4];
  fill.style.width = s.pct+'%';
  fill.className   = 'strength-fill '+s.cls;
  label.textContent = s.txt;
}
</script>
</body>
</html>
