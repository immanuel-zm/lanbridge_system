<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(4);
$pageTitle    = 'Marketing Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

$db         = getDB();
$user       = currentUser();
$uid        = (int)$user['id'];
$deptId     = (int)$user['department_id'];
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');

$approved  = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='approved' AND r.report_date>='$monthStart' AND u.department_id=$deptId")->fetchColumn();
$pending   = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.status='pending' AND u.department_id=$deptId")->fetchColumn();
$myToday   = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date='$today'")->fetchColumn();
$kpisMonth = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid AND submission_date>='$monthStart'")->fetchColumn();

// 14-day trend for this user
$trendLabels = $trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M d', strtotime($d));
    $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE user_id=? AND report_date=?");
    $stmt->execute([$uid, $d]);
    $trendData[] = (int)$stmt->fetchColumn();
}

$team = $db->query("SELECT u.first_name,u.last_name,(SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date='$today') AS submitted_today,(SELECT COUNT(*) FROM reports WHERE user_id=u.id AND status='approved' AND report_date>='$monthStart') AS approved_month FROM users u WHERE u.department_id=$deptId AND u.is_active=1 AND u.id!=$uid ORDER BY u.first_name")->fetchAll();

$recentKpis = $db->prepare("SELECT k.*,c.name AS cat_name FROM kpi_submissions k JOIN kpi_categories c ON k.category_id=c.id WHERE k.user_id=? ORDER BY k.created_at DESC LIMIT 5");
$recentKpis->execute([$uid]);
$recentKpis = $recentKpis->fetchAll();
?>

<div class="dept-banner"><?= icon('megaphone',24) ?><div><div class="dept-banner-name">Marketing &amp; Communications</div><div class="dept-banner-desc">Brand management, campaigns, digital media &amp; public relations</div></div></div>

<div class="stat-grid">
  <div class="stat-card gold"><div class="stat-top"><div><div class="stat-number"><?= $approved ?></div><div class="stat-label">Dept Approved This Month</div></div><div class="stat-icon"><?= icon('check-square',20) ?></div></div></div>
  <div class="stat-card orange"><div class="stat-top"><div><div class="stat-number"><?= $pending ?></div><div class="stat-label">Pending Review</div></div><div class="stat-icon"><?= icon('clock',20) ?></div></div><div class="stat-delta <?= $pending>0?'down':'up' ?>"><?= icon('check-square',12) ?><a href="head_approvals.php" style="color:inherit;"> <?= $pending>0?'Review now':'All clear' ?></a></div></div>
  <div class="stat-card green"><div class="stat-top"><div><div class="stat-number"><?= $kpisMonth ?></div><div class="stat-label">My KPI Entries</div></div><div class="stat-icon"><?= icon('bar-chart',20) ?></div></div></div>
  <div class="stat-card blue"><div class="stat-top"><div><div class="stat-number"><?= $myToday?'✓':'✗' ?></div><div class="stat-label">My Report Today</div></div><div class="stat-icon"><?= icon('file-text',20) ?></div></div><div class="stat-delta <?= $myToday?'up':'down' ?>"><?= $myToday?'Submitted':'Not yet — submit now' ?></div></div>
</div>

<div class="grid-2 mb-24">
  <!-- Trend chart -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('trending-up') ?> My Submission Trend — 14 Days</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="myTrend"></canvas></div></div>
  </div>

  <!-- Quick Actions -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('send') ?> Quick Actions</div></div>
    <div class="card-body">
      <div class="action-grid" style="grid-template-columns:1fr 1fr;">
        <a href="head_approvals.php" class="action-tile"><?= icon('check-square',28) ?><div class="action-tile-label">Review Reports</div><?php if($pending>0):?><div class="action-tile-count"><?= $pending ?> pending</div><?php endif;?></a>
        <a href="submit_report.php"  class="action-tile"><?= icon('send',28) ?><div class="action-tile-label">Daily Report</div></a>
        <a href="submit_kpi.php"     class="action-tile"><?= icon('bar-chart',28) ?><div class="action-tile-label">Submit KPI</div></a>
        <a href="export.php"         class="action-tile"><?= icon('download',28) ?><div class="action-tile-label">Export Data</div></a>
      </div>
    </div>
  </div>
</div>

<div class="grid-2">
  <!-- Recent KPIs -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('bar-chart') ?> My Recent KPIs</div><a href="my_submissions.php" class="btn btn-outline btn-sm">View All</a></div>
    <?php if(empty($recentKpis)):?>
    <div class="card-body"><div class="empty-state"><?= icon('inbox',28) ?><p>No KPIs yet</p><a href="submit_kpi.php" class="btn btn-primary" style="margin-top:12px;">Submit KPI</a></div></div>
    <?php else:?>
    <div class="table-wrap"><table><thead><tr><th>Category</th><th>Quality</th><th>Status</th><th>Date</th></tr></thead><tbody>
    <?php foreach($recentKpis as $k):?>
    <tr><td class="td-bold" style="font-size:12.5px;"><?= sanitize($k['cat_name']) ?></td><td><?= $k['quality_score']?$k['quality_score'].'%':'—' ?></td><td><?= statusBadge($k['status']) ?></td><td class="td-muted"><?= formatDate($k['submission_date'],'M d') ?></td></tr>
    <?php endforeach;?></tbody></table></div>
    <?php endif;?>
  </div>

  <!-- Team -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('users') ?> Marketing Team</div></div>
    <?php if(empty($team)):?>
    <div class="card-body"><div class="empty-state"><?= icon('users',28) ?><p>No other team members yet</p></div></div>
    <?php else:?>
    <div class="table-wrap"><table><thead><tr><th>Staff</th><th>Today</th><th>Approved</th></tr></thead><tbody>
    <?php foreach($team as $m):?>
    <tr>
      <td><div style="display:flex;align-items:center;gap:8px;"><div class="avatar avatar-sm"><?= getInitials($m['first_name'],$m['last_name']) ?></div><span class="td-bold"><?= sanitize($m['first_name'].' '.$m['last_name']) ?></span></div></td>
      <td><?= $m['submitted_today']?'<span class="badge badge-success">✓</span>':'<span class="badge badge-danger">✗</span>' ?></td>
      <td style="font-size:18px;font-weight:700;color:var(--gold);"><?= (int)$m['approved_month'] ?></td>
    </tr>
    <?php endforeach;?></tbody></table></div>
    <?php endif;?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const gc='rgba(255,255,255,0.05)',tc='#9ba3b8';
new Chart(document.getElementById('myTrend'),{type:'line',data:{labels:<?= json_encode($trendLabels) ?>,datasets:[{label:'My Submissions',data:<?= json_encode($trendData) ?>,borderColor:'#c9a84c',backgroundColor:'rgba(201,168,76,0.08)',fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#c9a84c',pointBorderColor:'#0d0f14',pointBorderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}},y:{ticks:{color:tc,font:{size:11}},grid:{color:gc},beginAtZero:true}}}});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
