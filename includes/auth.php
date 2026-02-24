<?php
// ============================================================
// LANBRIDGE COLLEGE KPI SYSTEM — auth.php
// All authentication, session, security, and helper functions
// ============================================================

require_once __DIR__ . '/config.php';

// ── Singleton PDO connection ──────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Show friendly error, never expose credentials
            die('<div style="font-family:sans-serif;padding:40px;background:#0d0f14;color:#e8556a;text-align:center;">
                <h2>Database Connection Failed</h2>
                <p>Please check your database credentials in <code>includes/config.php</code></p>
                <p style="color:#5c6478;font-size:12px;">' . (IS_LOCAL ? htmlspecialchars($e->getMessage()) : 'Contact your system administrator.') . '</p>
            </div>');
        }
    }
    return $pdo;
}

// ── Sanitize output (XSS prevention) ─────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ── Check if user is logged in ────────────────────────────────
function isLoggedIn(): bool {
    if (empty($_SESSION['user_id'])) return false;

    // Check session timeout
    if (!empty($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// ── Require login or redirect ─────────────────────────────────
function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $back = $redirect ?: SITE_URL . '/login.php';
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($back));
        exit;
    }
}

// ── Require role level (lower number = more access) ──────────
// Usage: requireRole(3) means level 3 or lower (CEO=1, VP=2, Head=3, Staff=4)
function requireRole(int $maxLevel, string $redirect = ''): void {
    requireLogin();
    $user = currentUser();
    if ((int)$user['role_level'] > $maxLevel) {
        header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'], $user));
        exit;
    }
}

// ── Get current logged-in user data ──────────────────────────
function currentUser(): array {
    if (empty($_SESSION['user_id'])) return [];
    // Clear stale cache that predates dept_code being stored
    if (!empty($_SESSION['user_cache'])) {
        if (!array_key_exists('dept_code', $_SESSION['user_cache'])) {
            unset($_SESSION['user_cache']); // force refresh
        } else {
            return $_SESSION['user_cache'];
        }
    }

    $db  = getDB();
    $sql = "SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level,
                   d.name AS dept_name, d.code AS dept_code
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.id = ? AND u.is_active = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) { logout(); return []; }

    $_SESSION['user_cache'] = $user;
    return $user;
}

// ── Clear user cache (call after profile updates) ─────────────
function clearUserCache(): void {
    unset($_SESSION['user_cache']);
}

// ── Get dashboard path for a role ────────────────────────────
function getRoleDashboard(string $slug, array $user = []): string {
    // IT department heads → IT dashboard
    if ($slug === 'dept_head' && strtoupper(trim($user['dept_code'] ?? '')) === 'IT') {
        return 'it_dashboard.php';
    }
    return match($slug) {
        'ceo'                => 'ceo_dashboard.php',
        'principal'          => 'principal_dashboard.php',
        'vice_principal'     => 'vp_dashboard.php',
        'finance_admin'      => 'finance_dashboard.php',
        'bursar'             => 'finance_dashboard.php',
        'auditor'            => 'finance_dashboard.php',
        'finance_officer'    => 'finance_dashboard.php',
        'it_admin'           => 'it_dashboard.php',
        'it_officer'         => 'it_dashboard.php',
        'dept_head'          => 'head_dashboard.php',
        default              => 'staff_dashboard.php',
    };
}

// ── Get security config value ─────────────────────────────────
function getSecurityConfig(string $key, $default = null): mixed {
    static $cache = [];
    if (!isset($cache[$key])) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM security_config WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row  = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
    }
    return $cache[$key];
}

// ── Check if IP/email is locked out ──────────────────────────
function isLockedOut(string $email, string $ip): array {
    $db          = getDB();
    $maxAttempts = (int)getSecurityConfig('max_login_attempts', 5);
    $lockMins    = (int)getSecurityConfig('lockout_duration_mins', 30);
    $window      = date('Y-m-d H:i:s', strtotime("-{$lockMins} minutes"));

    $stmt = $db->prepare(
        "SELECT COUNT(*) AS cnt FROM login_attempts
         WHERE email = ? AND success = 0 AND attempt_time > ?"
    );
    $stmt->execute([$email, $window]);
    $row = $stmt->fetch();

    if ((int)$row['cnt'] >= $maxAttempts) {
        // Find oldest attempt in window to calculate remaining lock time
        $stmt2 = $db->prepare(
            "SELECT attempt_time FROM login_attempts
             WHERE email = ? AND success = 0 AND attempt_time > ?
             ORDER BY attempt_time ASC LIMIT 1"
        );
        $stmt2->execute([$email, $window]);
        $first   = $stmt2->fetch();
        $unlockAt = strtotime($first['attempt_time']) + ($lockMins * 60);
        $remaining = max(0, ceil(($unlockAt - time()) / 60));
        return ['locked' => true, 'minutes_remaining' => $remaining, 'attempts' => (int)$row['cnt']];
    }

    return ['locked' => false, 'attempts' => (int)$row['cnt']];
}

// ── Record a login attempt ────────────────────────────────────
function recordLoginAttempt(string $email, string $ip, bool $success): void {
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO login_attempts (email, ip_address, success, user_agent)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$email, $ip, $success ? 1 : 0, $_SERVER['HTTP_USER_AGENT'] ?? '']);
}

// ── Attempt login — returns ['success'=>bool, 'user'=>[], 'error'=>''] ──
function attemptLogin(string $email, string $password): array {
    $db  = getDB();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $email = strtolower(trim($email));

    // 1. Check lockout
    $lockCheck = isLockedOut($email, $ip);
    if ($lockCheck['locked']) {
        return ['success' => false, 'error' => "Too many failed attempts. Try again in {$lockCheck['minutes_remaining']} minute(s).", 'locked' => true];
    }

    // 2. Find user
    $stmt = $db->prepare(
        "SELECT u.*, r.slug AS role_slug, r.level AS role_level
         FROM users u JOIN roles r ON u.role_id = r.id
         WHERE u.email = ? AND u.is_active = 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 3. Verify password
    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($email, $ip, false);
        $remaining = (int)getSecurityConfig('max_login_attempts', 5) - ($lockCheck['attempts'] + 1);
        $msg = $remaining > 0
            ? "Invalid email or password. {$remaining} attempt(s) remaining."
            : "Invalid email or password. Account will be locked on next failure.";
        return ['success' => false, 'error' => $msg];
    }

    // 4. Success — set up session
    recordLoginAttempt($email, $ip, true);
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['user_cache']);

    // 5. Update last login
    $db->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE id = ?")
       ->execute([$user['id']]);

    // 6. Log activity
    logActivity($user['id'], 'LOGIN', 'User logged in from ' . $ip);

    return ['success' => true, 'user' => $user];
}

// ── Logout ───────────────────────────────────────────────────
function logout(): void {
    if (!empty($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Validate password strength ────────────────────────────────
function validatePasswordStrength(string $password): array {
    $errors = [];
    $minLen = (int)getSecurityConfig('password_min_length', 8);

    if (strlen($password) < $minLen)
        $errors[] = "Password must be at least {$minLen} characters.";
    if (getSecurityConfig('require_uppercase', 1) && !preg_match('/[A-Z]/', $password))
        $errors[] = "Password must contain at least one uppercase letter.";
    if (getSecurityConfig('require_lowercase', 1) && !preg_match('/[a-z]/', $password))
        $errors[] = "Password must contain at least one lowercase letter.";
    if (getSecurityConfig('require_numbers', 1) && !preg_match('/[0-9]/', $password))
        $errors[] = "Password must contain at least one number.";
    if (getSecurityConfig('require_special', 1) && !preg_match('/[\W_]/', $password))
        $errors[] = "Password must contain at least one special character (!@#\$%^&* etc).";

    return $errors;
}

// ── Check if password was recently used ──────────────────────
function isPasswordReused(int $userId, string $newPassword): bool {
    $db    = getDB();
    $count = (int)getSecurityConfig('password_history_count', 5);
    $stmt  = $db->prepare(
        "SELECT password_hash FROM password_history
         WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$userId, $count]);
    foreach ($stmt->fetchAll() as $row) {
        if (password_verify($newPassword, $row['password_hash'])) return true;
    }
    return false;
}

// ── Update password ───────────────────────────────────────────
function updatePassword(int $userId, string $newPassword): bool {
    $db   = getDB();
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    // Save to history
    $db->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)")
       ->execute([$userId, $hash]);

    // Update user
    $db->prepare(
        "UPDATE users SET password_hash = ?, password_changed_at = NOW(),
         force_password_change = 0 WHERE id = ?"
    )->execute([$hash, $userId]);

    clearUserCache();
    logActivity($userId, 'PASSWORD_CHANGE', 'Password updated successfully');
    return true;
}

// ── Check if password is expired ─────────────────────────────
function isPasswordExpired(array $user): bool {
    $days = (int)getSecurityConfig('password_expiry_days', 90);
    if (!$user['password_changed_at']) return false;
    $changed = strtotime($user['password_changed_at']);
    return (time() - $changed) > ($days * 86400);
}

// ── Log user activity ─────────────────────────────────────────
function logActivity(?int $userId, string $action, string $details = ''): void {
    try {
        $db   = getDB();
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $db->prepare(
            "INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $action, $details, $ip]);

        // Also write to audit_log
        $db->prepare(
            "INSERT INTO audit_log (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)"
        )->execute([$userId, $action, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) {
        // Silently fail — logging should never break the app
    }
}

// ── Send in-app notification ──────────────────────────────────
function sendNotification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void {
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $title, $message, $type, $link]);
}

// ── Get unread notification count ────────────────────────────
function getUnreadNotificationCount(int $userId): int {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ── Queue an email ────────────────────────────────────────────
function queueEmail(string $toEmail, string $toName, string $subject, string $body): void {
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO email_queue (to_email, to_name, subject, body) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$toEmail, $toName, $subject, $body]);
}

// ── Flash message helpers ─────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Get user initials (for avatar circles) ────────────────────
function getInitials(string $firstName, string $lastName): string {
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

// ── Format date nicely ────────────────────────────────────────
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

// ── Time ago helper ───────────────────────────────────────────
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d', strtotime($datetime));
}

// ── Get greeting based on time ────────────────────────────────
function getGreeting(): string {
    $hour = (int)date('G');
    if ($hour < 12) return 'Good morning';
    if ($hour < 17) return 'Good afternoon';
    return 'Good evening';
}

// ── Status badge HTML ─────────────────────────────────────────
function statusBadge(string $status): string {
    $map = [
        'pending'            => ['class' => 'badge-warning',  'label' => 'Pending'],
        'approved'           => ['class' => 'badge-success',  'label' => 'Approved'],
        'rejected'           => ['class' => 'badge-danger',   'label' => 'Rejected'],
        'revision_requested' => ['class' => 'badge-info',     'label' => 'Revision'],
    ];
    $b = $map[$status] ?? ['class' => 'badge-muted', 'label' => ucfirst($status)];
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}

// ── Get system setting ────────────────────────────────────────
function getSetting(string $key, $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row  = $stmt->fetch();
        $cache[$key] = $row ? $row['setting_value'] : $default;
    }
    return $cache[$key];
}

// ── Avatar HTML helper ────────────────────────────────────────
// Usage: echo avatarHtml($row, 'sm') — where $row has first_name, last_name, avatar
function avatarHtml(array $user, string $size = ''): string {
    $initials  = getInitials($user['first_name'] ?? '?', $user['last_name'] ?? '?');
    $sizeClass = $size ? ' avatar-' . $size : '';
    if (!empty($user['avatar'])) {
        $url = SITE_URL . '/' . ltrim($user['avatar'], '/');
        return '<div class="avatar' . $sizeClass . '">'
             . '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="' . htmlspecialchars($initials, ENT_QUOTES) . '">'
             . '</div>';
    }
    return '<div class="avatar' . $sizeClass . '">' . htmlspecialchars($initials, ENT_QUOTES) . '</div>';
}

// ── Finance role check ────────────────────────────────────────
function isFinanceRole(array $user): bool {
    return in_array($user['role_slug'] ?? '', ['ceo','principal','finance_admin','bursar','auditor','finance_officer']);
}

// ── IT role check ─────────────────────────────────────────────
function isItRole(array $user): bool {
    // IT role slugs OR anyone whose department is Information Technology
    if (in_array($user['role_slug'] ?? '', ['ceo','principal','it_admin','it_officer'])) return true;
    $deptCode = strtoupper(trim($user['dept_code'] ?? ''));
    return $deptCode === 'IT';
}

// ── Finance audit log (immutable — write-only) ────────────────
function logFinanceAudit(
    ?int   $userId,
    string $action,
    string $tableName,
    ?int   $recordId  = null,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    try {
        $db   = getDB();
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $db->prepare(
            "INSERT INTO financial_audit_logs
             (user_id, action, table_name, record_id, old_values, new_values, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $ip,
        ]);
    } catch (Exception $e) {
        // Silently fail — logging must never break the application
    }
}

// ── Generate unique reference numbers ────────────────────────
function generateRef(string $prefix): string {
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// ── Format currency (ZMW) ─────────────────────────────────────
function formatMoney(float $amount, string $currency = 'ZMW'): string {
    return $currency . ' ' . number_format($amount, 2);
}

// ── Priority badge HTML ───────────────────────────────────────
function priorityBadge(string $priority): string {
    $map = [
        'low'      => 'badge-muted',
        'medium'   => 'badge-info',
        'high'     => 'badge-warning',
        'critical' => 'badge-danger',
    ];
    $class = $map[$priority] ?? 'badge-muted';
    return '<span class="badge ' . $class . '">' . ucfirst($priority) . '</span>';
}

// ── IT ticket status badge ────────────────────────────────────
function ticketBadge(string $status): string {
    $map = [
        'open'           => ['badge-warning', 'Open'],
        'in_progress'    => ['badge-info',    'In Progress'],
        'pending_user'   => ['badge-gold',    'Pending User'],
        'resolved'       => ['badge-success', 'Resolved'],
        'closed'         => ['badge-muted',   'Closed'],
        'cancelled'      => ['badge-danger',  'Cancelled'],
    ];
    $b = $map[$status] ?? ['badge-muted', ucfirst($status)];
    return '<span class="badge ' . $b[0] . '">' . $b[1] . '</span>';
}


// ── Holiday helpers ───────────────────────────────────────────
function isHoliday(string $date): bool {
    static $cache = null;
    if ($cache === null) {
        try {
            $db = getDB();
            $rows = $db->query("SELECT holiday_date FROM public_holidays WHERE is_active=1")->fetchAll(PDO::FETCH_COLUMN, 0);
            $cache = array_flip($rows);
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return isset($cache[$date]);
}

function isWorkday(string $date): bool {
    $dow = (int)date('N', strtotime($date));
    return $dow < 6 && !isHoliday($date);
}
