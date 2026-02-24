<?php
$pageTitle    = 'Audit Trail';
$pageSubtitle = 'Full system activity log';
require_once __DIR__ . '/../includes/header.php';
requireRole(1);

$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$action   = $_GET['action_filter'] ?? '';
$page     = max(1,(int)($_GET['page']??1));
$perPage  = 50;
$offset   = ($page-1)*$perPage;

$actionWhere = $action ? "AND l.action='$action'" : '';
$total = (int)$db->query("SELECT COUNT(*) FROM audit_log l WHERE DATE(l.created_at) BETWEEN '$dateFrom' AND '$dateTo' $actionWhere")->fetchColumn();
$pages = ceil($total/$perPage);

$logs = $db->query(
    "SELECT l.*, u.first_name, u.last_name FROM audit_log l
     LEFT JOIN users u ON l.user_id=u.id
     WHERE DATE(l.created_at) BETWEEN '$dateFrom' AND '$dateTo' $actionWhere
     ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset"
)->fetchAll();

$actions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$actionColors = [
    'LOGIN'=>'badge-info','LOGOUT'=>'badge-muted','REPORT_SUBMIT'=>'badge-warning',
    'REPORT_APPROVED'=>'badge-success','REPORT_REJECTED'=>'badge-danger',
    'PASSWORD_CHANGE'=>'badge-info','USER_CREATED'=>'badge-success','USER_UPDATED'=>'badge-warning',
    'USER_DEACTIVATED'=>'badge-danger','PASSWORD_RESET'=>'badge-warning',
];
?>

<!-- Filters -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px 20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="margin:0;">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= sanitize($dateFrom) ?>">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= sanitize($dateTo) ?>">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Action</label>
        <select name="action_filter" class="form-control">
          <option value="">All Actions</option>
          <?php foreach ($actions as $a): ?>
          <option value="<?= sanitize($a) ?>" <?= $action===$a?'selected':'' ?>><?= sanitize($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary"><?= icon('shield',14) ?> Filter</button>
        <a href="audit.php" class="btn btn-outline">Reset</a>
        <a href="audit.php?from=<?= $dateFrom ?>&to=<?= $dateTo ?>&action_filter=<?= urlencode($action) ?>&format=csv" class="btn btn-outline"><?= icon('download',14) ?> CSV</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('shield') ?> Audit Log <span class="badge badge-muted" style="margin-left:6px;"><?= $total ?> records</span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="5"><div class="empty-state" style="padding:32px;"><?= icon('shield',32) ?><h3>No audit records found</h3></div></td></tr>
        <?php else: ?>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td>
            <div class="td-bold" style="font-size:12px;"><?= date('M d, Y', strtotime($l['created_at'])) ?></div>
            <div class="td-muted"><?= date('h:i:s A', strtotime($l['created_at'])) ?></div>
          </td>
          <td>
            <?php if ($l['first_name']): ?>
            <div class="td-bold"><?= sanitize($l['first_name'].' '.$l['last_name']) ?></div>
            <?php else: ?>
            <span class="td-muted">System</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $actionColors[$l['action']] ?? 'badge-muted' ?>" style="font-size:10px;"><?= sanitize($l['action']) ?></span></td>
          <td class="td-muted text-sm" style="max-width:220px;"><div class="truncate"><?= sanitize($l['details']??'—') ?></div></td>
          <td class="td-muted text-sm"><?= sanitize($l['ip_address']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div style="padding:16px 24px;display:flex;gap:8px;align-items:center;border-top:1px solid var(--border);">
    <?php for ($p=1;$p<=$pages;$p++): ?>
    <a href="?from=<?= $dateFrom ?>&to=<?= $dateTo ?>&action_filter=<?= urlencode($action) ?>&page=<?= $p ?>"
       class="btn <?= $p===$page?'btn-primary':'btn-outline' ?> btn-sm"><?= $p ?></a>
    <?php endfor; ?>
    <span class="td-muted text-sm">Page <?= $page ?> of <?= $pages ?></span>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
