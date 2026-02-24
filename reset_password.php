<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: '.SITE_URL); exit; }

$token = trim($_GET['token'] ?? '');
$error = $success = '';
$user  = null;

if ($token) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE password_reset_token=? AND password_reset_expires > NOW() AND is_active=1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
}

if (!$user && $token) { $error = 'This reset link is invalid or has expired. Please request a new one.'; }

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($errors = validatePasswordStrength($new)) {
        $error = implode(' ', $errors);
    } elseif (isPasswordReused((int)$user['id'], $new)) {
        $error = 'You cannot reuse a recent password.';
    } else {
        updatePassword((int)$user['id'], $new);
        $db->prepare("UPDATE users SET password_reset_token=NULL, password_reset_expires=NULL WHERE id=?")->execute([$user['id']]);
        $success = 'Password updated successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password — Lanbridge KPI</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="login-page">
  <div class="login-card" style="max-width:460px;">
    <div class="login-logo"><img src="<?= SITE_URL ?>/assets/images/logo.jpg" alt="Lanbridge College" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>
    <h1 class="login-title">Set New Password</h1>

    <?php if ($error && !$user): ?>
    <div class="alert alert-danger"><?= icon('x',15) ?> <?= sanitize($error) ?></div>
    <div style="text-align:center;margin-top:16px;">
      <a href="<?= SITE_URL ?>/forgot_password.php" class="btn btn-primary">Request New Reset Link</a>
    </div>
    <?php elseif ($success): ?>
    <div class="alert alert-success"><?= icon('check-square',15) ?> <?= sanitize($success) ?></div>
    <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary btn-full" style="margin-top:16px;"><?= icon('log-out',15) ?> Go to Login</a>
    <?php elseif ($user): ?>
    <p class="login-subtitle">Welcome, <?= sanitize($user['first_name']) ?>. Set your new password below.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= icon('x',15) ?> <?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" id="newPass" class="form-control" required placeholder="Choose a strong password" oninput="checkStrength(this.value)">
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-label" id="strengthLabel">Enter a password</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
      </div>
      <ul class="req-list">
        <li class="req-item" id="req-len"><?= icon('x',12) ?> At least 8 characters</li>
        <li class="req-item" id="req-upper"><?= icon('x',12) ?> One uppercase letter</li>
        <li class="req-item" id="req-lower"><?= icon('x',12) ?> One lowercase letter</li>
        <li class="req-item" id="req-num"><?= icon('x',12) ?> One number</li>
        <li class="req-item" id="req-special"><?= icon('x',12) ?> One special character</li>
      </ul>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px;"><?= icon('lock',15) ?> Set New Password</button>
    </form>
    <?php endif; ?>
    <div style="text-align:center;margin-top:16px;"><a href="<?= SITE_URL ?>/login.php" style="font-size:13px;color:var(--text-muted);">← Back to Login</a></div>
    <div class="login-footer">© <?= date('Y') ?> Lanbridge College</div>
  </div>
</div>
<script>
const checkIcon = `<?= icon('check-square',12) ?>`;
const xIcon = `<?= icon('x',12) ?>`;
function checkStrength(val) {
  const reqs = {'req-len':val.length>=8,'req-upper':/[A-Z]/.test(val),'req-lower':/[a-z]/.test(val),'req-num':/[0-9]/.test(val),'req-special':/[\W_]/.test(val)};
  let score = Object.values(reqs).filter(Boolean).length;
  Object.entries(reqs).forEach(([id,met]) => { const el=document.getElementById(id); if(!el)return; el.classList.toggle('met',met); el.innerHTML=(met?checkIcon:xIcon)+' '+el.textContent.trim().replace(/^. /,''); });
  const fill=document.getElementById('strengthFill'),label=document.getElementById('strengthLabel');
  const s=score<=0?{pct:0,cls:'',txt:'Enter a password'}:score===1?{pct:20,cls:'weak',txt:'Weak'}:score===2?{pct:40,cls:'fair',txt:'Fair'}:score<=4?{pct:70,cls:'good',txt:'Good'}:{pct:90,cls:'strong',txt:'Strong'};
  fill.style.width=s.pct+'%'; fill.className='strength-fill '+s.cls; label.textContent=s.txt;
}
</script>
</body>
</html>
