<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(5);
$pageTitle    = 'Student Affairs Dashboard';
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
$myApproved= (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='approved' AND report_date>='$monthStart'")->fetchColumn();
$myTotal   = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date>='$monthStart'")->fetchColumn();
$myRate    = $myTotal > 0 ? round(($myApproved/$myTotal)*100) : 0;

$team = $db->query("SELECT u.first_name,u.last_name,(SELECT COUNT(*) FROM reports WHERE user_id=u.id AND report_date='$today') AS submitted_today,(SELECT COUNT(*) FROM reports WHERE user_id=u.id AND status='approved' AND report_date>='$monthStart') AS approved_month FROM users u WHERE u.department_id=$deptId AND u.is_active=1 AND u.id!=$uid ORDER BY u.first_name")->fetchAll();
?>

<div class="dept-banner"><?= icon('heart',24) ?><div><div class="dept-banner-name">Student Affairs</div><div class="dept-banner-desc">Student welfare, support services &amp; campus life programmes</div></div></div>

<div class="stat-grid">
  <div class="stat-card green"><div class="stat-top"><div><div class="stat-number"><?= $approved ?></div><div class="stat-label">Dept Approved This Month</div></div><div class="stat-icon"><?= icon('check-square',20) ?></div></div></div>
  <div class="stat-card orange"><div class="stat-top"><div><div class="stat-number"><?= $pending ?></div><div class="stat-label">Pending My Review</div></div><div class="stat-icon"><?= icon('clock',20) ?></div></div><div class="stat-delta <?= $pending>0?'down':'up' ?>"><?= icon('check-square',12) ?><a href="head_approvals.php" style="color:inherit;"> <?= $pending>0?'Review now':'All clear!' ?></a></div></div>
  <div class="stat-card gold"><div class="stat-top"><div><div class="stat-number"><?= $myRate ?>%</div><div class="stat-label">My Approval Rate</div></div><div class="stat-icon"><?= icon('bar-chart',20) ?></div></div><div class="stat-delta <?= $myRate>=70?'up':'down' ?>"><?= icon('trending-up',12) ?> <?= $myApproved ?> / <?= $myTotal ?> reports</div></div>
  <div class="stat-card blue"><div class="stat-top"><div><div class="stat-number"><?= $myToday?'✓':'✗' ?></div><div class="stat-label">My Report Today</div></div><div class="stat-icon"><?= icon('file-text',20) ?></div></div><div class="stat-delta <?= $myToday?'up':'down' ?>"><?= $myToday?'Submitted':'Not yet — submit now' ?></div></div>
</div>

<!-- Banner CTA if not submitted -->
<?php if (!$myToday): ?>
<div class="alert alert-warning" style="margin-bottom:24px;">
  <?= icon('clock',16) ?> <strong>Reminder:</strong> You haven't submitted your daily report yet today.
  <a href="submit_report.php" class="btn btn-primary btn-sm" style="margin-left:12px;"><?= icon('send',13) ?> Submit Now</a>
</div>
<?php endif; ?>

<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('send') ?> Quick Actions</div></div>
    <div class="card-body">
      <div class="action-grid" style="grid-template-columns:1fr 1fr;">
        <a href="head_approvals.php" class="action-tile"><?= icon('check-square',28) ?><div class="action-tile-label">Review Reports</div><?php if($pending>0):?><div class="action-tile-count"><?= $pending ?> pending</div><?php endif;?></a>
        <a href="submit_report.php"  class="action-tile"><?= icon('send',28) ?><div class="action-tile-label">My Daily Report</div></a>
        <a href="submit_kpi.php"     class="action-tile"><?= icon('bar-chart',28) ?><div class="action-tile-label">Submit KPI</div></a>
        <a href="my_submissions.php" class="action-tile"><?= icon('clock',28) ?><div class="action-tile-label">My History</div></a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('users') ?> Student Affairs Team</div></div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
