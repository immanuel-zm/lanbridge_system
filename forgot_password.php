<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: '.SITE_URL.'/portals/'.getRoleDashboard(currentUser()['role_slug'])); exit; }

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, first_name FROM users WHERE email=? AND is_active=1");
        $stmt->execute([$email]);
        $found = $stmt->fetch();
        if ($found) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->prepare("UPDATE users SET password_reset_token=?, password_reset_expires=? WHERE id=?")->execute([$token,$expires,$found['id']]);
            $resetLink = SITE_URL.'/reset_password.php?token='.$token;
            queueEmail($email, $found['first_name'], 'Password Reset — Lanbridge KPI',
                "Hello {$found['first_name']},\n\nClick the link below to reset your password (valid 1 hour):\n\n$resetLink\n\nIf you did not request this, ignore this email.\n\nLanbridge College KPI System"
            );
            logActivity($found['id'], 'PASSWORD_RESET_REQUEST', 'Reset requested for '.$email);
        }
        $sent = true; // Always show success (prevents email enumeration)
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — Lanbridge KPI</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo"><img src="<?= SITE_URL ?>/assets/images/logo.jpg" alt="Lanbridge College" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>
    <h1 class="login-title">Forgot Password?</h1>
    <p class="login-subtitle">Enter your email to receive a reset link</p>

    <?php if ($sent): ?>
    <div class="alert alert-success">
      <?= icon('check-square',15) ?>
      <div>If an account exists for that email, a reset link has been sent.<br>
      <small style="opacity:0.8;">For local testing: check the <strong>email_queue</strong> table in phpMyAdmin for the reset link.</small></div>
    </div>
    <?php else: ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= icon('x',15) ?> <?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon"><?= icon('bell',15) ?></span>
          <input type="email" name="email" class="form-control" required autofocus placeholder="your@email.com">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg"><?= icon('send',15) ?> Send Reset Link</button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;">
      <a href="<?= SITE_URL ?>/login.php" style="font-size:13px;color:var(--text-muted);">← Back to Login</a>
    </div>
    <div class="login-footer">© <?= date('Y') ?> Lanbridge College</div>
  </div>
</div>
</body>
</html>
