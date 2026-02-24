<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(2);
$pageTitle    = 'Principal Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

$db         = getDB();
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');

// College-wide stats
$totalStaff     = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active=1 AND role_id=(SELECT id FROM roles WHERE slug='staff')")->fetchColumn();
$reportsToday   = (int)$db->query("SELECT COUNT(*) FROM reports WHERE report_date='$today'")->fetchColumn();
$pendingAll     = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();
$approvalRate   = $db->query("SELECT ROUND(SUM(status='approved')/NULLIF(COUNT(*),0)*100,0) FROM reports WHERE report_date>='$monthStart'")->fetchColumn() ?? 0;
$compliance     = $totalStaff > 0 ? round(($reportsToday / $totalStaff) * 100) : 0;

// All departments overview
$deptOverview = $db->query(
    "SELECT d.name, d.code,
            COUNT(DISTINCT u.id) AS staff_count,
            SUM(r.status='approved') AS approved,
            SUM(r.status='pending')  AS pending,
            SUM(r.status='rejected') AS rejected,
            ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,0) AS rate
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date>='$monthStart'
     GROUP BY d.id ORDER BY rate DESC"
)->fetchAll();

// 14-day trend
$trendLabels = $trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M d', strtotime($d));
    $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE report_date=?");
    $stmt->execute([$d]);
    $trendData[] = (int)$stmt->fetchColumn();
}

// Senior staff (managers & above)
$seniorStaff = $db->query(
    "SELECT u.first_name, u.last_name, r.name AS role_name, d.name AS dept_name,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date='$today') AS submitted_today,
            (SELECT COUNT(*) FROM reports WHERE user_id=u.id AND status='approved' AND report_date>='$monthStart') AS approved_month
     FROM users u
     JOIN roles r ON u.role_id=r.id
     LEFT JOIN departments d ON u.department_id=d.id
     WHERE u.is_active=1 AND r.level BETWEEN 4 AND 6
     ORDER BY r.level, u.first_name"
)->fetchAll();

// Recent reports
$recentReports = $db->query(
    "SELECT r.*, u.first_name, u.last_name, d.code AS dept_code
     FROM reports r JOIN users u ON r.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     ORDER BY r.created_at DESC LIMIT 8"
)->fetchAll();
?>

<!-- Stats -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top"><div><div class="stat-number"><?= $totalStaff ?></div><div class="stat-label">Total Staff</div></div><div class="stat-icon"><?= icon('users',20) ?></div></div>
    <div class="stat-delta"><?= icon('briefcase',12) ?> College-wide</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top"><div><div class="stat-number"><?= $reportsToday ?></div><div class="stat-label">Reports Today</div></div><div class="stat-icon"><?= icon('file-text',20) ?></div></div>
    <div class="stat-delta <?= $compliance>=70?'up':($compliance>=40?'':'down') ?>"><?= icon('trending-up',12) ?> <?= $compliance ?>% compliance</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top"><div><div class="stat-number"><?= $pendingAll ?></div><div class="stat-label">Pending Approvals</div></div><div class="stat-icon"><?= icon('clock',20) ?></div></div>
    <div class="stat-delta <?= $pendingAll>0?'down':'up' ?>"><?= icon('check-square',12) ?> <a href="vp_approvals.php" style="color:inherit;"><?= $pendingAll>0?'Review now':'All clear' ?></a></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top"><div><div class="stat-number"><?= $approvalRate ?>%</div><div class="stat-label">Monthly Approval Rate</div></div><div class="stat-icon"><?= icon('bar-chart',20) ?></div></div>
    <div class="stat-delta <?= $approvalRate>=70?'up':($approvalRate>=40?'':'down') ?>"><?= icon('trending-up',12) ?> This month</div>
  </div>
</div>

<!-- Charts -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('trending-up') ?> Submission Trend — 14 Days</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="trendChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('layers') ?> Department Performance</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="deptChart"></canvas></div></div>
  </div>
</div>

<!-- Department Table -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> All Departments — This Month</div>
    <a href="reports.php" class="btn btn-outline btn-sm">Full Report</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Department</th><th>Staff</th><th>Approved</th><th>Pending</th><th>Rejected</th><th>Rate</th></tr></thead>
      <tbody>
        <?php foreach ($deptOverview as $d):
          $rate  = (int)($d['rate']??0);
          $color = $rate>=70?'green':($rate>=40?'gold':'red');
        ?>
        <tr>
          <td><div class="td-bold"><?= sanitize($d['name']) ?></div><div class="td-muted"><?= sanitize($d['code']) ?></div></td>
          <td><?= (int)$d['staff_count'] ?></td>
          <td><span class="badge badge-success"><?= (int)$d['approved'] ?></span></td>
          <td><span class="badge badge-warning"><?= (int)$d['pending'] ?></span></td>
          <td><span class="badge badge-danger"><?= (int)$d['rejected'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
              <div class="progress" style="flex:1;"><div class="progress-bar <?= $color ?>" style="width:<?= $rate ?>%"></div></div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);"><?= $rate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Senior Staff + Recent -->
<div class="grid-2">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('star') ?> Senior Staff Overview</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Role</th><th>Today</th><th>Approved</th></tr></thead>
        <tbody>
          <?php foreach ($seniorStaff as $s): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="avatar avatar-sm"><?= getInitials($s['first_name'],$s['last_name']) ?></div>
                <span class="td-bold"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></span>
              </div>
            </td>
            <td><span class="badge badge-muted" style="font-size:10px;"><?= sanitize($s['role_name']) ?></span></td>
            <td><?= $s['submitted_today'] ? '<span class="badge badge-success">✓</span>' : '<span class="badge badge-danger">✗</span>' ?></td>
            <td class="td-bold" style="color:var(--gold);"><?= (int)$s['approved_month'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('clock') ?> Recent Submissions</div><a href="vp_approvals.php" class="btn btn-outline btn-sm">View All</a></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Staff</th><th>Dept</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentReports as $r): ?>
          <tr>
            <td class="td-bold"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></td>
            <td><span class="badge badge-muted"><?= sanitize($r['dept_code']??'—') ?></span></td>
            <td class="td-muted"><?= formatDate($r['report_date'],'M d') ?></td>
            <td><?= statusBadge($r['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const gc='rgba(255,255,255,0.05)',tc='#9ba3b8';
const baseScales={x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}},y:{ticks:{color:tc,font:{size:11}},grid:{color:gc},beginAtZero:true}};
new Chart(document.getElementById('trendChart'),{type:'line',data:{labels:<?= json_encode($trendLabels) ?>,datasets:[{label:'Submissions',data:<?= json_encode($trendData) ?>,borderColor:'#c9a84c',backgroundColor:'rgba(201,168,76,0.08)',fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#c9a84c',pointBorderColor:'#0d0f14',pointBorderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:baseScales}});
new Chart(document.getElementById('deptChart'),{type:'bar',data:{labels:<?= json_encode(array_column($deptOverview,'code')) ?>,datasets:[{label:'Approved',data:<?= json_encode(array_map(fn($d)=>(int)$d['approved'],$deptOverview)) ?>,backgroundColor:'rgba(45,212,160,0.7)',borderRadius:6},{label:'Pending',data:<?= json_encode(array_map(fn($d)=>(int)$d['pending'],$deptOverview)) ?>,backgroundColor:'rgba(245,166,35,0.5)',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:baseScales}});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
