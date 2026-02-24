<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
logout();
header('Location: ' . SITE_URL . '/login.php?logged_out=1');
exit;
