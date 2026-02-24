<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(6);
$pageTitle    = 'Export Reports';
$pageSubtitle = 'Download performance data in your preferred format';
require_once __DIR__ . '/../includes/header.php';

$db        = getDB();
$user      = currentUser();
$roleLevel = (int)$user['role_level'];
$deptId    = (int)$user['department_id'];

$month  = $_GET['month']  ?? date('Y-m');
$dept   = (int)($_GET['dept'] ?? 0);
$status = $_GET['status'] ?? 'all';

$monthStart = $month.'-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

$deptWhere   = $roleLevel <= 2 ? ($dept ? "AND u.department_id=$dept" : '') : "AND u.department_id=$deptId";
$statusWhere = ($status && $status!=='all') ? "AND r.status='$status'" : '';

// Fetch export data
$data = $db->query(
    "SELECT u.employee_id, u.first_name, u.last_name, d.name AS dept,
            r.report_date, r.tasks_completed, r.key_metrics, r.challenges,
            r.tomorrow_plan, r.status, r.approval_comment,
            CONCAT(rev.first_name,' ',rev.last_name) AS reviewed_by,
            r.created_at
     FROM reports r
     JOIN users u ON r.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users rev ON r.approved_by=rev.id
     WHERE r.report_date BETWEEN '$monthStart' AND '$monthEnd'
     $deptWhere $statusWhere
     ORDER BY r.report_date DESC, u.last_name"
)->fetchAll();

$depts = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// ── Handle CSV export ──────────────────────────────────────────
if (isset($_GET['format']) && $_GET['format'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="lanbridge_kpi_'.$month.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Employee ID','Name','Department','Date','Tasks','Key Metrics','Challenges','Tomorrow Plan','Status','Reviewer Comment','Reviewed By','Submitted At']);
    foreach ($data as $row) {
        fputcsv($out,[
            $row['employee_id'],
            $row['first_name'].' '.$row['last_name'],
            $row['dept'],
            $row['report_date'],
            $row['tasks_completed'],
            $row['key_metrics'],
            $row['challenges'],
            $row['tomorrow_plan'],
            $row['status'],
            $row['approval_comment'],
            $row['reviewed_by'],
            $row['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

// ── Handle Excel export ────────────────────────────────────────
if (isset($_GET['format']) && $_GET['format'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="lanbridge_kpi_'.$month.'.xls"');
    echo '<table border="1">';
    echo '<tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Date</th><th>Tasks</th><th>Key Metrics</th><th>Status</th><th>Reviewed By</th></tr>';
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['employee_id']).'</td>';
        echo '<td>'.htmlspecialchars($row['first_name'].' '.$row['last_name']).'</td>';
        echo '<td>'.htmlspecialchars($row['dept']).'</td>';
        echo '<td>'.htmlspecialchars($row['report_date']).'</td>';
        echo '<td>'.htmlspecialchars(substr($row['tasks_completed'],0,200)).'</td>';
        echo '<td>'.htmlspecialchars($row['key_metrics']).'</td>';
        echo '<td>'.htmlspecialchars($row['status']).'</td>';
        echo '<td>'.htmlspecialchars($row['reviewed_by']).'</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit;
}
?>

<!-- Filter Card -->
<div class="card mb-24">
  <div class="card-header"><div class="card-title"><?= icon('download') ?> Export Filters</div></div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="<?= sanitize($month) ?>">
      </div>
      <?php if ($roleLevel <= 2): ?>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Department</label>
        <select name="dept" class="form-control">
          <option value="">All</option>
          <?php foreach ($depts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $dept==$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="all">All</option>
          <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
          <option value="pending"  <?= $status==='pending'?'selected':'' ?>>Pending</option>
          <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><?= icon('bar-chart',14) ?> Apply Filter</button>
    </form>
  </div>
</div>

<!-- Export Options -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">

  <a href="?month=<?= urlencode($month) ?>&dept=<?= $dept ?>&status=<?= urlencode($status) ?>&format=csv" class="action-tile" style="text-align:center;">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    <div class="action-tile-label">Export CSV</div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">Opens in Excel / Google Sheets</div>
  </a>

  <a href="?month=<?= urlencode($month) ?>&dept=<?= $dept ?>&status=<?= urlencode($status) ?>&format=excel" class="action-tile" style="text-align:center;">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    <div class="action-tile-label">Export Excel (.xls)</div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">Formatted spreadsheet</div>
  </a>

  <a href="export_pdf.php?month=<?= urlencode($month) ?>&dept=<?= $dept ?>&status=<?= urlencode($status) ?>" target="_blank" class="action-tile" style="text-align:center;">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    <div class="action-tile-label">Export PDF</div>
    <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">Print-ready report</div>
  </a>

</div>

<!-- Preview Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('file-text') ?> Preview <span class="badge badge-muted" style="margin-left:6px;"><?= count($data) ?> records</span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Employee</th><th>Department</th><th>Date</th><th>Tasks</th><th>Status</th><th>Reviewed By</th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
        <tr><td colspan="6"><div class="empty-state" style="padding:40px;"><?= icon('inbox',36) ?><h3>No records match your filters</h3></div></td></tr>
        <?php else: ?>
        <?php foreach (array_slice($data,0,20) as $row): ?>
        <tr>
          <td>
            <div class="td-bold"><?= sanitize($row['first_name'].' '.$row['last_name']) ?></div>
            <div class="td-muted"><?= sanitize($row['employee_id']) ?></div>
          </td>
          <td class="td-muted"><?= sanitize($row['dept']??'—') ?></td>
          <td class="td-bold"><?= formatDate($row['report_date'],'M d, Y') ?></td>
          <td style="max-width:200px;"><div class="truncate text-sm" style="color:var(--text-primary);"><?= sanitize(substr($row['tasks_completed'],0,80)) ?>...</div></td>
          <td><?= statusBadge($row['status']) ?></td>
          <td class="td-muted text-sm"><?= sanitize($row['reviewed_by']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($data) > 20): ?>
        <tr><td colspan="6" style="text-align:center;padding:14px;color:var(--text-muted);font-size:13px;">
          ... and <?= count($data)-20 ?> more rows (all included in export)
        </td></tr>
        <?php endif; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
