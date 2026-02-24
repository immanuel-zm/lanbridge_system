<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(6);
$pageTitle    = 'Performance Reports';
$pageSubtitle = 'Staff performance analytics and records';
require_once __DIR__ . '/../includes/header.php';

$db        = getDB();
$user      = currentUser();
$roleLevel = (int)$user['role_level'];
$deptId    = (int)$user['department_id'];

// Filters
$month  = $_GET['month']  ?? date('Y-m');
$dept   = (int)($_GET['dept']   ?? 0);
$status = $_GET['status'] ?? 'all';
$userId = (int)($_GET['user']   ?? 0);

$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// Build dept restriction
$deptWhere = $roleLevel <= 2
    ? ($dept ? "AND u.department_id=$dept" : '')
    : "AND u.department_id=$deptId";

$statusWhere = ($status && $status!=='all') ? "AND r.status='$status'" : '';
$userWhere   = $userId ? "AND u.id=$userId" : '';

// Per-staff summary
$summary = $db->query(
    "SELECT u.id, u.first_name, u.last_name, u.employee_id,
            d.name AS dept_name, d.code AS dept_code, ro.name AS role_name,
            COUNT(r.id) AS total,
            SUM(r.status='approved') AS approved,
            SUM(r.status='rejected') AS rejected,
            SUM(r.status='pending')  AS pending,
            ROUND(AVG(k.quality_score),1) AS avg_quality,
            SUM(k.time_spent_hours) AS total_hours,
            COUNT(DISTINCT r.report_date) AS active_days,
            ROUND(SUM(r.status='approved')/NULLIF(COUNT(r.id),0)*100,0) AS rate
     FROM users u
     JOIN roles ro ON u.role_id=ro.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date BETWEEN '$monthStart' AND '$monthEnd' $statusWhere
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.submission_date BETWEEN '$monthStart' AND '$monthEnd'
     WHERE u.is_active=1 AND ro.slug='staff' $deptWhere $userWhere
     GROUP BY u.id
     ORDER BY rate DESC, approved DESC"
)->fetchAll();

// Stats
$totalStaff = count($summary);
$totalApproved = array_sum(array_column($summary,'approved'));
$totalPending  = array_sum(array_column($summary,'pending'));
$avgQual = $totalStaff > 0 ? round(array_sum(array_column($summary,'avg_quality'))/$totalStaff,1) : 0;

$depts = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>

<!-- Filter Bar -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px 20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="<?= sanitize($month) ?>">
      </div>
      <?php if ($roleLevel <= 2): ?>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Department</label>
        <select name="dept" class="form-control">
          <option value="">All Departments</option>
          <?php foreach ($depts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $dept==$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="all" <?= $status==='all'?'selected':'' ?>>All Statuses</option>
          <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
          <option value="pending"  <?= $status==='pending'?'selected':'' ?>>Pending</option>
          <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary"><?= icon('bar-chart',14) ?> Apply</button>
        <a href="reports.php" class="btn btn-outline">Reset</a>
        <a href="export.php?month=<?= urlencode($month) ?>&dept=<?= $dept ?>&status=<?= urlencode($status) ?>" class="btn btn-outline"><?= icon('download',14) ?> Export</a>
      </div>
    </form>
  </div>
</div>

<!-- Summary Stats -->
<div class="stat-grid">
  <div class="stat-card gold"><div class="stat-top"><div><div class="stat-number"><?= $totalStaff ?></div><div class="stat-label">Staff Members</div></div><div class="stat-icon"><?= icon('users',20) ?></div></div></div>
  <div class="stat-card green"><div class="stat-top"><div><div class="stat-number"><?= $totalApproved ?></div><div class="stat-label">Approved Reports</div></div><div class="stat-icon"><?= icon('check-square',20) ?></div></div></div>
  <div class="stat-card orange"><div class="stat-top"><div><div class="stat-number"><?= $totalPending ?></div><div class="stat-label">Pending Review</div></div><div class="stat-icon"><?= icon('clock',20) ?></div></div></div>
  <div class="stat-card blue"><div class="stat-top"><div><div class="stat-number"><?= $avgQual ?: '—' ?></div><div class="stat-label">Avg Quality Score</div></div><div class="stat-icon"><?= icon('bar-chart',20) ?></div></div></div>
</div>

<!-- Per-staff summary table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('users') ?> Staff Performance — <?= date('F Y', strtotime($monthStart)) ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Staff Member</th><th>Department</th><th>Total</th>
          <th>Approved</th><th>Rejected</th><th>Avg Quality</th><th>Hours</th><th>Active Days</th><th>Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($summary)): ?>
        <tr><td colspan="10"><div class="empty-state" style="padding:40px;"><?= icon('inbox',36) ?><h3>No data for this period</h3></div></td></tr>
        <?php else: ?>
        <?php foreach ($summary as $i => $s):
          $rate  = (int)($s['rate'] ?? 0);
          $color = $rate>=70?'green':($rate>=40?'gold':'red');
        ?>
        <tr>
          <td class="td-num"><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="avatar avatar-sm"><?= getInitials($s['first_name'],$s['last_name']) ?></div>
              <div>
                <div class="td-bold"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></div>
                <div class="td-muted"><?= sanitize($s['employee_id']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-muted"><?= sanitize($s['dept_code']??'—') ?></span></td>
          <td><?= (int)$s['total'] ?></td>
          <td><span class="badge badge-success"><?= (int)$s['approved'] ?></span></td>
          <td><span class="badge badge-danger"><?= (int)$s['rejected'] ?></span></td>
          <td><?= $s['avg_quality'] ? $s['avg_quality'].'%' : '—' ?></td>
          <td><?= $s['total_hours'] ? $s['total_hours'].'h' : '—' ?></td>
          <td><?= (int)$s['active_days'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
              <div class="progress" style="flex:1;"><div class="progress-bar <?= $color ?>" style="width:<?= $rate ?>%"></div></div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);flex-shrink:0;"><?= $rate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
