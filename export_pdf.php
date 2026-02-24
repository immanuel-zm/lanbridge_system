<?php
// export_pdf.php — Print-ready PDF report
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin(); requireRole(3);

$db        = getDB();
$user      = currentUser();
$roleLevel = (int)$user['role_level'];
$deptId    = (int)$user['department_id'];

$month  = $_GET['month']  ?? date('Y-m');
$dept   = (int)($_GET['dept'] ?? 0);
$status = $_GET['status'] ?? 'all';

$monthStart  = $month.'-01';
$monthEnd    = date('Y-m-t', strtotime($monthStart));
$deptWhere   = $roleLevel <= 2 ? ($dept ? "AND u.department_id=$dept" : '') : "AND u.department_id=$deptId";
$statusWhere = ($status && $status!=='all') ? "AND r.status='$status'" : '';

$data = $db->query(
    "SELECT u.employee_id, u.first_name, u.last_name, d.name AS dept,
            r.report_date, r.tasks_completed, r.key_metrics, r.status,
            CONCAT(IFNULL(rev.first_name,''),' ',IFNULL(rev.last_name,'')) AS reviewed_by
     FROM reports r
     JOIN users u ON r.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users rev ON r.approved_by=rev.id
     WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd' $deptWhere $statusWhere
     ORDER BY r.report_date DESC, u.last_name"
)->fetchAll();

$total    = count($data);
$approved = count(array_filter($data, fn($r)=>$r['status']==='approved'));
$pending  = count(array_filter($data, fn($r)=>$r['status']==='pending'));
$rejected = count(array_filter($data, fn($r)=>$r['status']==='rejected'));
$rate     = $total > 0 ? round(($approved/$total)*100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>KPI Report — <?= date('F Y', strtotime($monthStart)) ?></title>
<style>
  @media print { .no-print { display:none!important; } body { padding:0; } }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:#fff; color:#1a1a2e; font-size:12px; padding:30px; }
  .no-print { position:fixed; top:16px; right:16px; background:#c9a84c; color:#000; border:none; padding:10px 20px; border-radius:8px; font-weight:700; cursor:pointer; font-size:13px; }
  h1 { font-size:22px; color:#0d0f14; margin-bottom:4px; }
  .sub { font-size:13px; color:#666; margin-bottom:20px; }
  .summary { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
  .box { background:#f5f5f5; border-radius:8px; padding:12px 20px; text-align:center; flex:1; min-width:100px; }
  .box .num { font-size:26px; font-weight:700; color:#0d0f14; }
  .box .lbl { font-size:10px; text-transform:uppercase; letter-spacing:0.5px; color:#666; margin-top:2px; }
  table { width:100%; border-collapse:collapse; margin-top:8px; }
  th { background:#0d0f14; color:#c9a84c; padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; }
  td { padding:8px 10px; border-bottom:1px solid #eee; vertical-align:top; }
  tr:nth-child(even) td { background:#f9f9f9; }
  .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:600; text-transform:uppercase; }
  .approved { background:#d4f7ed; color:#0d7a5f; }
  .pending  { background:#fff3d4; color:#8a5700; }
  .rejected { background:#ffe0e3; color:#8a1a2a; }
  .footer { margin-top:30px; text-align:center; font-size:11px; color:#999; border-top:1px solid #eee; padding-top:12px; }
</style>
</head>
<body>
<button class="no-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
<h1>Lanbridge College — Staff Performance Report</h1>
<div class="sub">Period: <?= date('F Y', strtotime($monthStart)) ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?> &nbsp;|&nbsp; By: <?= sanitize($user['first_name'].' '.$user['last_name']) ?></div>
<div class="summary">
  <div class="box"><div class="num"><?= $total ?></div><div class="lbl">Total</div></div>
  <div class="box"><div class="num" style="color:#0d7a5f;"><?= $approved ?></div><div class="lbl">Approved</div></div>
  <div class="box"><div class="num" style="color:#8a5700;"><?= $pending ?></div><div class="lbl">Pending</div></div>
  <div class="box"><div class="num" style="color:#8a1a2a;"><?= $rejected ?></div><div class="lbl">Rejected</div></div>
  <div class="box"><div class="num"><?= $rate ?>%</div><div class="lbl">Approval Rate</div></div>
</div>
<table>
  <thead><tr><th>Employee</th><th>Department</th><th>Date</th><th>Tasks Summary</th><th>Status</th><th>Reviewed By</th></tr></thead>
  <tbody>
    <?php foreach ($data as $row): ?>
    <tr>
      <td><strong><?= sanitize($row['first_name'].' '.$row['last_name']) ?></strong><br><small><?= sanitize($row['employee_id']) ?></small></td>
      <td><?= sanitize($row['dept']??'—') ?></td>
      <td><?= formatDate($row['report_date'],'M d, Y') ?></td>
      <td><?= sanitize(substr($row['tasks_completed'],0,120)) ?>...</td>
      <td><span class="badge <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
      <td><?= sanitize(trim($row['reviewed_by'])??'—') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="footer">Lanbridge College KPI System — Confidential Document — <?= date('Y') ?></div>
</body>
</html>
