<?php
// ============================================================
// LANBRIDGE COLLEGE KPI — header.php
// Shared layout: sidebar + topbar
// Usage: require_once '../includes/header.php';
// Must set $pageTitle before including this file.
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();
$user      = currentUser();
$role      = $user['role_slug'];
$roleLevel = (int)$user['role_level'];
$initials  = getInitials($user['first_name'], $user['last_name']);
$unread    = getUnreadNotificationCount($user['id']);
$avatarUrl = !empty($user['avatar']) ? SITE_URL . '/' . ltrim($user['avatar'], '/') : null;
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? date('l, F j, Y');

// Determine active nav item from current file
$currentFile = basename($_SERVER['PHP_SELF']);

function navItem(string $href, string $icon, string $label, string $currentFile, ?int $badge = null): string {
    $file    = basename($href);
    $active  = ($file === $currentFile) ? ' active' : '';
    $badgeHtml = $badge ? '<span class="nav-badge">' . $badge . '</span>' : '';
    return '<a href="' . $href . '" class="nav-item' . $active . '">' . $icon . '<span>' . $label . '</span>' . $badgeHtml . '</a>';
}

// SVG icon helper
function icon(string $name, int $size = 16): string {
    $icons = [
        'grid'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'bar-chart'   => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'users'       => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'check-square'=> '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
        'file-text'   => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'download'    => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'shield'      => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'settings'    => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'bell'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'user'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'lock'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'log-out'     => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'layers'      => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
        'send'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'clock'       => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'map-pin'     => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'menu'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'x'           => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'trending-up' => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'inbox'       => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>',
        'briefcase'   => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'dollar'      => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
        'monitor'     => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'clipboard'   => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>',
        'tool'        => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'volume-2'    => '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>',
    ];
    $svg = $icons[$name] ?? $icons['grid'];
    return sprintf($svg, $size, $size);
}

// Get pending approvals count for badge
$pendingCount = 0;
if ($roleLevel <= 3) {
    try {
        $db = getDB();
        if ($roleLevel <= 2) {
            $pendingCount = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
        } else {
            $deptId = $user['department_id'];
            $stmt   = $db->prepare("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='pending' AND u.department_id=?");
            $stmt->execute([$deptId]);
            $pendingCount = (int)$stmt->fetchColumn();
        }
    } catch (Exception $e) {}
}

// Get user's open helpdesk ticket count for badge
$myOpenHelpdesk = 0;
try {
    $db = getDB();
    $myOpenHelpdesk = (int)$db->query(
        "SELECT COUNT(*) FROM it_tickets WHERE submitted_by={$user['id']} AND status IN ('open','in_progress','pending_user')"
    )->fetchColumn();
} catch (Exception $e) {}

$base = SITE_URL . '/portals/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($pageTitle) ?> — Lanbridge KPI</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
  <style>
  /* ── Real-time notification panel ── */
  .notif-wrapper { position:relative; }
  .notif-panel {
    position:absolute; top:calc(100% + 8px); right:0;
    width:340px; background:var(--bg-card);
    border:1px solid var(--border); border-radius:var(--radius);
    box-shadow:0 8px 32px rgba(0,0,0,0.4);
    z-index:9999; overflow:hidden;
    animation:notifFadeIn 0.18s ease;
  }
  @keyframes notifFadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
  .notif-panel-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 16px; border-bottom:1px solid var(--border);
    background:var(--bg-elevated);
  }
  .notif-panel-list { max-height:320px; overflow-y:auto; }
  .notif-panel-footer {
    display:block; text-align:center; padding:10px;
    font-size:12px; color:var(--gold); text-decoration:none;
    border-top:1px solid var(--border); background:var(--bg-elevated);
  }
  .notif-panel-footer:hover { background:var(--bg-hover); }
  .notif-item {
    display:flex; gap:10px; padding:12px 16px;
    border-bottom:1px solid var(--border);
    cursor:pointer; transition:background 0.15s;
    text-decoration:none; color:inherit;
  }
  .notif-item:hover { background:var(--bg-hover); }
  .notif-item.unread { background:rgba(201,168,76,0.05); }
  .notif-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px; }
  .notif-dot.success { background:var(--success); }
  .notif-dot.danger  { background:var(--danger); }
  .notif-dot.warning { background:var(--warning); }
  .notif-dot.info    { background:var(--info); }
  .notif-item-title  { font-size:12.5px; font-weight:600; color:var(--text-primary); line-height:1.4; }
  .notif-item-msg    { font-size:11px; color:var(--text-muted); margin-top:2px; line-height:1.4; }
  .notif-item-time   { font-size:10px; color:var(--text-muted); margin-top:3px; }
  .notif-empty { padding:32px; text-align:center; font-size:13px; color:var(--text-muted); }
  .notif-toast-stack { position:fixed; bottom:24px; right:24px; z-index:99999; display:flex; flex-direction:column-reverse; gap:8px; pointer-events:none; }
  .notif-toast {
    min-width:280px; max-width:360px; background:var(--bg-card);
    border:1px solid var(--border); border-radius:var(--radius);
    box-shadow:0 4px 24px rgba(0,0,0,0.4);
    padding:14px 16px; display:flex; gap:10px; align-items:flex-start;
    animation:toastIn 0.25s ease; pointer-events:all;
  }
  @keyframes toastIn { from{opacity:0;transform:translateX(40px)} to{opacity:1;transform:translateX(0)} }
  .notif-toast-close { margin-left:auto; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:16px; padding:0 2px; flex-shrink:0; line-height:1; }
  </style>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ─────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-logo"><img src="<?= SITE_URL ?>/assets/images/logo.jpg" alt="Lanbridge College" style="width:100%;height:100%;object-fit:cover;border-radius:8px;"></div>
    <div class="brand-text">
      <strong>Lanbridge</strong>
      <span>KPI System</span>
    </div>
  </div>

  <!-- Role badge -->
  <div class="sidebar-role">
    <span class="role-badge">
      <?= icon('briefcase', 10) ?>
      <?= sanitize($user['role_name']) ?>
    </span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <?php if ($roleLevel === 1): // CEO ?>
      <div class="nav-section-label">Executive</div>
      <?= navItem($base.'ceo_dashboard.php',  icon('grid',16),        'Executive Dashboard', $currentFile) ?>
      <?= navItem($base.'ceo_analytics.php',  icon('bar-chart',16),   'Advanced Analytics',  $currentFile) ?>

      <div class="nav-section-label">Management</div>
      <?= navItem($base.'manage_users.php',   icon('users',16),       'Manage Staff',        $currentFile) ?>
      <?= navItem($base.'departments.php',    icon('layers',16),      'Departments',         $currentFile) ?>
      <?= navItem($base.'vp_approvals.php',   icon('check-square',16),'Approve Reports',     $currentFile, $pendingCount ?: null) ?>

      <div class="nav-section-label">Intelligence</div>
      <?= navItem($base.'ai_insights.php',    icon('zap',16),         'AI Insights',         $currentFile) ?>
      <?= navItem($base.'scorecards.php',     icon('award',16),       'Scorecards',          $currentFile) ?>
      <?= navItem($base.'risk_radar.php',     icon('shield',16),      'Risk Radar',          $currentFile) ?>

      <div class="nav-section-label">Reports</div>
      <?= navItem($base.'reports.php',        icon('file-text',16),   'Performance Reports', $currentFile) ?>
      <?= navItem($base.'export.php',         icon('download',16),    'Export Data',         $currentFile) ?>
      <?= navItem($base.'audit_log.php',      icon('shield',16),      'Audit Log',           $currentFile) ?>
      <?= navItem($base.'settings.php',       icon('settings',16),    'Settings',            $currentFile) ?>
      <?= navItem($base.'holidays.php',       icon('calendar',16),    'Holiday Calendar',    $currentFile) ?>

    <?php elseif ($roleLevel === 2): // Vice Principal ?>
      <div class="nav-section-label">Overview</div>
      <?= navItem($base.'vp_dashboard.php',   icon('grid',16),        'Dashboard',           $currentFile) ?>

      <div class="nav-section-label">Academic</div>
      <?= navItem($base.'vp_approvals.php',   icon('check-square',16),'Approve Reports',     $currentFile, $pendingCount ?: null) ?>
      <?= navItem($base.'manage_users.php',   icon('users',16),       'Academic Staff',      $currentFile) ?>
      <?= navItem($base.'reports.php',        icon('file-text',16),   'Performance',         $currentFile) ?>
      <?= navItem($base.'ai_insights.php',    icon('zap',16),         'AI Insights',         $currentFile) ?>
      <?= navItem($base.'scorecards.php',     icon('award',16),       'Scorecards',          $currentFile) ?>
      <?= navItem($base.'export.php',         icon('download',16),    'Export Data',         $currentFile) ?>
      <?= navItem($base.'ceo_analytics.php',  icon('bar-chart',16),   'Analytics',           $currentFile) ?>
      <?= navItem($base.'audit_log.php',      icon('shield',16),      'Audit Log',           $currentFile) ?>

    <?php elseif ($roleLevel === 3 && strtoupper($user['dept_code']??'') === 'IT'): // IT Dept Head ?>
      <div class="nav-section-label">IT Operations</div>
      <?= navItem($base.'it_dashboard.php',  icon('monitor',16),        'IT Dashboard',     $currentFile) ?>
      <?= navItem($base.'it_tickets.php',    icon('message-square',16), 'Helpdesk Tickets', $currentFile) ?>
      <?= navItem($base.'it_assets.php',     icon('layers',16),         'Asset Register',   $currentFile) ?>
      <?= navItem($base.'tasks.php',         icon('clipboard',16),      'Tasks',            $currentFile) ?>

    <?php elseif ($roleLevel === 3): // Department Head ?>
      <div class="nav-section-label">Overview</div>
      <?= navItem($base.'head_dashboard.php', icon('grid',16),        'Dashboard',           $currentFile) ?>

      <div class="nav-section-label">My Department</div>
      <?= navItem($base.'head_approvals.php', icon('check-square',16),'Approve Reports',     $currentFile, $pendingCount ?: null) ?>
      <?= navItem($base.'manage_users.php',   icon('users',16),       'My Team',             $currentFile) ?>
      <?= navItem($base.'reports.php',        icon('file-text',16),   'Dept Performance',    $currentFile) ?>
      <?= navItem($base.'export.php',         icon('download',16),    'Export Data',         $currentFile) ?>

    <?php elseif (in_array($role, ['finance_admin','finance_officer','bursar','auditor'])): ?>
      <div class="nav-section-label">Finance</div>
      <?= navItem($base.'finance_dashboard.php',  icon('dollar',16),    'Finance Dashboard',   $currentFile) ?>
      <?= navItem($base.'finance_fees.php',        icon('users',16),     'Student Fees',        $currentFile) ?>
      <?= navItem($base.'finance_transactions.php',icon('bar-chart',16), 'Transactions',        $currentFile) ?>
      <?= navItem($base.'finance_budget.php',      icon('layers',16),    'Budget & Expenditure',$currentFile) ?>
      <?= navItem($base.'finance_payroll.php',     icon('briefcase',16), 'Payroll',             $currentFile) ?>
      <?= navItem($base.'finance_procurement.php', icon('clipboard',16), 'Procurement',         $currentFile) ?>
      <?= navItem($base.'audit_log.php',           icon('shield',16),    'Audit Log',           $currentFile) ?>

    <?php elseif (in_array($role, ['it_admin','it_officer'])): ?>
      <div class="nav-section-label">IT Operations</div>
      <?= navItem($base.'it_dashboard.php',  icon('monitor',16),   'IT Dashboard',  $currentFile) ?>
      <?= navItem($base.'it_tickets.php',    icon('message-square',16),'Helpdesk Tickets',$currentFile) ?>
      <?= navItem($base.'it_assets.php',     icon('layers',16),    'Asset Register',$currentFile) ?>
      <?= navItem($base.'tasks.php',         icon('clipboard',16), 'Tasks',         $currentFile) ?>

    <?php else: // Staff & other roles ?>
      <div class="nav-section-label">My Work</div>
      <?= navItem($base.'staff_dashboard.php',icon('grid',16),        'My Dashboard',        $currentFile) ?>
      <?= navItem($base.'submit_report.php',  icon('send',16),        'Submit Daily Report', $currentFile) ?>
      <?= navItem($base.'submit_kpi.php',     icon('bar-chart',16),   'Submit KPI',          $currentFile) ?>
      <?= navItem($base.'my_submissions.php', icon('clock',16),       'My History',          $currentFile) ?>
    <?php endif; ?>

    <!-- Tasks + Announcements + Notifications (all roles) -->
    <div class="nav-section-label">Account</div>
    <?= navItem($base.'tasks.php',         icon('clipboard',16),'Tasks',         $currentFile) ?>
    <?= navItem($base.'helpdesk.php',      icon('tool',16),     'IT Help Desk',  $currentFile, $myOpenHelpdesk ?: null) ?>
    <?= navItem($base.'announcements.php', icon('volume-2',16), 'Announcements', $currentFile) ?>
    <?= navItem($base.'notifications.php', icon('bell',16),     'Notifications', $currentFile, $unread ?: null) ?>

  </nav>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <?= navItem(SITE_URL.'/change_password.php', icon('lock',14), 'Change Password', $currentFile) ?>
    <?= navItem($base.'profile.php',             icon('user',14), 'My Profile',      $currentFile) ?>
    <a href="<?= SITE_URL ?>/logout.php" class="nav-item danger">
      <?= icon('log-out', 14) ?>
      <span>Sign Out</span>
    </a>
  </div>

</aside>

<!-- ── TOPBAR ──────────────────────────────────────────────── -->
<header class="topbar">
  <div class="topbar-left">
    <button class="hamburger" onclick="toggleSidebar()" title="Toggle menu">
      <?= icon('menu', 20) ?>
    </button>
    <div class="page-title-area">
      <div class="page-title"><?= sanitize($pageTitle) ?></div>
      <div class="page-subtitle"><?= sanitize($pageSubtitle) ?></div>
    </div>
  </div>

  <div class="topbar-right">
    <!-- Notifications bell with live dropdown -->
    <div class="notif-wrapper" id="notifWrapper">
      <button class="notif-btn" id="notifBell" title="Notifications" onclick="toggleNotifPanel(event)">
        <?= icon('bell', 18) ?>
        <span class="notif-count" id="notifBadge" style="<?= $unread > 0 ? '' : 'display:none;' ?>"><?= $unread > 9 ? '9+' : $unread ?></span>
      </button>
      <!-- Dropdown panel -->
      <div class="notif-panel" id="notifPanel" style="display:none;">
        <div class="notif-panel-header">
          <span style="font-weight:700;font-size:13px;">Notifications</span>
          <button onclick="markAllRead()" style="background:none;border:none;color:var(--gold);font-size:11px;cursor:pointer;padding:0;">Mark all read</button>
        </div>
        <div class="notif-panel-list" id="notifList">
          <div class="notif-empty">Loading…</div>
        </div>
        <a href="<?= $base ?>notifications.php" class="notif-panel-footer">View all notifications →</a>
      </div>
    </div>

    <!-- User card -->
    <div class="user-card">
      <div class="user-avatar">
        <?php if ($avatarUrl): ?>
        <img src="<?= sanitize($avatarUrl) ?>" alt="avatar"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        <?php else: ?>
        <?= sanitize($initials) ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <strong><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></strong>
        <span><?= sanitize($user['dept_name'] ?? $user['role_name']) ?></span>
      </div>
    </div>
  </div>
</header>

<!-- ── MAIN CONTENT AREA ───────────────────────────────────── -->
<main class="main-content">
<?php
// Show flash message if set
$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;">
  <?= sanitize($flash['message']) ?>
</div>
<?php endif; ?>
