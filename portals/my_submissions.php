<?php
$pageTitle    = 'My Submission History';
$pageSubtitle = 'All your KPI reports and daily submissions';
require_once __DIR__ . '/../includes/header.php';

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

$month = $_GET['month'] ?? date('Y-m');
$monthStart = $month . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// Reports
$reports = $db->prepare(
    "SELECT r.*, u.first_name AS rev_first, u.last_name AS rev_last
     FROM reports r LEFT JOIN users u ON r.approved_by=u.id
     WHERE r.user_id=? AND r.report_date BETWEEN ? AND ?
     ORDER BY r.report_date DESC"
);
$reports->execute([$uid, $monthStart, $monthEnd]);
$reports = $reports->fetchAll();

// KPI submissions
$kpis = $db->prepare(
    "SELECT k.*, c.name AS cat_name, u.first_name AS rev_first, u.last_name AS rev_last
     FROM kpi_submissions k
     JOIN kpi_categories c ON k.category_id=c.id
     LEFT JOIN users u ON k.reviewed_by=u.id
     WHERE k.user_id=? AND k.submission_date BETWEEN ? AND ?
     ORDER BY k.submission_date DESC"
);
$kpis->execute([$uid, $monthStart, $monthEnd]);
$kpis = $kpis->fetchAll();

// Stats
$totalReports  = count($reports);
$approvedRep   = count(array_filter($reports, fn($r)=>$r['status']==='approved'));
$totalKpis     = count($kpis);
$approvedKpis  = count(array_filter($kpis, fn($k)=>$k['status']==='approved'));
?>

<!-- Month picker + Stats -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
  <form method="GET" style="display:flex;gap:10px;align-items:center;">
    <label class="form-label" style="margin:0;">Month:</label>
    <input type="month" name="month" class="form-control" style="max-width:180px;" value="<?= sanitize($month) ?>">
    <button type="submit" class="btn btn-outline btn-sm">Filter</button>
  </form>
  <div style="display:flex;gap:12px;flex-wrap:wrap;">
    <span class="badge badge-muted">Daily Reports: <?= $totalReports ?></span>
    <span class="badge badge-success">Approved: <?= $approvedRep ?></span>
    <span class="badge badge-muted">KPI Entries: <?= $totalKpis ?></span>
    <span class="badge badge-success">Approved KPIs: <?= $approvedKpis ?></span>
  </div>
</div>

<!-- Daily Reports -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('file-text') ?> Daily Reports — <?= date('F Y', strtotime($monthStart)) ?></div>
    <a href="submit_report.php" class="btn btn-primary btn-sm"><?= icon('send',13) ?> Submit Today</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Tasks Summary</th><th>Key Metrics</th><th>Status</th><th>Reviewed By</th><th>Comment</th></tr></thead>
      <tbody>
        <?php if (empty($reports)): ?>
        <tr><td colspan="6"><div class="empty-state" style="padding:32px;"><?= icon('inbox',32) ?><h3>No reports this month</h3></div></td></tr>
        <?php else: ?>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td>
            <div class="td-bold"><?= formatDate($r['report_date'],'M d, Y') ?></div>
            <div class="td-muted"><?= date('D', strtotime($r['report_date'])) ?></div>
          </td>
          <td style="max-width:220px;"><div class="truncate" style="font-size:13px;color:var(--text-primary);"><?= sanitize(substr($r['tasks_completed'],0,80)) ?>...</div></td>
          <td style="max-width:160px;"><div class="truncate text-sm text-muted"><?= sanitize($r['key_metrics']??'—') ?></div></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td class="td-muted text-sm"><?= $r['rev_first'] ? sanitize($r['rev_first'].' '.$r['rev_last']) : '—' ?></td>
          <td style="max-width:140px;"><div class="truncate text-sm text-muted"><?= sanitize($r['approval_comment']??'—') ?></div></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- KPI Submissions -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('bar-chart') ?> KPI Entries — <?= date('F Y', strtotime($monthStart)) ?></div>
    <a href="submit_kpi.php" class="btn btn-primary btn-sm"><?= icon('send',13) ?> Submit KPI</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Qty</th><th>Quality</th><th>Hours</th><th>Status</th><th>Notes</th></tr></thead>
      <tbody>
        <?php if (empty($kpis)): ?>
        <tr><td colspan="8"><div class="empty-state" style="padding:32px;"><?= icon('inbox',32) ?><h3>No KPI entries this month</h3></div></td></tr>
        <?php else: ?>
        <?php foreach ($kpis as $k): ?>
        <tr>
          <td>
            <div class="td-bold"><?= formatDate($k['submission_date'],'M d') ?></div>
            <div class="td-muted"><?= date('D', strtotime($k['submission_date'])) ?></div>
          </td>
          <td><span class="badge badge-gold" style="font-size:11px;"><?= sanitize($k['cat_name']) ?></span></td>
          <td style="max-width:200px;"><div class="truncate text-sm" style="color:var(--text-primary);"><?= sanitize(substr($k['task_description'],0,70)) ?></div></td>
          <td><?= $k['quantity_completed'] ?: '—' ?></td>
          <td><?= $k['quality_score'] ? $k['quality_score'].'%' : '—' ?></td>
          <td><?= $k['time_spent_hours'] ? $k['time_spent_hours'].'h' : '—' ?></td>
          <td><?= statusBadge($k['status']) ?></td>
          <td style="max-width:120px;"><div class="truncate text-sm text-muted"><?= sanitize($k['reviewer_notes']??($k['supporting_notes']??'—')) ?></div></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
