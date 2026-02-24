<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireRole(3);

$db         = getDB();
$user       = currentUser();
$uid        = (int)$user['id'];
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');

$aiEngine = new AIEngine($db);
if (empty($_SESSION['ai_vp_run']) || time() - $_SESSION['ai_vp_run'] > 3600) {
    $aiEngine->runAll();
    $_SESSION['ai_vp_run'] = time();
}

$pageTitle    = 'Vice Principal Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

// All departments performance
$deptPerf = $db->query(
    "SELECT d.id, d.name, d.code,
            COUNT(DISTINCT u.id) AS staff_count,
            COUNT(r.id) AS total_reports,
            SUM(r.status='approved') AS approved,
            SUM(r.status='pending') AS pending,
            SUM(r.status='rejected') AS rejected,
            ROUND(AVG(k.quality_score),1) AS avg_quality,
            SUM(CASE WHEN r.report_date='$today' THEN 1 ELSE 0 END) AS submitted_today
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date>='$monthStart'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.submission_date>='$monthStart'
     GROUP BY d.id
     ORDER BY approved DESC"
)->fetchAll();

// Top-level numbers
$totalStaff    = (int)$db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='staff' AND u.is_active=1")->fetchColumn();
$totalPending  = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
$totalApproved = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='approved' AND report_date>='$monthStart'")->fetchColumn();
$totalReports  = (int)$db->query("SELECT COUNT(*) FROM reports WHERE report_date>='$monthStart'")->fetchColumn();
$overallRate   = $totalReports > 0 ? round($totalApproved / $totalReports * 100) : 0;

// AI insights (all depts)
$allInsights = $aiEngine->getActiveInsights(null, 4);
$insightCount = $aiEngine->getInsightCount();

// Scorecards this month
$period = date('Y-m');
$topStaff = $db->query(
    "SELECT ps.overall_score, ps.rank_in_dept, u.first_name, u.last_name, d.name AS dept_name, u.avatar
     FROM performance_scorecards ps
     JOIN users u ON ps.user_id=u.id
     LEFT JOIN departments d ON ps.department_id=d.id
     WHERE ps.period='$period'
     ORDER BY ps.overall_score DESC
     LIMIT 5"
)->fetchAll();
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalStaff ?></div><div class="stat-label">Total Active Staff</div></div>
      <div class="stat-icon"><?= icon('users',20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('briefcase',12) ?> College-wide headcount</div>
  </div>
  <div class="stat-card <?= $totalPending > 0 ? 'orange' : 'green' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalPending ?></div><div class="stat-label">Pending Approvals</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta <?= $totalPending > 0 ? 'down' : 'up' ?>">
      <a href="vp_approvals.php" style="color:inherit;"><?= $totalPending > 0 ? 'Needs review' : 'All caught up!' ?></a>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $overallRate ?>%</div><div class="stat-label">Overall Approval Rate</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta <?= $overallRate >= 70 ? 'up' : 'down' ?>"><?= $totalApproved ?>/<?= $totalReports ?> this month</div>
  </div>
  <div class="stat-card <?= $insightCount > 0 ? '' : 'blue' ?>" style="<?= $insightCount > 0 ? '--card-accent:#e8556a;' : '' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $insightCount ?></div><div class="stat-label">AI Alerts</div></div>
      <div class="stat-icon"><?= icon('zap',20) ?></div>
    </div>
    <div class="stat-delta <?= $insightCount > 0 ? 'down' : 'up' ?>">
      <a href="ai_insights.php" style="color:inherit;"><?= $insightCount > 0 ? 'Review required' : 'No active alerts' ?></a>
    </div>
  </div>
</div>

<!-- AI Alerts -->
<?php if (!empty($allInsights)): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.3);background:rgba(232,85,106,0.03);">
  <div class="card-header" style="border-bottom:1px solid rgba(232,85,106,0.15);">
    <div class="card-title" style="color:var(--danger);"><?= icon('zap',15) ?> AI Intelligence Alerts</div>
    <a href="ai_insights.php" class="btn btn-outline btn-sm" style="border-color:var(--danger);color:var(--danger);">Review All <?= $insightCount ?></a>
  </div>
  <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($allInsights as $ins):
      $col = $ins['severity']==='critical' ? 'var(--danger)' : ($ins['severity']==='warning' ? 'var(--warning)' : 'var(--info)');
      $ico = $ins['severity']==='critical' ? '🚨' : ($ins['severity']==='warning' ? '⚠️' : 'ℹ️');
    ?>
    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 10px;background:var(--bg-elevated);border-radius:8px;border-left:3px solid <?= $col ?>;">
      <span><?= $ico ?></span>
      <div style="flex:1;">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($ins['title']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= sanitize(substr($ins['description'],0,130)) ?>...</div>
      </div>
      <?php if ($ins['dept_name']): ?>
      <span class="badge badge-muted" style="font-size:10px;flex-shrink:0;"><?= sanitize($ins['dept_name']) ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Department Performance Table -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> Department Performance — <?= date('F Y') ?></div>
    <div style="display:flex;gap:8px;">
      <a href="vp_approvals.php" class="btn btn-primary btn-sm"><?= icon('check-square',13) ?> Approve Reports</a>
      <a href="scorecards.php" class="btn btn-outline btn-sm"><?= icon('award',13) ?> Scorecards</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Department</th>
          <th>Staff</th>
          <th>Submitted Today</th>
          <th>Month Approved</th>
          <th>Pending</th>
          <th>Avg Quality</th>
          <th>Approval Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deptPerf as $d):
          $dRate = (int)$d['total_reports'] > 0 ? round((int)$d['approved'] / (int)$d['total_reports'] * 100) : 0;
          $rateColor = $dRate >= 70 ? 'var(--success)' : ($dRate >= 40 ? 'var(--warning)' : 'var(--danger)');
        ?>
        <tr>
          <td>
            <div class="td-bold"><?= sanitize($d['name']) ?></div>
            <span class="badge badge-muted" style="font-size:10px;"><?= sanitize($d['code']) ?></span>
          </td>
          <td><?= (int)$d['staff_count'] ?></td>
          <td>
            <span style="font-weight:700;color:<?= (int)$d['submitted_today']>0?'var(--success)':'var(--text-muted)' ?>;">
              <?= (int)$d['submitted_today'] ?>
            </span>
            <span class="td-muted">/<?= (int)$d['staff_count'] ?></span>
          </td>
          <td><span style="font-weight:700;color:var(--success);"><?= (int)$d['approved'] ?></span></td>
          <td>
            <?php if ((int)$d['pending'] > 0): ?>
            <span class="badge badge-warning"><?= (int)$d['pending'] ?></span>
            <?php else: ?>
            <span class="badge badge-success">0</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600;color:<?= $d['avg_quality'] ? ((float)$d['avg_quality']>=70?'var(--success)':'var(--warning)') : 'var(--text-muted)' ?>;">
            <?= $d['avg_quality'] ? $d['avg_quality'].'%' : '—' ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:110px;">
              <div class="progress" style="flex:1;">
                <div class="progress-bar <?= $dRate>=70?'green':($dRate>=40?'gold':'red') ?>" style="width:<?= $dRate ?>%;"></div>
              </div>
              <span style="font-size:12px;font-weight:700;color:<?= $rateColor ?>;flex-shrink:0;"><?= $dRate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Cross-Department Comparison Charts -->
<?php
// Prepare chart data from $deptPerf
$chartLabels   = [];
$chartApproval = [];
$chartQuality  = [];
$chartSubmitted = [];
foreach ($deptPerf as $dp) {
    if ((int)$dp['staff_count'] === 0) continue;
    $chartLabels[]    = $dp['name'];
    $dRate = (int)$dp['total_reports'] > 0 ? round((int)$dp['approved'] / (int)$dp['total_reports'] * 100) : 0;
    $chartApproval[]  = $dRate;
    $chartQuality[]   = round((float)($dp['avg_quality'] ?? 0), 1);
    $todayPct = (int)$dp['staff_count'] > 0 ? round((int)$dp['submitted_today'] / (int)$dp['staff_count'] * 100) : 0;
    $chartSubmitted[] = $todayPct;
}
?>
<div class="grid-2 mb-24">

  <!-- Approval Rate by Department -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('trending-up') ?> Approval Rate by Department</div>
      <span class="badge badge-muted"><?= date('F Y') ?></span>
    </div>
    <div class="card-body" style="padding:16px;">
      <canvas id="approvalChart" height="220"></canvas>
    </div>
  </div>

  <!-- Avg KPI Quality by Department -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('bar-chart') ?> Avg KPI Quality by Department</div>
      <span class="badge badge-muted"><?= date('F Y') ?></span>
    </div>
    <div class="card-body" style="padding:16px;">
      <canvas id="qualityChart" height="220"></canvas>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
  const labels   = <?= json_encode($chartLabels) ?>;
  const approval = <?= json_encode($chartApproval) ?>;
  const quality  = <?= json_encode($chartQuality) ?>;

  const baseOpts = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.parsed.y + '%' } } },
    scales: {
      x: { ticks: { color: '#9ba3b8', font: { size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { min: 0, max: 100, ticks: { color: '#9ba3b8', font: { size: 11 }, callback: v => v + '%' }, grid: { color: 'rgba(255,255,255,0.06)' } }
    }
  };

  // Approval chart
  new Chart(document.getElementById('approvalChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: approval,
        backgroundColor: approval.map(v => v >= 70 ? 'rgba(45,212,160,0.7)' : v >= 40 ? 'rgba(245,166,35,0.7)' : 'rgba(232,85,106,0.7)'),
        borderColor:      approval.map(v => v >= 70 ? '#2dd4a0' : v >= 40 ? '#f5a623' : '#e8556a'),
        borderWidth: 2,
        borderRadius: 5,
      }]
    },
    options: { ...baseOpts, plugins: { ...baseOpts.plugins,
      tooltip: { callbacks: { label: ctx => 'Approval: ' + ctx.parsed.y + '%' } } } }
  });

  // Quality chart
  new Chart(document.getElementById('qualityChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: quality,
        backgroundColor: quality.map(v => v >= 75 ? 'rgba(74,158,219,0.7)' : v >= 50 ? 'rgba(201,168,76,0.7)' : 'rgba(156,163,184,0.4)'),
        borderColor:      quality.map(v => v >= 75 ? '#4a9edb' : v >= 50 ? '#c9a84c' : '#9ba3b8'),
        borderWidth: 2,
        borderRadius: 5,
      }]
    },
    options: { ...baseOpts, plugins: { ...baseOpts.plugins,
      tooltip: { callbacks: { label: ctx => 'Avg Quality: ' + ctx.parsed.y + '%' } } },
      scales: { ...baseOpts.scales, y: { ...baseOpts.scales.y,
        ticks: { ...baseOpts.scales.y.ticks, callback: v => v + '/100' },
        tooltip: { callbacks: { label: ctx => ctx.parsed.y + '/100' } }
      } }
    }
  });
})();
</script>

<!-- Low performer detection for VP (once per day) -->
<?php
$vpLowPerfKey = 'vp_low_perf_' . date('Y-m-d');
if (empty($_SESSION[$vpLowPerfKey])) {
    $_SESSION[$vpLowPerfKey] = true;
    $allStaff = $db->query("SELECT u.id, u.first_name, u.last_name, u.department_id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='staff' AND u.is_active=1")->fetchAll();
    foreach ($allStaff as $st) {
        $missed = 0;
        $ck = strtotime('-1 day');
        for ($i = 0; $i < 10 && $missed < 3; $i++) {
            $dow3 = (int)date('N', $ck);
            if ($dow3 >= 6) { $ck = strtotime('-1 day', $ck); continue; }
            $ds3 = date('Y-m-d', $ck);
            $has = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id={$st['id']} AND report_date='$ds3'")->fetchColumn();
            if (!$has) $missed++; else break;
            $ck = strtotime('-1 day', $ck);
        }
        if ($missed >= 3) {
            // Notify dept head
            $deptHead = $db->query("SELECT id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='dept_head' AND u.department_id={$st['department_id']} AND u.is_active=1 LIMIT 1")->fetchColumn();
            if ($deptHead) {
                $existsNotif = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id=$deptHead AND title='Low Submission Alert' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND message LIKE '%{$st['first_name']}%'")->fetchColumn();
                if (!$existsNotif) {
                    sendNotification($deptHead, 'Low Submission Alert',
                        sanitize($st['first_name'].' '.$st['last_name']).' has not submitted a report for '.$missed.'+ consecutive weekdays.',
                        'warning', SITE_URL.'/portals/vp_approvals.php');
                }
            }
        }
    }
}
?>

<!-- Top Performers + Quick Actions -->
<div class="grid-2">

  <!-- Top Performers -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('award') ?> Top Performers — <?= date('F Y') ?></div>
      <a href="scorecards.php" class="btn btn-outline btn-sm">Full Scorecards</a>
    </div>
    <?php if (empty($topStaff)): ?>
    <div class="card-body">
      <div class="empty-state" style="padding:24px;"><?= icon('award',28) ?><p>Generate scorecards to see top performers</p></div>
    </div>
    <?php else: ?>
    <div class="card-body" style="padding:12px 16px;">
      <?php foreach ($topStaff as $i => $s): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
        <div style="font-size:20px;width:28px;text-align:center;flex-shrink:0;">
          <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'#'.($i+1))) ?>
        </div>
        <?php if (!empty($s['avatar'])): ?>
        <img src="<?= sanitize(SITE_URL.'/'.ltrim($s['avatar'],'/')) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="">
        <?php else: ?>
        <div class="avatar avatar-sm"><?= getInitials($s['first_name'],$s['last_name']) ?></div>
        <?php endif; ?>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></div>
          <div style="font-size:11px;color:var(--text-muted);"><?= sanitize($s['dept_name']) ?></div>
        </div>
        <div style="font-size:18px;font-weight:800;color:<?= (float)$s['overall_score']>=80?'var(--success)':((float)$s['overall_score']>=60?'var(--warning)':'var(--danger)') ?>;">
          <?= number_format((float)$s['overall_score'],0) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Quick Actions -->
  <div>
    <div class="card">
      <div class="card-header"><div class="card-title"><?= icon('zap') ?> Quick Actions</div></div>
      <div class="card-body" style="padding:12px;">
        <?php
        $actions = [
          ['vp_approvals.php',  'check-square', 'Approve Pending Reports',    $totalPending > 0 ? $totalPending.' pending' : null],
          ['ai_insights.php',   'zap',          'AI Insights & Fraud Alerts', $insightCount > 0 ? $insightCount.' alerts' : null],
          ['scorecards.php',    'award',         'Staff Performance Scorecards', null],
          ['reports.php',       'file-text',    'Performance Reports',        null],
          ['manage_users.php',  'users',        'Manage Staff',               null],
          ['export.php',        'download',     'Export Data',                null],
        ];
        foreach ($actions as [$href, $ic, $label, $badge]):
        ?>
        <a href="<?= $href ?>" style="display:flex;align-items:center;gap:10px;padding:10px 8px;border-radius:8px;color:var(--text-primary);text-decoration:none;border-bottom:1px solid var(--border);transition:background 0.15s;" onmouseover="this.style.background='var(--bg-elevated)'" onmouseout="this.style.background=''">
          <span style="color:var(--gold);"><?= icon($ic,16) ?></span>
          <span style="font-size:13px;font-weight:500;flex:1;"><?= $label ?></span>
          <?php if ($badge): ?>
          <span class="badge badge-warning" style="font-size:10px;"><?= $badge ?></span>
          <?php endif; ?>
          <?= icon('chevron-right',13) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
