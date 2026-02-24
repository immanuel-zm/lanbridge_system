<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(1);

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

// Load AI engine and run detection (once per hour via session throttle)
require_once __DIR__ . '/../includes/ai_engine.php';
$aiEngine = new AIEngine($db);
$lastAiRun = $_SESSION['ai_last_run'] ?? 0;
if (time() - $lastAiRun > 3600) {
    $aiEngine->runAll();
    $_SESSION['ai_last_run'] = time();
}
$aiInsights    = $aiEngine->getActiveInsights(null, 5);
$aiAlertCount  = $aiEngine->getInsightCount();

// ── Handle announcement POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title    = trim($_POST['ann_title']    ?? '');
    $body     = trim($_POST['ann_body']     ?? '');
    $audience = $_POST['ann_audience'] === 'all' ? 'all' : 'departments';
    $deptIds  = array_map('intval', $_POST['ann_depts'] ?? []);

    if (strlen($title) < 3 || strlen($body) < 10) {
        setFlash('danger', '❌ Please provide a title (min 3 chars) and message (min 10 chars).');
    } else {
        // Insert announcement
        $stmt = $db->prepare("INSERT INTO announcements (posted_by, title, body, audience) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $title, $body, $audience]);
        $annId = (int)$db->lastInsertId();

        // Attach departments if targeted
        if ($audience === 'departments' && !empty($deptIds)) {
            $deptStmt = $db->prepare("INSERT IGNORE INTO announcement_departments (announcement_id, department_id) VALUES (?, ?)");
            foreach ($deptIds as $deptId) {
                $deptStmt->execute([$annId, $deptId]);
            }
        }

        // Send notifications
        if ($audience === 'all') {
            // Notify every active user
            $users = $db->query("SELECT id FROM users WHERE is_active=1 AND id != $uid")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($users as $userId) {
                sendNotification((int)$userId, '📢 ' . $title, $body, 'info', SITE_URL . '/portals/announcements.php');
            }
            $recipientCount = count($users);
        } else {
            // Notify only users in selected departments
            if (!empty($deptIds)) {
                $inList  = implode(',', $deptIds);
                $users   = $db->query("SELECT id FROM users WHERE is_active=1 AND department_id IN ($inList) AND id != $uid")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($users as $userId) {
                    sendNotification((int)$userId, '📢 ' . $title, $body, 'info', SITE_URL . '/portals/announcements.php');
                }
                $recipientCount = count($users);
            } else {
                $recipientCount = 0;
            }
        }

        logActivity($uid, 'ANNOUNCEMENT_POSTED', 'Announcement: ' . $title . ' | Recipients: ' . $recipientCount);
        setFlash('success', "✅ Announcement posted! Notification sent to {$recipientCount} staff member(s).");
    }
    header('Location: ceo_dashboard.php'); exit;
}

// ── Handle deactivate announcement ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_ann'])) {
    $annId = (int)$_POST['deactivate_ann'];
    $db->prepare("UPDATE announcements SET is_active=0, updated_at=NOW() WHERE id=?")->execute([$annId]);
    setFlash('success', '✅ Announcement removed.');
    header('Location: ceo_dashboard.php'); exit;
}

$pageTitle    = 'Executive Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

// ── Stat data ─────────────────────────────────────────────────
$today      = date('Y-m-d');
$monthStart = date('Y-m-01');

$totalStaff       = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1 AND role_id=(SELECT id FROM roles WHERE slug='staff')")->fetchColumn();
$reportsToday     = (int)$db->query("SELECT COUNT(*) FROM reports WHERE report_date='$today'")->fetchColumn();
$complianceRate   = $totalStaff > 0 ? round(($reportsToday / $totalStaff) * 100) : 0;
$pendingApprovals = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
$deptCount        = (int)$db->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// ── Trend data ────────────────────────────────────────────────
$trendLabels = $trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M d', strtotime($d));
    $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE report_date=?");
    $stmt->execute([$d]);
    $trendData[] = (int)$stmt->fetchColumn();
}

// ── Department performance ────────────────────────────────────
$deptPerf = $db->query(
    "SELECT d.name, d.code,
            COUNT(r.id) AS total,
            SUM(r.status='approved') AS approved
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date >= '$monthStart'
     GROUP BY d.id ORDER BY d.name"
)->fetchAll();

$deptOverview = $db->query(
    "SELECT d.name, d.code,
            COUNT(DISTINCT u.id) AS staff_count,
            COUNT(r.id) AS total_reports,
            SUM(r.status='approved') AS approved,
            ROUND(AVG(k.quality_score),1) AS avg_quality,
            ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,0) AS approval_rate
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date >= '$monthStart'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.submission_date >= '$monthStart'
     GROUP BY d.id ORDER BY approval_rate DESC"
)->fetchAll();

// ── Top performers ────────────────────────────────────────────
$topPerformers = $db->query(
    "SELECT u.first_name, u.last_name, d.code AS dept,
            COUNT(r.id) AS approved,
            ROUND(AVG(k.quality_score),1) AS avg_quality
     FROM users u
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN reports r ON r.user_id=u.id AND r.status='approved' AND r.report_date >= '$monthStart'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.status='approved' AND k.submission_date >= '$monthStart'
     WHERE u.is_active=1 AND u.role_id=(SELECT id FROM roles WHERE slug='staff')
     GROUP BY u.id HAVING approved > 0
     ORDER BY approved DESC, avg_quality DESC LIMIT 5"
)->fetchAll();

// ── Recent submissions ────────────────────────────────────────
$recentSubs = $db->query(
    "SELECT u.first_name, u.last_name, r.report_date, r.status, r.created_at
     FROM reports r JOIN users u ON r.user_id=u.id
     ORDER BY r.created_at DESC LIMIT 8"
)->fetchAll();

// ── Announcements data ────────────────────────────────────────
$allDepts = $db->query("SELECT id, name, code FROM departments ORDER BY name")->fetchAll();

// Active announcements (with dept names if targeted)
$activeAnns = $db->query(
    "SELECT a.*,
            u.first_name AS poster_first, u.last_name AS poster_last,
            GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS dept_names
     FROM announcements a
     JOIN users u ON a.posted_by = u.id
     LEFT JOIN announcement_departments ad ON ad.announcement_id = a.id
     LEFT JOIN departments d ON ad.department_id = d.id
     WHERE a.is_active = 1
     GROUP BY a.id
     ORDER BY a.created_at DESC
     LIMIT 10"
)->fetchAll();
?>

<!-- ── Stat Cards ───────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalStaff ?></div><div class="stat-label">Total Active Staff</div></div>
      <div class="stat-icon"><?= icon('users', 20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('briefcase', 12) ?> All departments combined</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $reportsToday ?></div><div class="stat-label">Reports Today</div></div>
      <div class="stat-icon"><?= icon('file-text', 20) ?></div>
    </div>
    <div class="stat-delta <?= $complianceRate >= 70 ? 'up' : ($complianceRate >= 40 ? '' : 'down') ?>">
      <?= icon('trending-up', 12) ?> <?= $complianceRate ?>% compliance rate today
    </div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= $pendingApprovals ?></div><div class="stat-label">Pending Approvals</div></div>
      <div class="stat-icon"><?= icon('clock', 20) ?></div>
    </div>
    <div class="stat-delta <?= $pendingApprovals > 0 ? 'down' : 'up' ?>">
      <?= icon('check-square', 12) ?>
      <a href="vp_approvals.php" style="color:inherit;"><?= $pendingApprovals > 0 ? 'Click to review' : 'All caught up!' ?></a>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $deptCount ?></div><div class="stat-label">Departments</div></div>
      <div class="stat-icon"><?= icon('layers', 20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('map-pin', 12) ?> Active college departments</div>
  </div>
</div>

<!-- ── Announcements Section ────────────────────────────────── -->
<div class="card mb-24" style="border:1px solid rgba(201,168,76,0.35);">
  <div class="card-header" style="background:linear-gradient(135deg,rgba(201,168,76,0.08),transparent);border-bottom:1px solid rgba(201,168,76,0.2);">
    <div class="card-title" style="color:var(--gold);">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.64a16 16 0 0 0 6 6l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>
      College Announcements
    </div>
    <button class="btn btn-primary btn-sm" onclick="openModal('annModal')">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Announcement
    </button>
  </div>

  <?php if (empty($activeAnns)): ?>
  <div class="card-body">
    <div class="empty-state" style="padding:32px 0;">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.64a16 16 0 0 0 6 6l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>
      <p style="margin-top:10px;">No active announcements. Click <strong>New Announcement</strong> to post one.</p>
    </div>
  </div>
  <?php else: ?>
  <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($activeAnns as $ann): ?>
    <div style="
      background:var(--bg-elevated);
      border:1px solid var(--border);
      border-left:4px solid var(--gold);
      border-radius:var(--radius);
      padding:16px 18px;
      position:relative;
    ">
      <!-- Header row -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px;">
        <div>
          <div style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
            📢 <?= sanitize($ann['title']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if ($ann['audience'] === 'all'): ?>
            <span class="badge badge-gold" style="font-size:10px;">🌐 All Departments</span>
            <?php else: ?>
            <span class="badge badge-info" style="font-size:10px;">
              🏢 <?= sanitize($ann['dept_names'] ?: 'Selected Departments') ?>
            </span>
            <?php endif; ?>
            <span style="font-size:11px;color:var(--text-muted);">
              Posted by <?= sanitize($ann['poster_first'] . ' ' . $ann['poster_last']) ?>
              · <?= timeAgo($ann['created_at']) ?>
            </span>
          </div>
        </div>
        <!-- Remove button -->
        <form method="POST" style="flex-shrink:0;">
          <button type="submit" name="deactivate_ann" value="<?= $ann['id'] ?>"
            class="btn btn-danger btn-sm"
            style="font-size:11px;padding:4px 10px;"
            data-confirm="Remove this announcement? Staff will no longer see it.">
            Remove
          </button>
        </form>
      </div>
      <!-- Body -->
      <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap;"><?= sanitize($ann['body']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── AI Insights Alert Banner ───────────────────────────── -->
<?php if (!empty($aiInsights)): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.30);background:rgba(232,85,106,0.03);">
  <div class="card-header" style="border-bottom:1px solid rgba(232,85,106,0.15);">
    <div class="card-title" style="color:var(--danger);">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      AI Insights — <?= $aiAlertCount ?> Active Alert<?= $aiAlertCount !== 1 ? 's' : '' ?>
    </div>
    <a href="ai_insights.php" class="btn btn-outline btn-sm" style="border-color:var(--danger);color:var(--danger);">Review All</a>
  </div>
  <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($aiInsights as $ins):
      $sev = $ins['severity'];
      $color = $sev === 'critical' ? 'var(--danger)' : ($sev === 'warning' ? 'var(--warning)' : 'var(--info)');
      $icon = $sev === 'critical' ? '🚨' : ($sev === 'warning' ? '⚠️' : 'ℹ️');
    ?>
    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 10px;background:var(--bg-elevated);border-radius:8px;border-left:3px solid <?= $color ?>;">
      <span><?= $icon ?></span>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($ins['title']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= sanitize(substr($ins['description'], 0, 140)) ?>...</div>
      </div>
      <span style="font-size:11px;color:<?= $color ?>;font-weight:700;flex-shrink:0;"><?= $ins['confidence_pct'] ?>% conf.</span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── Charts Row ───────────────────────────────────────────── -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('trending-up') ?> Submission Trend — Last 14 Days</div>
    </div>
    <div class="card-body">
      <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('bar-chart') ?> Department Performance — This Month</div>
    </div>
    <div class="card-body">
      <div class="chart-wrap"><canvas id="deptChart"></canvas></div>
    </div>
  </div>
</div>

<!-- ── Department Overview ──────────────────────────────────── -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> Department Overview — This Month</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Department</th><th>Active Staff</th><th>Total Reports</th><th>Approved</th><th>Avg Quality</th><th>Approval Rate</th></tr>
      </thead>
      <tbody>
        <?php foreach ($deptOverview as $d):
          $rate  = (int)($d['approval_rate'] ?? 0);
          $color = $rate >= 70 ? 'green' : ($rate >= 40 ? 'gold' : 'red');
        ?>
        <tr>
          <td>
            <div class="td-bold"><?= sanitize($d['name']) ?></div>
            <div class="td-muted"><?= sanitize($d['code']) ?></div>
          </td>
          <td class="td-bold"><?= (int)$d['staff_count'] ?></td>
          <td><?= (int)$d['total_reports'] ?></td>
          <td><span class="badge badge-success"><?= (int)$d['approved'] ?></span></td>
          <td><?= $d['avg_quality'] ? $d['avg_quality'] . '%' : '—' ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;min-width:140px;">
              <div class="progress" style="flex:1;">
                <div class="progress-bar <?= $color ?>" style="width:<?= $rate ?>%"></div>
              </div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);flex-shrink:0;"><?= $rate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Bottom Tables ────────────────────────────────────────── -->
<div class="grid-2">
  <!-- Top Performers -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('trending-up') ?> Top Performers — This Month</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Staff Member</th><th>Dept</th><th>Approved</th><th>Avg Quality</th></tr></thead>
        <tbody>
          <?php if (empty($topPerformers)): ?>
          <tr><td colspan="5"><div class="empty-state" style="padding:24px;"><?= icon('inbox', 28) ?><p>No data yet this month</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($topPerformers as $i => $p): ?>
          <tr>
            <td class="td-num"><?= $i + 1 ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="avatar avatar-sm"><?= getInitials($p['first_name'], $p['last_name']) ?></div>
                <span class="td-bold"><?= sanitize($p['first_name'] . ' ' . $p['last_name']) ?></span>
              </div>
            </td>
            <td><span class="badge badge-muted"><?= sanitize($p['dept'] ?? '—') ?></span></td>
            <td><span class="badge badge-success"><?= (int)$p['approved'] ?></span></td>
            <td><?= $p['avg_quality'] ? $p['avg_quality'] . '%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Submissions -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('clock') ?> Recent Submissions</div>
      <a href="vp_approvals.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Staff</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recentSubs)): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:24px;"><?= icon('inbox', 28) ?><p>No submissions yet</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($recentSubs as $r): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="avatar avatar-sm"><?= getInitials($r['first_name'], $r['last_name']) ?></div>
                <span class="td-bold"><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></span>
              </div>
            </td>
            <td>
              <div><?= formatDate($r['report_date'], 'M d, Y') ?></div>
              <div class="td-muted"><?= timeAgo($r['created_at']) ?></div>
            </td>
            <td><?= statusBadge($r['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- NEW ANNOUNCEMENT MODAL                                     -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="annModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.72 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.64 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.64a16 16 0 0 0 6 6l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>
        Post College Announcement
      </div>
      <button class="modal-close" onclick="closeModal('annModal')">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" id="annForm">
      <div class="modal-body">

        <!-- Title -->
        <div class="form-group">
          <label class="form-label">Announcement Title <span style="color:var(--danger);">*</span></label>
          <input type="text" name="ann_title" class="form-control"
            placeholder="e.g. Staff Meeting — Friday 3pm, New Academic Calendar, System Maintenance..."
            required maxlength="255">
        </div>

        <!-- Body -->
        <div class="form-group">
          <label class="form-label">Message <span style="color:var(--danger);">*</span></label>
          <textarea name="ann_body" class="form-control" rows="5"
            placeholder="Write your announcement here. Be clear and concise. Staff will see this message in their notification and on the announcements page."
            data-counter="annBodyCounter" required></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Minimum 10 characters</span>
            <span class="char-counter" id="annBodyCounter">0 chars</span>
          </div>
        </div>

        <!-- Audience selector -->
        <div class="form-group">
          <label class="form-label">Who should see this? <span style="color:var(--danger);">*</span></label>
          <div style="display:flex;gap:12px;margin-bottom:14px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex:1;
              background:var(--bg-elevated);border:2px solid var(--border);border-radius:var(--radius);
              padding:12px 14px;transition:border-color 0.15s;" id="lblAll">
              <input type="radio" name="ann_audience" value="all" id="audAll" checked
                onchange="toggleDeptPicker()" style="accent-color:var(--gold);width:16px;height:16px;">
              <div>
                <div style="font-weight:600;font-size:13px;color:var(--text-primary);">🌐 All Departments</div>
                <div style="font-size:11px;color:var(--text-muted);">Every active staff member</div>
              </div>
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;flex:1;
              background:var(--bg-elevated);border:2px solid var(--border);border-radius:var(--radius);
              padding:12px 14px;transition:border-color 0.15s;" id="lblDepts">
              <input type="radio" name="ann_audience" value="departments" id="audDepts"
                onchange="toggleDeptPicker()" style="accent-color:var(--gold);width:16px;height:16px;">
              <div>
                <div style="font-weight:600;font-size:13px;color:var(--text-primary);">🏢 Select Departments</div>
                <div style="font-size:11px;color:var(--text-muted);">Choose specific departments</div>
              </div>
            </label>
          </div>

          <!-- Department checkboxes (shown only when targeting departments) -->
          <div id="deptPicker" style="display:none;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:14px;">
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
              Select departments to notify:
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
              <?php foreach ($allDepts as $dept): ?>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;
                background:var(--bg-primary);border:1px solid var(--border);border-radius:8px;
                padding:8px 10px;font-size:13px;color:var(--text-primary);
                transition:border-color 0.15s;" class="dept-check-label">
                <input type="checkbox" name="ann_depts[]" value="<?= $dept['id'] ?>"
                  style="accent-color:var(--gold);width:15px;height:15px;"
                  onchange="updateDeptLabel(this.closest('label'))">
                <span>
                  <strong><?= sanitize($dept['code']) ?></strong>
                  <span style="color:var(--text-muted);"> — <?= sanitize($dept['name']) ?></span>
                </span>
              </label>
              <?php endforeach; ?>
            </div>
            <div style="margin-top:10px;">
              <button type="button" onclick="selectAllDepts(true)" class="btn btn-outline btn-sm" style="font-size:11px;padding:4px 10px;">Select All</button>
              <button type="button" onclick="selectAllDepts(false)" class="btn btn-outline btn-sm" style="font-size:11px;padding:4px 10px;margin-left:6px;">Clear All</button>
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div style="background:var(--bg-elevated);border:1px solid rgba(201,168,76,0.2);border-radius:var(--radius);padding:12px 14px;">
          <div style="font-size:11px;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Preview — how it will appear to staff</div>
          <div style="font-size:13px;color:var(--text-muted);">
            📢 <span id="previewTitle" style="color:var(--text-primary);font-weight:600;">Your announcement title</span><br>
            <span id="previewBody" style="line-height:1.6;font-size:12.5px;">Your message will appear here...</span>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('annModal')">Cancel</button>
        <button type="submit" name="post_announcement" value="1" class="btn btn-primary" id="annSubmitBtn">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          Post & Notify
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Charts ────────────────────────────────────────────────────
const gridColor  = 'rgba(255,255,255,0.05)';
const tickColor  = '#9ba3b8';
const baseScales = {
  x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } },
  y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, beginAtZero: true }
};

new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($trendLabels) ?>,
    datasets: [{
      label: 'Submissions',
      data: <?= json_encode($trendData) ?>,
      borderColor: '#c9a84c',
      backgroundColor: 'rgba(201,168,76,0.08)',
      fill: true, tension: 0.4,
      pointRadius: 4, pointBackgroundColor: '#c9a84c',
      pointBorderColor: '#0d0f14', pointBorderWidth: 2,
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: baseScales }
});

new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($deptPerf, 'code')) ?>,
    datasets: [
      { label: 'Approved', data: <?= json_encode(array_map(fn($d) => (int)$d['approved'], $deptPerf)) ?>, backgroundColor: 'rgba(45,212,160,0.7)', borderRadius: 6 },
      { label: 'Total',    data: <?= json_encode(array_map(fn($d) => (int)$d['total'],    $deptPerf)) ?>, backgroundColor: 'rgba(201,168,76,0.25)', borderRadius: 6 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tickColor, font: { size: 11 } } } }, scales: baseScales }
});

// ── Announcement modal logic ──────────────────────────────────
function toggleDeptPicker() {
  const isTargeted = document.getElementById('audDepts').checked;
  document.getElementById('deptPicker').style.display = isTargeted ? 'block' : 'none';
  // Update card border highlight
  document.getElementById('lblAll').style.borderColor   = !isTargeted ? 'var(--gold)' : 'var(--border)';
  document.getElementById('lblDepts').style.borderColor = isTargeted  ? 'var(--gold)' : 'var(--border)';
}

function selectAllDepts(select) {
  document.querySelectorAll('#deptPicker input[type="checkbox"]').forEach(cb => {
    cb.checked = select;
    updateDeptLabel(cb.closest('label'));
  });
}

function updateDeptLabel(label) {
  const cb = label.querySelector('input[type="checkbox"]');
  label.style.borderColor = cb.checked ? 'var(--gold)' : 'var(--border)';
  label.style.background  = cb.checked ? 'rgba(201,168,76,0.08)' : 'var(--bg-primary)';
}

// Live preview
document.querySelector('[name="ann_title"]').addEventListener('input', function() {
  document.getElementById('previewTitle').textContent = this.value || 'Your announcement title';
});
document.querySelector('[name="ann_body"]').addEventListener('input', function() {
  document.getElementById('previewBody').textContent = this.value || 'Your message will appear here...';
});

// Init border on load
document.getElementById('lblAll').style.borderColor = 'var(--gold)';

// Validate before submit
document.getElementById('annForm').addEventListener('submit', function(e) {
  const isTargeted = document.getElementById('audDepts').checked;
  if (isTargeted) {
    const checked = document.querySelectorAll('#deptPicker input[type="checkbox"]:checked');
    if (checked.length === 0) {
      e.preventDefault();
      alert('Please select at least one department, or choose "All Departments".');
    }
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
