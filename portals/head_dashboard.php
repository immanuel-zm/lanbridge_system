<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireRole(6);

$db         = getDB();
$user       = currentUser();
$uid        = (int)$user['id'];
$deptId     = (int)$user['department_id'];
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');

// Run AI detection for this dept (throttled to once per hour)
$aiEngine = new AIEngine($db);
$sessKey  = 'ai_dept_run_' . $deptId;
if (empty($_SESSION[$sessKey]) || time() - $_SESSION[$sessKey] > 3600) {
    $aiEngine->runAll();
    $_SESSION[$sessKey] = time();
}

$pageTitle    = 'Department Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

// Dept info
$dept = $db->query("SELECT * FROM departments WHERE id=$deptId")->fetch();

// Core metrics
$teamCount     = (int)$db->query("SELECT COUNT(*) FROM users WHERE department_id=$deptId AND is_active=1 AND role_id=(SELECT id FROM roles WHERE slug='staff')")->fetchColumn();
$approvedMonth = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='approved' AND r.report_date>='$monthStart' AND u.department_id=$deptId")->fetchColumn();
$pendingCount  = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='pending' AND u.department_id=$deptId")->fetchColumn();
$rejectedMonth = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='rejected' AND r.report_date>='$monthStart' AND u.department_id=$deptId")->fetchColumn();
$totalMonth    = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date>='$monthStart' AND u.department_id=$deptId")->fetchColumn();
$avgQuality    = (float)($db->query("SELECT ROUND(AVG(k.quality_score),1) FROM kpi_submissions k JOIN users u ON k.user_id=u.id WHERE u.department_id=$deptId AND k.submission_date>='$monthStart' AND k.quality_score IS NOT NULL")->fetchColumn() ?? 0);
$approvalRate  = $totalMonth > 0 ? round(($approvedMonth / $totalMonth) * 100) : 0;
$kpiMonth      = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions k JOIN users u ON k.user_id=u.id WHERE u.department_id=$deptId AND k.submission_date>='$monthStart'")->fetchColumn();

$submittedToday = (int)$db->query("SELECT COUNT(DISTINCT r.user_id) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date='$today' AND u.department_id=$deptId")->fetchColumn();

// AI insights for this dept
$deptInsights = $aiEngine->getActiveInsights($deptId, 3);

// Team members
$team = $db->query(
    "SELECT u.id, u.first_name, u.last_name, u.position, u.avatar,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date='$today') AS submitted_today,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date>='$monthStart') AS total_month,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date>='$monthStart' AND status='approved') AS approved_month,
            (SELECT ROUND(AVG(quality_score),0) FROM kpi_submissions WHERE user_id=u.id AND submission_date>='$monthStart' AND quality_score IS NOT NULL) AS avg_quality
     FROM users u
     WHERE u.department_id=$deptId AND u.is_active=1 AND u.id != $uid
     ORDER BY submitted_today DESC, u.first_name"
)->fetchAll();

// KPI Leaderboard — team ranked by avg quality this month
$leaderboard = $db->query(
    "SELECT u.id, u.first_name, u.last_name, u.avatar, u.position,
            COUNT(k.id) AS kpi_count,
            ROUND(AVG(k.quality_score),1) AS avg_quality,
            SUM(k.status='approved') AS kpi_approved,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date>='$monthStart') AS report_count,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date>='$monthStart' AND status='approved') AS report_approved
     FROM users u
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.submission_date>='$monthStart' AND k.quality_score IS NOT NULL
     WHERE u.department_id=$deptId AND u.is_active=1 AND u.id != $uid
     GROUP BY u.id
     ORDER BY avg_quality DESC, kpi_count DESC"
)->fetchAll();

// Low performer detection — staff missing 3+ consecutive weekdays
$lowPerformerKey = 'low_perf_notif_' . date('Y-m-d') . '_dept_' . $deptId;
if (empty($_SESSION[$lowPerformerKey])) {
    $_SESSION[$lowPerformerKey] = true;
    foreach ($team as $tm) {
        $missed = 0;
        $checkDate = strtotime('-1 day');
        while ($missed < 3) {
            $dow2 = (int)date('N', $checkDate);
            if ($dow2 >= 6) { $checkDate = strtotime('-1 day', $checkDate); continue; }
            $ds2 = date('Y-m-d', $checkDate);
            $hasSub = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id={$tm['id']} AND report_date='$ds2'")->fetchColumn();
            if (!$hasSub) $missed++;
            else break;
            $checkDate = strtotime('-1 day', $checkDate);
        }
        if ($missed >= 3) {
            $existing = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id=$uid AND title='Low Submission Alert' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND message LIKE '%{$tm['first_name']}%'")->fetchColumn();
            if (!$existing) {
                sendNotification($uid, 'Low Submission Alert',
                    sanitize($tm['first_name'].' '.$tm['last_name']).' has missed '.$missed.'+ consecutive weekdays without submitting a report.',
                    'warning', SITE_URL.'/portals/head_approvals.php');
            }
        }
    }
}

$trend = [];
$d = strtotime('-10 days');
while (count($trend) < 7 && $d <= time()) {
    if ((int)date('N', $d) < 6) {
        $ds  = date('Y-m-d', $d);
        $cnt = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date='$ds' AND u.department_id=$deptId")->fetchColumn();
        $trend[] = ['date' => $ds, 'label' => date('D d', $d), 'count' => $cnt];
    }
    $d = strtotime('+1 day', $d);
}
?>

<!-- Dept Banner -->
<div class="dept-banner">
  <?= icon('layers', 24) ?>
  <div>
    <div class="dept-banner-name"><?= sanitize($dept['name'] ?? 'My Department') ?></div>
    <div class="dept-banner-desc"><?= sanitize($dept['description'] ?? '') ?></div>
  </div>
  <div style="margin-left:auto;display:flex;gap:8px;">
    <a href="head_approvals.php" class="btn btn-primary btn-sm">
      <?= icon('check-square',13) ?> Review
      <?php if ($pendingCount): ?>
      <span class="badge badge-danger" style="margin-left:4px;"><?= $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <a href="scorecards.php" class="btn btn-outline btn-sm"><?= icon('award',13) ?> Scorecards</a>
  </div>
</div>

<!-- AI Alerts -->
<?php if (!empty($deptInsights)): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.3);background:rgba(232,85,106,0.03);">
  <div class="card-header" style="border-bottom:1px solid rgba(232,85,106,0.15);">
    <div class="card-title" style="color:var(--danger);">
      <?= icon('alert-triangle',15) ?> <?= count($deptInsights) ?> AI Alert<?= count($deptInsights) !== 1 ? 's' : '' ?> — Your Department
    </div>
    <a href="ai_insights.php?dept=<?= $deptId ?>" class="btn btn-outline btn-sm" style="border-color:var(--danger);color:var(--danger);">Review All</a>
  </div>
  <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($deptInsights as $ins):
      $col = $ins['severity'] === 'critical' ? 'var(--danger)' : ($ins['severity'] === 'warning' ? 'var(--warning)' : 'var(--info)');
      $ico = $ins['severity'] === 'critical' ? '🚨' : ($ins['severity'] === 'warning' ? '⚠️' : 'ℹ️');
    ?>
    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 10px;background:var(--bg-elevated);border-radius:8px;border-left:3px solid <?= $col ?>;">
      <span><?= $ico ?></span>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($ins['title']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= sanitize(substr($ins['description'],0,120)) ?>...</div>
      </div>
      <span style="font-size:11px;color:<?= $col ?>;font-weight:700;flex-shrink:0;"><?= $ins['confidence_pct'] ?>%</span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $teamCount ?></div><div class="stat-label">Team Members</div></div>
      <div class="stat-icon"><?= icon('users',20) ?></div>
    </div>
    <div class="stat-delta"><?= $submittedToday ?>/<?= $teamCount ?> submitted today</div>
  </div>
  <div class="stat-card <?= $pendingCount > 0 ? 'orange' : 'green' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $pendingCount ?></div><div class="stat-label">Awaiting Review</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta <?= $pendingCount > 0 ? 'down' : 'up' ?>">
      <a href="head_approvals.php" style="color:inherit;"><?= $pendingCount > 0 ? 'Action required' : 'All reviewed!' ?></a>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $approvalRate ?>%</div><div class="stat-label">Approval Rate</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta <?= $approvalRate >= 70 ? 'up' : 'down' ?>"><?= $approvedMonth ?>/<?= $totalMonth ?> approved this month</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $avgQuality ?: '—' ?></div><div class="stat-label">Avg KPI Quality</div></div>
      <div class="stat-icon"><?= icon('bar-chart',20) ?></div>
    </div>
    <div class="stat-delta"><?= $kpiMonth ?> KPI entries this month</div>
  </div>
</div>

<!-- Main Grid -->
<div class="grid-2 mb-24">

  <!-- Team Status -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('users') ?> Team — Today's Status</div>
      <span class="badge <?= $submittedToday >= $teamCount ? 'badge-success' : 'badge-warning' ?>"><?= $submittedToday ?>/<?= $teamCount ?></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Today</th><th>Month</th><th>Quality</th></tr></thead>
        <tbody>
          <?php if (empty($team)): ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:20px;"><?= icon('users',24) ?><p>No team members</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($team as $m):
            $mRate = (int)$m['total_month'] > 0 ? round((int)$m['approved_month'] / (int)$m['total_month'] * 100) : 0;
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <?php if (!empty($m['avatar'])): ?>
                <img src="<?= sanitize(SITE_URL.'/'.ltrim($m['avatar'],'/')) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;" alt="">
                <?php else: ?>
                <div class="avatar avatar-sm"><?= getInitials($m['first_name'],$m['last_name']) ?></div>
                <?php endif; ?>
                <span class="td-bold" style="font-size:12.5px;"><?= sanitize($m['first_name'].' '.$m['last_name']) ?></span>
              </div>
            </td>
            <td>
              <?= $m['submitted_today']
                ? '<span class="badge badge-success" style="font-size:10px;">✓ Done</span>'
                : '<span class="badge badge-danger" style="font-size:10px;">✗ Missing</span>' ?>
            </td>
            <td>
              <span style="font-size:13px;font-weight:600;"><?= $m['approved_month'] ?></span>
              <span class="td-muted">/<?= $m['total_month'] ?></span>
              <div style="width:50px;height:3px;background:var(--bg-elevated);border-radius:2px;margin-top:3px;">
                <div style="height:100%;width:<?= $mRate ?>%;background:<?= $mRate>=70?'var(--success)':($mRate>=40?'var(--warning)':'var(--danger)') ?>;border-radius:2px;"></div>
              </div>
            </td>
            <td style="font-weight:700;font-size:13px;color:<?= $m['avg_quality'] ? ((float)$m['avg_quality']>=75?'var(--success)':'var(--warning)') : 'var(--text-muted)' ?>;">
              <?= $m['avg_quality'] ? $m['avg_quality'].'%' : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Submission trend -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('trending-up') ?> 7-Day Submission Trend</div>
    </div>
    <div class="card-body">
      <?php foreach (array_reverse($trend) as $t):
        $pct    = $teamCount > 0 ? min(100, round($t['count'] / $teamCount * 100)) : 0;
        $col    = $pct >= 80 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)');
        $isToday = ($t['date'] === $today);
      ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span style="font-size:11px;width:52px;flex-shrink:0;font-weight:<?= $isToday?'700':'400' ?>;color:<?= $isToday?'var(--gold)':'var(--text-muted)' ?>;">
          <?= $t['label'] ?><?= $isToday?' ★':'' ?>
        </span>
        <div style="flex:1;height:18px;background:var(--bg-elevated);border-radius:4px;overflow:hidden;">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:4px;"></div>
        </div>
        <span style="font-size:12px;font-weight:600;color:<?= $col ?>;width:28px;text-align:right;"><?= $t['count'] ?></span>
      </div>
      <?php endforeach; ?>
      <div style="font-size:11px;color:var(--text-muted);text-align:center;padding-top:8px;border-top:1px solid var(--border);margin-top:4px;">
        Max: <?= $teamCount ?> staff per day
      </div>
    </div>
  </div>
</div>

<!-- Month Summary -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('bar-chart') ?> <?= date('F Y') ?> Summary</div>
    <div style="display:flex;gap:8px;">
      <a href="reports.php" class="btn btn-outline btn-sm"><?= icon('file-text',13) ?> Reports</a>
      <a href="export.php" class="btn btn-outline btn-sm"><?= icon('download',13) ?> Export</a>
    </div>
  </div>
  <div class="card-body">
    <div class="metric-boxes" style="grid-template-columns:repeat(5,1fr);">
      <div class="metric-box"><div class="metric-box-num"><?= $totalMonth ?></div><div class="metric-box-label">Submitted</div></div>
      <div class="metric-box"><div class="metric-box-num" style="color:var(--success);"><?= $approvedMonth ?></div><div class="metric-box-label">Approved</div></div>
      <div class="metric-box"><div class="metric-box-num" style="color:var(--warning);"><?= $pendingCount ?></div><div class="metric-box-label">Pending</div></div>
      <div class="metric-box"><div class="metric-box-num" style="color:var(--danger);"><?= $rejectedMonth ?></div><div class="metric-box-label">Rejected</div></div>
      <div class="metric-box"><div class="metric-box-num" style="color:var(--info);"><?= $kpiMonth ?></div><div class="metric-box-label">KPI Entries</div></div>
    </div>
    <div style="margin-top:14px;">
      <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
        <span style="color:var(--text-muted);">Approval Rate</span>
        <span style="font-weight:700;color:<?= $approvalRate>=70?'var(--success)':($approvalRate>=40?'var(--warning)':'var(--danger)') ?>;"><?= $approvalRate ?>%</span>
      </div>
      <div class="progress"><div class="progress-bar <?= $approvalRate>=70?'green':($approvalRate>=40?'gold':'red') ?>" style="width:<?= $approvalRate ?>%"></div></div>
    </div>
  </div>
</div>

<!-- KPI Quality Leaderboard -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('award') ?> Team KPI Leaderboard — <?= date('F Y') ?></div>
    <span class="badge badge-muted"><?= count($leaderboard) ?> members</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:48px;">Rank</th>
          <th>Staff Member</th>
          <th>Avg Quality</th>
          <th>KPI Entries</th>
          <th>Report Rate</th>
          <th>Quality Bar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($leaderboard)): ?>
        <tr><td colspan="6">
          <div class="empty-state" style="padding:28px;">
            <?= icon('bar-chart',28) ?>
            <p style="font-size:12px;">No KPI data submitted this month yet</p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($leaderboard as $rank => $lm):
          $medal = $rank === 0 ? '🥇' : ($rank === 1 ? '🥈' : ($rank === 2 ? '🥉' : '#'.($rank+1)));
          $q = (float)($lm['avg_quality'] ?? 0);
          $qColor = $q >= 80 ? 'var(--success)' : ($q >= 60 ? 'var(--warning)' : ($q > 0 ? 'var(--danger)' : 'var(--text-muted)'));
          $rRate = (int)$lm['report_count'] > 0 ? round((int)$lm['report_approved'] / (int)$lm['report_count'] * 100) : 0;
        ?>
        <tr>
          <td style="text-align:center;font-size:<?= $rank < 3 ? '18' : '13' ?>px;font-weight:700;"><?= $medal ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:9px;">
              <?= avatarHtml($lm, 'sm') ?>
              <div>
                <div class="td-bold"><?= sanitize($lm['first_name'].' '.$lm['last_name']) ?></div>
                <div class="td-muted" style="font-size:11px;"><?= sanitize($lm['position'] ?? '') ?></div>
              </div>
            </div>
          </td>
          <td>
            <span style="font-size:18px;font-weight:800;color:<?= $qColor ?>;"><?= $q ?: '—' ?></span>
            <?php if ($q): ?><span style="font-size:11px;color:var(--text-muted);">/100</span><?php endif; ?>
          </td>
          <td>
            <span style="font-weight:700;"><?= (int)$lm['kpi_count'] ?></span>
            <span class="td-muted"> entries</span>
            <?php if ((int)$lm['kpi_approved'] > 0): ?>
            <div class="td-muted" style="font-size:10px;"><?= (int)$lm['kpi_approved'] ?> approved</div>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-size:13px;font-weight:600;color:<?= $rRate>=70?'var(--success)':($rRate>=40?'var(--warning)':'var(--danger)') ?>;"><?= $rRate ?>%</span>
            <div class="td-muted" style="font-size:11px;"><?= (int)$lm['report_approved'] ?>/<?= (int)$lm['report_count'] ?> approved</div>
          </td>
          <td style="min-width:100px;">
            <div style="background:var(--bg-elevated);border-radius:4px;height:8px;overflow:hidden;">
              <div style="height:100%;width:<?= $q ?>%;background:<?= $qColor ?>;border-radius:4px;transition:width .4s;"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Actions -->
<div class="action-grid">
  <a href="head_approvals.php" class="action-tile">
    <?= icon('check-square',28) ?>
    <div class="action-tile-label">Review Reports</div>
    <?php if ($pendingCount): ?><div class="action-tile-count"><?= $pendingCount ?> pending</div><?php endif; ?>
  </a>
  <a href="reports.php" class="action-tile">
    <?= icon('file-text',28) ?>
    <div class="action-tile-label">Performance Reports</div>
  </a>
  <a href="scorecards.php" class="action-tile">
    <?= icon('award',28) ?>
    <div class="action-tile-label">Staff Scorecards</div>
  </a>
  <a href="ai_insights.php?dept=<?= $deptId ?>" class="action-tile">
    <?= icon('zap',28) ?>
    <div class="action-tile-label">AI Insights</div>
    <?php if (!empty($deptInsights)): ?><div class="action-tile-count"><?= count($deptInsights) ?> alerts</div><?php endif; ?>
  </a>
  <a href="tasks.php" class="action-tile">
    <?= icon('clipboard',28) ?>
    <div class="action-tile-label">Tasks</div>
  </a>
  <a href="manage_users.php" class="action-tile">
    <?= icon('users',28) ?>
    <div class="action-tile-label">Manage Team</div>
  </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
