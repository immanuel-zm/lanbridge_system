<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(2);
$pageTitle    = 'Analytics & Reporting';
$pageSubtitle = 'Full institutional performance intelligence';
require_once __DIR__ . '/../includes/header.php';

$db   = getDB();
$user = currentUser();

// ── Period / filter controls ──────────────────────────────────
$period     = $_GET['period'] ?? date('Y-m');
$filterDept = (int)($_GET['dept'] ?? 0);
if (!preg_match('/^\d{4}-\d{2}$/', $period)) $period = date('Y-m');
$monthStart  = $period.'-01';
$monthEnd    = date('Y-m-t', strtotime($monthStart));
$periodLabel = date('F Y', strtotime($monthStart));
$prevPeriod  = date('Y-m', strtotime('-1 month', strtotime($monthStart)));
$prevStart   = $prevPeriod.'-01';
$prevEnd     = date('Y-m-t', strtotime($prevStart));
$deptWhere   = $filterDept ? "AND u.department_id=$filterDept" : "";
$allDepts    = $db->query("SELECT id,name,code FROM departments ORDER BY name")->fetchAll();

// ── Summary KPIs ──────────────────────────────────────────────
$totalStaff       = (int)$db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='staff' AND u.is_active=1")->fetchColumn();
$totalReports     = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' $deptWhere")->fetchColumn();
$approved         = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' AND r.status='approved' $deptWhere")->fetchColumn();
$pending          = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' AND r.status='pending' $deptWhere")->fetchColumn();
$rejected         = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' AND r.status='rejected' $deptWhere")->fetchColumn();
$approvalRate     = $totalReports > 0 ? round($approved / $totalReports * 100) : 0;
$avgQuality       = (float)$db->query("SELECT ROUND(AVG(k.quality_score),1) FROM kpi_submissions k JOIN users u ON k.user_id=u.id WHERE DATE_FORMAT(k.submission_date,'%Y-%m')='$period' $deptWhere")->fetchColumn();
$prevReports      = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$prevStart' AND '$prevEnd' $deptWhere")->fetchColumn();
$prevApproved     = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$prevStart' AND '$prevEnd' AND r.status='approved' $deptWhere")->fetchColumn();
$prevRate         = $prevReports > 0 ? round($prevApproved / $prevReports * 100) : 0;
$rateDelta        = $approvalRate - $prevRate;
$activeSubmitters = (int)$db->query("SELECT COUNT(DISTINCT r.user_id) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' $deptWhere")->fetchColumn();
$complianceRate   = $totalStaff > 0 ? round($activeSubmitters / $totalStaff * 100) : 0;

// ── Daily trend for selected month ───────────────────────────
$trendLabels = $trendApproved = $trendPending = $trendRejected = [];
$daysInMonth = (int)date('t', strtotime($monthStart));
for ($i = 1; $i <= $daysInMonth; $i++) {
    $d = $period.'-'.str_pad($i,2,'0',STR_PAD_LEFT);
    $trendLabels[] = date('d', strtotime($d));
    $stmt = $db->prepare("SELECT SUM(status='approved') a, SUM(status='pending') p, SUM(status='rejected') r FROM reports r2 JOIN users u ON r2.user_id=u.id WHERE r2.report_date=? $deptWhere");
    $stmt->execute([$d]);
    $row = $stmt->fetch();
    $trendApproved[]  = (int)($row['a']??0);
    $trendPending[]   = (int)($row['p']??0);
    $trendRejected[]  = (int)($row['r']??0);
}

// ── Dept performance ──────────────────────────────────────────
$deptPerf = $db->query(
    "SELECT d.id, d.name, d.code,
            COUNT(DISTINCT u.id) AS staff,
            COUNT(r.id) AS total,
            SUM(r.status='approved') AS approved,
            SUM(r.status='pending')  AS pending,
            SUM(r.status='rejected') AS rejected,
            ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,0) AS rate,
            ROUND(AVG(k.quality_score),0) AS avg_quality
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date BETWEEN '$monthStart' AND '$monthEnd'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND DATE_FORMAT(k.submission_date,'%Y-%m')='$period'
     GROUP BY d.id ORDER BY rate DESC"
)->fetchAll();

// ── 6-month trend ─────────────────────────────────────────────
$monthTrendLabels = $monthTrendRates = $monthTrendVolume = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months", strtotime($monthStart)));
    $ms = $m.'-01'; $me = date('Y-m-t', strtotime($ms));
    $monthTrendLabels[] = date('M', strtotime($ms));
    $tot = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$ms' AND '$me' $deptWhere")->fetchColumn();
    $app = (int)$db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE r.report_date BETWEEN '$ms' AND '$me' AND r.status='approved' $deptWhere")->fetchColumn();
    $monthTrendRates[]  = $tot > 0 ? round($app / $tot * 100) : 0;
    $monthTrendVolume[] = $tot;
}

// ── Top performers & needs attention ─────────────────────────
$topPerf = $db->query(
    "SELECT u.first_name, u.last_name, d.code AS dept,
            COUNT(r.id) AS total,
            SUM(r.status='approved') AS approved,
            ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,0) AS rate,
            ROUND(AVG(k.quality_score),0) AS quality
     FROM users u JOIN roles ro ON u.role_id=ro.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date BETWEEN '$monthStart' AND '$monthEnd'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND DATE_FORMAT(k.submission_date,'%Y-%m')='$period'
     WHERE u.is_active=1 AND ro.slug='staff' ".($filterDept?"AND u.department_id=$filterDept":"")."
     GROUP BY u.id HAVING total > 0
     ORDER BY rate DESC, approved DESC LIMIT 8"
)->fetchAll();

$needsAttention = $db->query(
    "SELECT u.first_name, u.last_name, d.code AS dept,
            MAX(r.report_date) AS last_report,
            SUM(r.status='rejected') AS rejections,
            COUNT(r.id) AS total
     FROM users u JOIN roles ro ON u.role_id=ro.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date BETWEEN '$monthStart' AND '$monthEnd'
     WHERE u.is_active=1 AND ro.slug='staff' ".($filterDept?"AND u.department_id=$filterDept":"")."
     GROUP BY u.id HAVING total = 0 OR rejections >= 2
     ORDER BY rejections DESC, last_report ASC LIMIT 8"
)->fetchAll();

// ── Financial data ────────────────────────────────────────────
$finIncome  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND status='approved' AND DATE_FORMAT(transaction_date,'%Y-%m')='$period'")->fetchColumn();
$finExpense = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND status='approved' AND DATE_FORMAT(transaction_date,'%Y-%m')='$period'")->fetchColumn();
$feesDue    = (float)$db->query("SELECT COALESCE(SUM(amount_due),0) FROM student_fees WHERE academic_year=YEAR(NOW())")->fetchColumn();
$feesPaid   = (float)$db->query("SELECT COALESCE(SUM(amount_paid),0) FROM student_fees WHERE academic_year=YEAR(NOW())")->fetchColumn();
$feesRate   = $feesDue > 0 ? round($feesPaid / $feesDue * 100) : 0;

$finTrendLabels = $finTrendIncome = $finTrendExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months", strtotime($monthStart)));
    $finTrendLabels[]  = date('M', strtotime($m.'-01'));
    $finTrendIncome[]  = round((float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND status='approved' AND DATE_FORMAT(transaction_date,'%Y-%m')='$m'")->fetchColumn(), 2);
    $finTrendExpense[] = round((float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND status='approved' AND DATE_FORMAT(transaction_date,'%Y-%m')='$m'")->fetchColumn(), 2);
}

// Available periods
$periods = [];
for ($i = 0; $i < 12; $i++) {
    $p = date('Y-m', strtotime("-$i months"));
    $periods[$p] = date('F Y', strtotime($p.'-01'));
}
?>

<!-- Controls -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <select name="period" class="form-control" style="width:150px;" onchange="this.form.submit()">
      <?php foreach ($periods as $p=>$l): ?>
      <option value="<?= $p ?>" <?= $p===$period?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <select name="dept" class="form-control" style="width:170px;" onchange="this.form.submit()">
      <option value="0">All Departments</option>
      <?php foreach ($allDepts as $d): ?>
      <option value="<?= $d['id'] ?>" <?= $filterDept===$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($filterDept || $period!==date('Y-m')): ?>
    <a href="ceo_analytics.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>
  <div style="margin-left:auto;display:flex;gap:8px;">
    <a href="export.php?month=<?= $period ?>&dept=<?= $filterDept ?>" class="btn btn-outline btn-sm"><?= icon('download',13) ?> Export Reports</a>
    <a href="export.php?type=payroll&period=<?= $period ?>" class="btn btn-outline btn-sm"><?= icon('dollar-sign',13) ?> Export Payroll</a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalReports ?></div><div class="stat-label">Total Reports</div></div>
      <div class="stat-icon"><?= icon('file-text',20) ?></div>
    </div>
    <div class="stat-delta <?= $totalReports>=$prevReports?'up':'down' ?>">
      <?= $prevReports>0 ? (($totalReports>=$prevReports?'↑':'↓').' '.abs($totalReports-$prevReports).' vs '.date('M',strtotime($prevStart))) : 'First recorded period' ?>
    </div>
  </div>
  <div class="stat-card <?= $approvalRate>=70?'green':($approvalRate>=40?'orange':'') ?>" style="<?= $approvalRate<40?'border-top-color:var(--danger);':'' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $approvalRate ?>%</div><div class="stat-label">Approval Rate</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta <?= $rateDelta>=0?'up':'down' ?>"><?= ($rateDelta>=0?'↑':'↓') ?> <?= abs($rateDelta) ?>% vs last month</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $complianceRate ?>%</div><div class="stat-label">Staff Compliance</div></div>
      <div class="stat-icon"><?= icon('users',20) ?></div>
    </div>
    <div class="stat-delta"><?= $activeSubmitters ?>/<?= $totalStaff ?> staff submitted</div>
  </div>
  <div class="stat-card <?= $avgQuality>=75?'green':($avgQuality>=50?'orange':'') ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $avgQuality ? $avgQuality.'%' : '—' ?></div><div class="stat-label">Avg KPI Quality</div></div>
      <div class="stat-icon"><?= icon('award',20) ?></div>
    </div>
    <div class="stat-delta">Average quality score this period</div>
  </div>
</div>

<!-- Row 1: Daily trend + Doughnut -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('trending-up') ?> Daily Trend — <?= $periodLabel ?></div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="dailyTrendChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('pie-chart') ?> Status Distribution</div></div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:12px;justify-content:center;">
      <div style="width:180px;height:180px;"><canvas id="doughnutChart"></canvas></div>
      <div style="display:flex;gap:16px;font-size:12px;flex-wrap:wrap;justify-content:center;">
        <span style="color:#2dd4a0;">● Approved: <?= $approved ?></span>
        <span style="color:#f5a623;">● Pending: <?= $pending ?></span>
        <span style="color:#e8556a;">● Rejected: <?= $rejected ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: 6-month trend + Dept bar -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('bar-chart') ?> 6-Month Approval Trend</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="monthTrendChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('layers') ?> Dept Performance — <?= $periodLabel ?></div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="deptBarChart"></canvas></div></div>
  </div>
</div>

<!-- Row 3: Financial charts -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('dollar-sign') ?> Revenue vs Expenditure — 6 Months</div>
      <a href="finance_dashboard.php" class="btn btn-outline btn-sm"><?= icon('external-link',13) ?> Finance</a>
    </div>
    <div class="card-body"><div class="chart-wrap"><canvas id="finTrendChart"></canvas></div></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('users') ?> Student Fees — <?= date('Y') ?></div></div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;padding:24px;">
      <div style="position:relative;width:150px;height:150px;">
        <canvas id="feesGauge"></canvas>
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
          <div style="font-size:26px;font-weight:800;color:<?= $feesRate>=75?'var(--success)':($feesRate>=50?'var(--warning)':'var(--danger)') ?>;"><?= $feesRate ?>%</div>
          <div style="font-size:10px;color:var(--text-muted);">collected</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;width:100%;max-width:260px;">
        <div style="text-align:center;background:var(--bg-elevated);border-radius:8px;padding:10px;">
          <div style="font-size:10px;color:var(--text-muted);margin-bottom:3px;">TOTAL DUE</div>
          <div style="font-size:13px;font-weight:700;color:var(--gold);"><?= formatMoney($feesDue) ?></div>
        </div>
        <div style="text-align:center;background:var(--bg-elevated);border-radius:8px;padding:10px;">
          <div style="font-size:10px;color:var(--text-muted);margin-bottom:3px;">COLLECTED</div>
          <div style="font-size:13px;font-weight:700;color:var(--success);"><?= formatMoney($feesPaid) ?></div>
        </div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:12px;color:var(--danger);font-weight:600;">Outstanding: <?= formatMoney($feesDue-$feesPaid) ?></div>
        <a href="finance_fees.php" style="font-size:12px;color:var(--gold);margin-top:4px;display:inline-block;">Manage Fees →</a>
      </div>
    </div>
  </div>
</div>

<!-- Dept overview table -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('grid') ?> Department Breakdown — <?= $periodLabel ?></div>
    <a href="export.php?month=<?= $period ?>&dept=<?= $filterDept ?>" class="btn btn-outline btn-sm"><?= icon('download',13) ?> Export CSV</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Department</th><th>Staff</th><th>Reports</th><th>Approved</th><th>Pending</th><th>Rejected</th><th>Approval Rate</th><th>Avg Quality</th></tr></thead>
      <tbody>
        <?php foreach ($deptPerf as $d):
          $rate = (int)($d['rate']??0);
          $col  = $rate>=70?'var(--success)':($rate>=40?'var(--warning)':'var(--danger)');
        ?>
        <tr>
          <td><div class="td-bold"><?= sanitize($d['name']) ?></div><span class="badge badge-muted" style="font-size:9px;"><?= sanitize($d['code']) ?></span></td>
          <td><?= (int)$d['staff'] ?></td>
          <td><?= (int)$d['total'] ?></td>
          <td><span class="badge badge-success"><?= (int)$d['approved'] ?></span></td>
          <td><span class="badge badge-warning"><?= (int)$d['pending'] ?></span></td>
          <td><span class="badge badge-danger"><?= (int)$d['rejected'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:110px;">
              <div class="progress" style="flex:1;"><div class="progress-bar <?= $rate>=70?'green':($rate>=40?'gold':'red') ?>" style="width:<?= $rate ?>%;"></div></div>
              <span style="font-size:12px;font-weight:700;color:<?= $col ?>;flex-shrink:0;"><?= $rate ?>%</span>
            </div>
          </td>
          <td style="font-weight:600;color:<?= $d['avg_quality']&&$d['avg_quality']>=75?'var(--success)':($d['avg_quality']?'var(--warning)':'var(--text-muted)') ?>;"><?= $d['avg_quality']?$d['avg_quality'].'%':'—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Top Performers + Needs Attention -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title">🏆 Top Performers — <?= $periodLabel ?></div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Staff</th><th>Dept</th><th>Reports</th><th>Rate</th><th>Quality</th></tr></thead>
        <tbody>
          <?php $medals=['🥇','🥈','🥉']; ?>
          <?php foreach ($topPerf as $i=>$p): ?>
          <tr>
            <td style="font-size:16px;"><?= $medals[$i]??'#'.($i+1) ?></td>
            <td><div style="display:flex;align-items:center;gap:8px;"><div class="avatar avatar-sm"><?= getInitials($p['first_name'],$p['last_name']) ?></div><span class="td-bold"><?= sanitize($p['first_name'].' '.$p['last_name']) ?></span></div></td>
            <td><span class="badge badge-muted"><?= sanitize($p['dept']??'—') ?></span></td>
            <td><?= (int)$p['total'] ?></td>
            <td><span class="badge badge-success"><?= (int)$p['rate'] ?>%</span></td>
            <td style="font-weight:600;color:<?= $p['quality']>=75?'var(--success)':'var(--warning)' ?>;"><?= $p['quality']?$p['quality'].'%':'—' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($topPerf)): ?><tr><td colspan="6"><div class="empty-state" style="padding:20px;"><?= icon('inbox',28) ?><p>No data</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">⚠️ Needs Attention</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Staff</th><th>Dept</th><th>Reports</th><th>Issue</th></tr></thead>
        <tbody>
          <?php foreach ($needsAttention as $n): ?>
          <tr>
            <td class="td-bold"><?= sanitize($n['first_name'].' '.$n['last_name']) ?></td>
            <td><span class="badge badge-muted"><?= sanitize($n['dept']??'—') ?></span></td>
            <td><?= (int)$n['total'] ?></td>
            <td><?= (int)$n['total']===0 ? '<span class="badge badge-danger">No submissions</span>' : '<span class="badge badge-'.((int)$n['rejections']>=3?'danger':'warning').'">'.(int)$n['rejections'].' rejections</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($needsAttention)): ?><tr><td colspan="4"><div class="empty-state" style="padding:20px;"><?= icon('check-square',28) ?><p style="color:var(--success);">All on track!</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Financial summary strip -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('dollar-sign') ?> Financial Summary — <?= $periodLabel ?></div>
    <a href="finance_dashboard.php" class="btn btn-outline btn-sm"><?= icon('external-link',13) ?> Full Finance</a>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
      <?php
      $fboxes = [
        ['INCOME',       formatMoney($finIncome),  'var(--success)', 'rgba(45,212,160,0.06)',  'rgba(45,212,160,0.15)'],
        ['EXPENDITURE',  formatMoney($finExpense),  'var(--danger)',  'rgba(232,85,106,0.06)', 'rgba(232,85,106,0.15)'],
        ['NET',          formatMoney($finIncome-$finExpense), $finIncome>=$finExpense?'var(--gold)':'var(--danger)', 'rgba(201,168,76,0.06)', 'rgba(201,168,76,0.15)'],
        ['FEES COLLECTED',$feesRate.'%',           'var(--info)',    'rgba(99,179,237,0.06)', 'rgba(99,179,237,0.15)'],
      ];
      foreach ($fboxes as [$label,$val,$col,$bg,$border]):
      ?>
      <div style="text-align:center;padding:16px;background:<?= $bg ?>;border:1px solid <?= $border ?>;border-radius:8px;">
        <div style="font-size:10px;color:var(--text-muted);margin-bottom:6px;"><?= $label ?></div>
        <div style="font-size:18px;font-weight:800;color:<?= $col ?>;"><?= $val ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const gc='rgba(255,255,255,0.05)',tc='#9ba3b8';
const bs={x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}},y:{ticks:{color:tc,font:{size:11}},grid:{color:gc},beginAtZero:true}};

new Chart(document.getElementById('dailyTrendChart'),{type:'line',data:{labels:<?= json_encode($trendLabels) ?>,datasets:[
  {label:'Approved',data:<?= json_encode($trendApproved) ?>,borderColor:'#2dd4a0',backgroundColor:'rgba(45,212,160,0.08)',fill:true,tension:0.4,pointRadius:2},
  {label:'Pending', data:<?= json_encode($trendPending) ?>, borderColor:'#f5a623',backgroundColor:'rgba(245,166,35,0.06)', fill:true,tension:0.4,pointRadius:2},
  {label:'Rejected',data:<?= json_encode($trendRejected) ?>,borderColor:'#e8556a',backgroundColor:'rgba(232,85,106,0.06)',fill:true,tension:0.4,pointRadius:2},
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:bs}});

new Chart(document.getElementById('doughnutChart'),{type:'doughnut',data:{labels:['Approved','Pending','Rejected'],datasets:[{data:[<?= $approved ?>,<?= $pending ?>,<?= $rejected ?>],backgroundColor:['#2dd4a0','#f5a623','#e8556a'],borderColor:'#1f2435',borderWidth:3}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{display:false}}}});

new Chart(document.getElementById('monthTrendChart'),{type:'line',data:{labels:<?= json_encode($monthTrendLabels) ?>,datasets:[
  {label:'Approval Rate %',data:<?= json_encode($monthTrendRates) ?>,borderColor:'#c9a84c',backgroundColor:'rgba(201,168,76,0.1)',fill:true,tension:0.4,yAxisID:'y'},
  {label:'Volume',data:<?= json_encode($monthTrendVolume) ?>,borderColor:'#2dd4a0',borderDash:[4,4],fill:false,tension:0.4,yAxisID:'y1'},
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:{x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}},y:{ticks:{color:tc,font:{size:11},callback:v=>v+'%'},grid:{color:gc},beginAtZero:true,max:100,position:'left'},y1:{ticks:{color:tc,font:{size:11}},grid:{display:false},beginAtZero:true,position:'right'}}}});

new Chart(document.getElementById('deptBarChart'),{type:'bar',data:{labels:<?= json_encode(array_column($deptPerf,'code')) ?>,datasets:[
  {label:'Approved',data:<?= json_encode(array_map(fn($d)=>(int)$d['approved'],$deptPerf)) ?>,backgroundColor:'rgba(45,212,160,0.75)',borderRadius:5},
  {label:'Pending', data:<?= json_encode(array_map(fn($d)=>(int)$d['pending'],$deptPerf)) ?>, backgroundColor:'rgba(245,166,35,0.75)', borderRadius:5},
  {label:'Rejected',data:<?= json_encode(array_map(fn($d)=>(int)$d['rejected'],$deptPerf)) ?>,backgroundColor:'rgba(232,85,106,0.75)', borderRadius:5},
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:bs}});

new Chart(document.getElementById('finTrendChart'),{type:'bar',data:{labels:<?= json_encode($finTrendLabels) ?>,datasets:[
  {label:'Income',     data:<?= json_encode($finTrendIncome) ?>, backgroundColor:'rgba(45,212,160,0.75)', borderRadius:5},
  {label:'Expenditure',data:<?= json_encode($finTrendExpense) ?>,backgroundColor:'rgba(232,85,106,0.65)',  borderRadius:5},
]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:bs}});

new Chart(document.getElementById('feesGauge'),{type:'doughnut',data:{datasets:[{data:[<?= $feesRate ?>,<?= 100-$feesRate ?>],backgroundColor:['<?= $feesRate>=75?"#2dd4a0":($feesRate>=50?"#f5a623":"#e8556a") ?>','rgba(255,255,255,0.05)'],borderColor:['transparent','transparent'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{enabled:false}}}});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
