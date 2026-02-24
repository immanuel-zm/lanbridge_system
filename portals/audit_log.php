<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(3); // CEO, Principal, VPs, Finance Admin, Bursar, Auditor, IT Admin

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];
$level= (int)$user['role_level'];

// Finance audit only for finance roles or senior management
$canSeeFinance = isFinanceRole($user) || $level <= 2;
$canSeeAll     = $level <= 2; // CEO/Principal see everything

// ── Filters ───────────────────────────────────────────────────
$tab        = $_GET['tab'] ?? 'activity';
$filterUser = (int)($_GET['user'] ?? 0);
$filterDept = (int)($_GET['dept'] ?? 0);
$filterDate = $_GET['date'] ?? '';
$search     = trim($_GET['search'] ?? '');
$perPage    = 50;
$page       = max(1,(int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

$pageTitle    = 'Audit Log';
$pageSubtitle = 'System activity and financial audit trail';
require_once __DIR__ . '/../includes/header.php';

$allUsers = $db->query("SELECT id,first_name,last_name FROM users WHERE is_active=1 ORDER BY first_name")->fetchAll();
$allDepts = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();

// ── Activity log ──────────────────────────────────────────────
function buildActivityQuery(PDO $db, int $filterUser, int $filterDept, string $filterDate, string $search, bool $canSeeAll, int $uid): array {
    $where = ['1=1'];
    $params = [];
    if (!$canSeeAll) { $where[] = 'l.user_id=?'; $params[] = $uid; }
    if ($filterUser) { $where[] = 'l.user_id=?'; $params[] = $filterUser; }
    if ($filterDept) { $where[] = 'u.department_id=?'; $params[] = $filterDept; }
    if ($filterDate) { $where[] = 'DATE(l.created_at)=?'; $params[] = $filterDate; }
    if ($search)     { $where[] = "(l.action LIKE ? OR l.details LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
    return ['where'=>implode(' AND ',$where), 'params'=>$params];
}

// ── Finance audit log ─────────────────────────────────────────
function buildFinanceQuery(PDO $db, int $filterUser, string $filterDate, string $search): array {
    $where = ['1=1'];
    $params = [];
    if ($filterUser) { $where[] = 'f.user_id=?'; $params[] = $filterUser; }
    if ($filterDate) { $where[] = 'DATE(f.created_at)=?'; $params[] = $filterDate; }
    if ($search)     { $where[] = "(f.action LIKE ? OR f.table_name LIKE ? OR u.first_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); }
    return ['where'=>implode(' AND ',$where), 'params'=>$params];
}

if ($tab === 'finance' && $canSeeFinance) {
    $q = buildFinanceQuery($db, $filterUser, $filterDate, $search);
    $total = (int)$db->prepare("SELECT COUNT(*) FROM financial_audit_logs f LEFT JOIN users u ON f.user_id=u.id WHERE ".$q['where'])->execute($q['params']) ? $db->prepare("SELECT COUNT(*) FROM financial_audit_logs f LEFT JOIN users u ON f.user_id=u.id WHERE ".$q['where'])->execute($q['params']) : 0;

    // Re-run for count
    $cntStmt = $db->prepare("SELECT COUNT(*) FROM financial_audit_logs f LEFT JOIN users u ON f.user_id=u.id WHERE ".$q['where']);
    $cntStmt->execute($q['params']);
    $total = (int)$cntStmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT f.*, u.first_name, u.last_name, u.avatar, d.name AS dept_name
         FROM financial_audit_logs f
         LEFT JOIN users u ON f.user_id=u.id
         LEFT JOIN departments d ON u.department_id=d.id
         WHERE ".$q['where']."
         ORDER BY f.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($q['params']);
    $logs = $stmt->fetchAll();
} else {
    $tab = 'activity';
    $q = buildActivityQuery($db, $filterUser, $filterDept, $filterDate, $search, $canSeeAll, $uid);

    $cntStmt = $db->prepare("SELECT COUNT(*) FROM user_activity_log l LEFT JOIN users u ON l.user_id=u.id WHERE ".$q['where']);
    $cntStmt->execute($q['params']);
    $total = (int)$cntStmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT l.*, u.first_name, u.last_name, u.avatar, d.name AS dept_name
         FROM user_activity_log l
         LEFT JOIN users u ON l.user_id=u.id
         LEFT JOIN departments d ON u.department_id=d.id
         WHERE ".$q['where']."
         ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($q['params']);
    $logs = $stmt->fetchAll();
}

$totalPages = max(1, (int)ceil($total / $perPage));

// ── Summary counts ────────────────────────────────────────────
$todayActivity   = (int)$db->query("SELECT COUNT(*) FROM user_activity_log WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$todayLogins     = (int)$db->query("SELECT COUNT(*) FROM user_activity_log WHERE action='LOGIN' AND DATE(created_at)=CURDATE()")->fetchColumn();
$financeAuditCnt = $canSeeFinance ? (int)$db->query("SELECT COUNT(*) FROM financial_audit_logs WHERE DATE(created_at)>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn() : 0;
$uniqueUsersToday= (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM user_activity_log WHERE DATE(created_at)=CURDATE()")->fetchColumn();

// Action colour map
$actionColors = [
    'LOGIN'             => 'var(--success)',
    'LOGOUT'            => 'var(--text-muted)',
    'REPORT_SUBMIT'     => 'var(--info)',
    'REPORT_APPROVED'   => 'var(--success)',
    'REPORT_REJECTED'   => 'var(--danger)',
    'USER_CREATED'      => 'var(--gold)',
    'USER_UPDATED'      => 'var(--warning)',
    'PASSWORD_CHANGE'   => 'var(--warning)',
    'PASSWORD_RESET'    => 'var(--danger)',
    'ANNOUNCEMENT_POSTED'=> 'var(--info)',
    'TASK_CREATED'      => 'var(--info)',
    'TASK_UPDATED'      => 'var(--warning)',
    'PROCUREMENT_REQUEST'=> 'var(--gold)',
    'PAYROLL_APPROVED'  => 'var(--success)',
    'PAYROLL_PAID'      => 'var(--success)',
];

function actionBadgeColor(string $action, array $map): string {
    foreach ($map as $key => $col) {
        if (str_starts_with($action, $key)) return $col;
    }
    return 'var(--text-muted)';
}
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $todayActivity ?></div><div class="stat-label">Actions Today</div></div>
      <div class="stat-icon"><?= icon('activity',20) ?></div>
    </div>
    <div class="stat-delta"><?= $uniqueUsersToday ?> unique users</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $todayLogins ?></div><div class="stat-label">Logins Today</div></div>
      <div class="stat-icon"><?= icon('log-in',20) ?></div>
    </div>
    <div class="stat-delta up">Staff signed in today</div>
  </div>
  <?php if ($canSeeFinance): ?>
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $financeAuditCnt ?></div><div class="stat-label">Finance Events (30d)</div></div>
      <div class="stat-icon"><?= icon('dollar-sign',20) ?></div>
    </div>
    <div class="stat-delta">Financial audit trail entries</div>
  </div>
  <?php endif; ?>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= $total ?></div><div class="stat-label">Matching Records</div></div>
      <div class="stat-icon"><?= icon('search',20) ?></div>
    </div>
    <div class="stat-delta">Current filter results</div>
  </div>
</div>

<!-- Tab bar -->
<div style="display:flex;gap:4px;margin-bottom:20px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:4px;width:fit-content;">
  <a href="?tab=activity" class="btn btn-sm <?= $tab==='activity'?'btn-primary':'btn-outline' ?>" style="border:none;"><?= icon('activity',13) ?> Activity Log</a>
  <?php if ($canSeeFinance): ?>
  <a href="?tab=finance"  class="btn btn-sm <?= $tab==='finance'?'btn-primary':'btn-outline' ?>"  style="border:none;"><?= icon('dollar-sign',13) ?> Finance Audit</a>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-24">
  <div class="card-header"><div class="card-title"><?= icon('filter') ?> Filter Logs</div></div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="tab" value="<?= $tab ?>">
      <?php if ($canSeeAll): ?>
      <div>
        <label class="form-label" style="font-size:11px;">User</label>
        <select name="user" class="form-control" style="width:170px;">
          <option value="0">All Users</option>
          <?php foreach ($allUsers as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $filterUser===$u['id']?'selected':'' ?>><?= sanitize($u['first_name'].' '.$u['last_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Department</label>
        <select name="dept" class="form-control" style="width:160px;">
          <option value="0">All Departments</option>
          <?php foreach ($allDepts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDept===$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label class="form-label" style="font-size:11px;">Date</label>
        <input type="date" name="date" class="form-control" value="<?= sanitize($filterDate) ?>">
      </div>
      <div style="flex:1;min-width:180px;">
        <label class="form-label" style="font-size:11px;">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Action, user name, details…" value="<?= sanitize($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('search',13) ?> Filter</button>
      <a href="audit_log.php?tab=<?= $tab ?>" class="btn btn-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<!-- Log Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">
      <?= $tab==='finance' ? icon('dollar-sign').' Finance Audit Trail' : icon('activity').' Activity Log' ?>
      <span class="badge badge-muted" style="margin-left:8px;"><?= number_format($total) ?> total</span>
    </div>
    <?php if ($total > 0): ?>
    <a href="audit_log.php?tab=<?= $tab ?>&export=1&user=<?= $filterUser ?>&dept=<?= $filterDept ?>&date=<?= $filterDate ?>&search=<?= urlencode($search) ?>" class="btn btn-outline btn-sm"><?= icon('download',13) ?> Export CSV</a>
    <?php endif; ?>
  </div>

  <?php
  // Handle CSV export
  if (isset($_GET['export']) && $total > 0) {
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment; filename="audit_log_'.date('Y-m-d').'.csv"');
      $out = fopen('php://output','w');
      if ($tab === 'finance') {
          fputcsv($out,['ID','User','Action','Table','Record ID','Old Values','New Values','IP','Timestamp']);
          $expStmt = $db->prepare("SELECT f.*,u.first_name,u.last_name FROM financial_audit_logs f LEFT JOIN users u ON f.user_id=u.id WHERE ".$q['where']." ORDER BY f.created_at DESC LIMIT 5000");
          $expStmt->execute($q['params']);
          foreach ($expStmt->fetchAll() as $r) {
              fputcsv($out,[$r['id'],$r['first_name'].' '.$r['last_name'],$r['action'],$r['table_name'],$r['record_id'],$r['old_values'],$r['new_values'],$r['ip_address'],$r['created_at']]);
          }
      } else {
          fputcsv($out,['ID','User','Department','Action','Details','IP','Timestamp']);
          $expStmt = $db->prepare("SELECT l.*,u.first_name,u.last_name,d.name AS dept FROM user_activity_log l LEFT JOIN users u ON l.user_id=u.id LEFT JOIN departments d ON u.department_id=d.id WHERE ".$q['where']." ORDER BY l.created_at DESC LIMIT 5000");
          $expStmt->execute($q['params']);
          foreach ($expStmt->fetchAll() as $r) {
              fputcsv($out,[$r['id'],$r['first_name'].' '.$r['last_name'],$r['dept'],$r['action'],$r['details'],$r['ip_address'],$r['created_at']]);
          }
      }
      fclose($out); exit;
  }
  ?>

  <?php if (empty($logs)): ?>
  <div class="card-body">
    <div class="empty-state" style="padding:48px;"><?= icon('activity',44) ?><h3>No log entries found</h3><p>Try adjusting your filters.</p></div>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <?php if ($tab === 'finance'): ?>
      <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>Changes</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l):
          $col = actionBadgeColor($l['action'],$actionColors);
        ?>
        <tr>
          <td style="white-space:nowrap;">
            <div style="font-size:12px;font-weight:600;"><?= date('M d, Y',strtotime($l['created_at'])) ?></div>
            <div class="td-muted" style="font-size:10px;"><?= date('H:i:s',strtotime($l['created_at'])) ?></div>
          </td>
          <td>
            <?php if ($l['first_name']): ?>
            <div class="td-bold" style="font-size:12px;"><?= sanitize($l['first_name'].' '.$l['last_name']) ?></div>
            <?php else: ?><span class="td-muted">System</span><?php endif; ?>
          </td>
          <td><span style="font-size:11px;font-weight:700;color:<?= $col ?>;"><?= sanitize(str_replace('_',' ',$l['action'])) ?></span></td>
          <td><span class="badge badge-muted" style="font-size:10px;font-family:monospace;"><?= sanitize($l['table_name']) ?></span></td>
          <td class="td-muted" style="font-size:11px;">#<?= (int)($l['record_id']??0) ?></td>
          <td style="max-width:200px;">
            <?php if ($l['old_values'] || $l['new_values']): ?>
            <details style="font-size:11px;">
              <summary style="cursor:pointer;color:var(--gold);">View changes</summary>
              <?php if ($l['old_values']): ?>
              <div style="color:var(--danger);margin-top:4px;word-break:break-all;">Before: <?= sanitize(substr($l['old_values'],0,150)) ?></div>
              <?php endif; ?>
              <?php if ($l['new_values']): ?>
              <div style="color:var(--success);margin-top:2px;word-break:break-all;">After: <?= sanitize(substr($l['new_values'],0,150)) ?></div>
              <?php endif; ?>
            </details>
            <?php else: ?><span class="td-muted">—</span><?php endif; ?>
          </td>
          <td class="td-muted" style="font-size:10px;font-family:monospace;"><?= sanitize($l['ip_address']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php else: ?>
      <thead><tr><th>Timestamp</th><th>User</th><th>Dept</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $l):
          $col = actionBadgeColor($l['action'],$actionColors);
        ?>
        <tr>
          <td style="white-space:nowrap;">
            <div style="font-size:12px;font-weight:600;"><?= date('M d, Y',strtotime($l['created_at'])) ?></div>
            <div class="td-muted" style="font-size:10px;"><?= date('H:i:s',strtotime($l['created_at'])) ?></div>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <?php if (!empty($l['avatar'])): ?>
              <img src="<?= sanitize(SITE_URL.'/'.ltrim($l['avatar'],'/')) ?>" style="width:24px;height:24px;border-radius:50%;object-fit:cover;" alt="">
              <?php else: ?>
              <div class="avatar avatar-sm" style="width:24px;height:24px;font-size:8px;"><?= $l['first_name']?getInitials($l['first_name'],$l['last_name']):'?' ?></div>
              <?php endif; ?>
              <span class="td-bold" style="font-size:12px;"><?= $l['first_name'] ? sanitize($l['first_name'].' '.$l['last_name']) : 'System' ?></span>
            </div>
          </td>
          <td><span class="badge badge-muted" style="font-size:10px;"><?= sanitize($l['dept_name']??'—') ?></span></td>
          <td><span style="font-size:11px;font-weight:700;color:<?= $col ?>;"><?= sanitize(str_replace('_',' ',$l['action'])) ?></span></td>
          <td style="max-width:240px;font-size:12px;color:var(--text-secondary);">
            <?= sanitize(substr($l['details']??'',0,100)) ?>
            <?= strlen($l['details']??'')>100?'…':'' ?>
          </td>
          <td class="td-muted" style="font-size:10px;font-family:monospace;"><?= sanitize($l['ip_address']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php endif; ?>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);">
    <span style="font-size:13px;color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?> · <?= number_format($total) ?> records</span>
    <div style="display:flex;gap:6px;">
      <?php if ($page > 1): ?>
      <a href="?tab=<?= $tab ?>&page=<?= $page-1 ?>&user=<?= $filterUser ?>&dept=<?= $filterDept ?>&date=<?= $filterDate ?>&search=<?= urlencode($search) ?>" class="btn btn-outline btn-sm"><?= icon('chevron-left',13) ?> Prev</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?tab=<?= $tab ?>&page=<?= $page+1 ?>&user=<?= $filterUser ?>&dept=<?= $filterDept ?>&date=<?= $filterDate ?>&search=<?= urlencode($search) ?>" class="btn btn-outline btn-sm">Next <?= icon('chevron-right',13) ?></a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
