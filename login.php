<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Redirect to dashboard
if (isLoggedIn()) {
    $user = currentUser();
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'], $user));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } else {
        $result = attemptLogin($email, $password);
        if ($result['success']) {
            $user = currentUser();
            // Force password change?
            if (!empty($user['force_password_change'])) {
                header('Location: ' . SITE_URL . '/change_password.php?forced=1');
                exit;
            }
            header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'], $user));
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Lanbridge College KPI</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
  <style>
    .forgot-link {
      display: block;
      text-align: right;
      font-size: 12px;
      color: var(--text-muted);
      margin-top: -10px;
      margin-bottom: 18px;
    }
    .forgot-link:hover { color: var(--gold); }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-card">

    <!-- Logo -->
    <div class="login-logo"><img src="<?= SITE_URL ?>/assets/images/logo.jpg" alt="Lanbridge College" style="width:100%;height:100%;object-fit:cover;border-radius:50%;"></div>

    <!-- Title -->
    <h1 class="login-title">Lanbridge College</h1>
    <p class="login-subtitle">Staff Performance Reporting System</p>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom:20px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= sanitize($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" autocomplete="off" novalidate>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </span>
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="you@lanbridgecollegezambia.com"
            value="<?= sanitize($_POST['email'] ?? '') ?>"
            required
            autofocus
          >
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </span>
          <input
            type="password"
            name="password"
            id="passwordField"
            class="form-control"
            placeholder="Enter your password"
            required
            style="padding-right: 42px;"
          >
          <button type="button" class="input-action" onclick="togglePassword()" title="Show/hide password" id="eyeBtn">
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <a href="<?= SITE_URL ?>/forgot_password.php" class="forgot-link">Forgot password?</a>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Sign In to Portal
      </button>
    </form>

    <div class="login-footer">
      © <?= date('Y') ?> Lanbridge College — Confidential System<br>
      Authorized personnel only. All access is logged.
    </div>
  </div>
</div>

<script>
function togglePassword() {
  const field = document.getElementById('passwordField');
  const icon  = document.getElementById('eyeIcon');
  if (field.type === 'password') {
    field.type = 'text';
    icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    field.type = 'password';
    icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>

</body>
</html>
