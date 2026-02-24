<?php
// Output buffering — prevents 'headers already sent' errors
ob_start();

// ============================================================
// LANBRIDGE COLLEGE KPI SYSTEM — config.php
// Core configuration file
// ============================================================

// ── Environment detection ────────────────────────────────────
define('IS_LOCAL', in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']));

// ── Database Credentials ─────────────────────────────────────
// LOCAL (XAMPP): use the values below as-is
// LIVE (cPanel): change to your cPanel DB details
define('DB_HOST', 'localhost');
define('DB_NAME', 'lanbridge_kpi');
define('DB_USER', IS_LOCAL ? 'root' : 'YOUR_CPANEL_DB_USER');     // ← Change for live
define('DB_PASS', IS_LOCAL ? ''     : 'YOUR_CPANEL_DB_PASSWORD');  // ← Change for live
define('DB_CHARSET', 'utf8mb4');

// ── Site Settings ────────────────────────────────────────────
define('SITE_NAME', 'Lanbridge College KPI');
define('SITE_URL',  IS_LOCAL
    ? 'http://localhost/kpi'
    : 'https://www.lanbridgecollegezambia.com/kpi'
);

// ── Security ─────────────────────────────────────────────────
define('SECURE_COOKIE', !IS_LOCAL);   // false on localhost, true on live HTTPS
define('SESSION_TIMEOUT', 7200);      // 2 hours in seconds
define('BCRYPT_COST', 12);

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Africa/Lusaka');

// ── Error reporting ──────────────────────────────────────────
if (IS_LOCAL) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ── Session configuration (must be BEFORE session_start) ─────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    if (SECURE_COOKIE) {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}
