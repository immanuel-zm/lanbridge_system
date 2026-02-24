<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'])); exit;
}

$isAuditor  = ($user['role_slug'] === 'auditor');
$canBudget  = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$uid        = (int)$user['id'];
$db         = getDB();
$fiscalYear = getSetting('finance_fiscal_year', date('Y'));

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_budget' && $canBudget) {
        $deptId  = (int)($_POST['dept_id'] ?? 0);
        $year    = trim($_POST['fiscal_year'] ?? $fiscalYear);
        $amount  = abs((float)($_POST['amount'] ?? 0));
        $notes   = trim($_POST['notes'] ?? '');

        if ($deptId && $amount > 0) {
            // Upsert
            $existing = $db->prepare("SELECT id FROM departmental_budget WHERE department_id=? AND fiscal_year=?");
            $existing->execute([$deptId, $year]);
            $row = $existing->fetch();

            if ($row) {
                $old = $db->query("SELECT allocated_amount FROM departmental_budget WHERE id={$row['id']}")->fetchColumn();
                $db->prepare("UPDATE departmental_budget SET allocated_amount=?,approved_by=?,approved_at=NOW(),notes=?,updated_at=NOW() WHERE id=?")
                   ->execute([$amount,$uid,$notes,$row['id']]);
                logFinanceAudit($uid,'BUDGET_UPDATED','departmental_budget',$row['id'],
                    ['allocated_amount'=>$old],['allocated_amount'=>$amount]);
            } else {
                $db->prepare("INSERT INTO departmental_budget (department_id,fiscal_year,allocated_amount,approved_by,approved_at,notes) VALUES (?,?,?,?,NOW(),?)")
                   ->execute([$deptId,$year,$amount,$uid,$notes]);
                logFinanceAudit($uid,'BUDGET_SET','departmental_budget',(int)$db->lastInsertId(),null,
                    ['dept_id'=>$deptId,'fiscal_year'=>$year,'amount'=>$amount]);
            }
            logActivity($uid,'FINANCE_BUDGET','Budget set for dept ID '.$deptId.' FY '.$year.': '.formatMoney($amount));
            setFlash('success','✅ Budget allocation saved.');
        } else {
            setFlash('danger','❌ Department and amount are required.');
        }
        header('Location: finance_budget.php'); exit;
    }

    if ($action === 'add_expenditure') {
        $deptId  = (int)($_POST['dept_id']     ?? 0);
        $year    = trim($_POST['fiscal_year']   ?? $fiscalYear);
        $cat     = trim($_POST['category']      ?? '');
        $desc    = trim($_POST['description']   ?? '');
        $amount  = abs((float)($_POST['amount'] ?? 0));
        $date    = $_POST['exp_date']           ?? date('Y-m-d');

        if ($deptId && $cat && $desc && $amount > 0 && $date) {
            $autoStatus = $canBudget ? 'approved' : 'pending';
            $approvedBy = $canBudget ? $uid : null;
            $db->prepare(
                "INSERT INTO expenditure (department_id,fiscal_year,category,description,amount,recorded_by,approved_by,status,expenditure_date)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([$deptId,$year,$cat,$desc,$amount,$uid,$approvedBy,$autoStatus,$date]);
            logFinanceAudit($uid,'EXPENDITURE_RECORDED','expenditure',(int)$db->lastInsertId(),null,
                ['dept_id'=>$deptId,'amount'=>$amount,'category'=>$cat,'status'=>$autoStatus]);
            logActivity($uid,'FINANCE_EXPENDITURE','Expenditure '.formatMoney($amount).' for dept ID '.$deptId);
            setFlash('success','✅ Expenditure recorded.');
        } else {
            setFlash('danger','❌ All fields are required.');
        }
        header('Location: finance_budget.php'); exit;
    }

    if ($action === 'approve_exp' && $canBudget) {
        $expId  = (int)($_POST['exp_id']  ?? 0);
        $status = ($_POST['new_status'] ?? '') === 'rejected' ? 'rejected' : 'approved';
        $reason = trim($_POST['reason'] ?? '');
        $old    = $db->query("SELECT * FROM expenditure WHERE id=$expId")->fetch();
        if ($old) {
            $db->prepare("UPDATE expenditure SET status=?,approved_by=?,rejection_reason=? WHERE id=?")
               ->execute([$status,$uid,$reason,$expId]);
            logFinanceAudit($uid,'EXP_'.strtoupper($status),'expenditure',$expId,
                ['status'=>$old['status']],['status'=>$status]);
            setFlash('success','✅ Expenditure '.ucfirst($status).'.');
        }
        header('Location: finance_budget.php'); exit;
    }
}

// ── Data ──────────────────────────────────────────────────────
$allDepts = $db->query("SELECT id,name,code FROM departments ORDER BY name")->fetchAll();
$years    = [$fiscalYear, (string)((int)$fiscalYear-1), (string)((int)$fiscalYear+1)];

$budgets = $db->query(
    "SELECT d.id, d.name, d.code,
            b.id AS budget_id, b.allocated_amount, b.fiscal_year,
            COALESCE(SUM(CASE WHEN e.status='approved' THEN e.amount ELSE 0 END),0) AS spent,
            COALESCE(SUM(CASE WHEN e.status='pending'  THEN e.amount ELSE 0 END),0) AS pending_exp
     FROM departments d
     LEFT JOIN departmental_budget b ON b.department_id=d.id AND b.fiscal_year='$fiscalYear'
     LEFT JOIN expenditure e ON e.department_id=d.id AND e.fiscal_year='$fiscalYear'
     GROUP BY d.id ORDER BY d.name"
)->fetchAll();

$pendingExp = $db->query(
    "SELECT e.*,d.name AS dept_name,u.first_name,u.last_name
     FROM expenditure e
     JOIN departments d ON e.department_id=d.id
     JOIN users u ON e.recorded_by=u.id
     WHERE e.status='pending'
     ORDER BY e.created_at ASC LIMIT 20"
)->fetchAll();

$recentExp = $db->query(
    "SELECT e.*,d.name AS dept_name
     FROM expenditure e
     JOIN departments d ON e.department_id=d.id
     WHERE e.status='approved'
     ORDER BY e.expenditure_date DESC, e.created_at DESC LIMIT 15"
)->fetchAll();

$pageTitle    = 'Budget & Expenditure';
$pageSubtitle = 'Fiscal Year '.$fiscalYear.' — Department Budget Control';
require_once __DIR__ . '/../includes/header.php';

// Totals
$totalBudget = array_sum(array_column($budgets, 'allocated_amount'));
$totalSpent  = array_sum(array_column($budgets, 'spent'));
$totalPend   = array_sum(array_column($budgets, 'pending_exp'));
?>

<!-- Overview Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:16px;"><?= formatMoney($totalBudget) ?></div><div class="stat-label">Total Budget (FY <?= $fiscalYear ?>)</div></div>
      <div class="stat-icon"><?= icon('layers',20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('briefcase',12) ?> Across all departments</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:16px;"><?= formatMoney($totalBudget - $totalSpent) ?></div><div class="stat-label">Remaining Budget</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta up"><?= $totalBudget > 0 ? round((($totalBudget-$totalSpent)/$totalBudget)*100) : 0 ?>% available</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:16px;"><?= formatMoney($totalSpent) ?></div><div class="stat-label">Total Spent (Approved)</div></div>
      <div class="stat-icon"><?= icon('download',20) ?></div>
    </div>
    <div class="stat-delta"><?= $totalBudget > 0 ? round(($totalSpent/$totalBudget)*100) : 0 ?>% of total budget used</div>
  </div>
  <div class="stat-card <?= count($pendingExp) > 0 ? '' : 'blue' ?>" style="<?= count($pendingExp) > 0 ? '--card-accent:#f5a623;' : '' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= count($pendingExp) ?></div><div class="stat-label">Pending Expenditures</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta <?= count($pendingExp) > 0 ? 'down' : 'up' ?>"><?= formatMoney($totalPend) ?> awaiting approval</div>
  </div>
</div>

<!-- Actions -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <?php if ($canBudget): ?>
  <button class="btn btn-primary" onclick="openModal('setBudgetModal')"><?= icon('layers',14) ?> Set / Update Budget</button>
  <?php endif; ?>
  <button class="btn btn-outline" onclick="openModal('addExpModal')"><?= icon('send',14) ?> Record Expenditure</button>
</div>

<!-- Budget vs Spend Table -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('bar-chart') ?> Department Budget Overview — FY <?= $fiscalYear ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Department</th><th>Budget Allocated</th><th>Spent</th><th>Pending</th><th>Remaining</th><th>Utilisation</th></tr>
      </thead>
      <tbody>
        <?php foreach ($budgets as $b):
          $alloc     = (float)$b['allocated_amount'];
          $spent     = (float)$b['spent'];
          $pending   = (float)$b['pending_exp'];
          $remaining = $alloc - $spent;
          $pct       = $alloc > 0 ? min(100, round(($spent/$alloc)*100)) : 0;
          $color     = $pct >= 95 ? 'red' : ($pct >= 75 ? 'gold' : 'green');
        ?>
        <tr style="<?= $remaining < 0 ? 'background:rgba(232,85,106,0.04);' : '' ?>">
          <td><div class="td-bold"><?= sanitize($b['name']) ?></div><div class="td-muted"><?= sanitize($b['code']) ?></div></td>
          <td><?= $alloc > 0 ? formatMoney($alloc) : '<span class="td-muted">Not set</span>' ?></td>
          <td style="color:var(--danger);"><?= formatMoney($spent) ?></td>
          <td><span class="badge badge-warning"><?= formatMoney($pending) ?></span></td>
          <td style="color:<?= $remaining < 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:600;">
            <?= formatMoney(abs($remaining)) ?><?= $remaining < 0 ? ' <small>(over)</small>' : '' ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:130px;">
              <div class="progress" style="flex:1;"><div class="progress-bar <?= $color ?>" style="width:<?= $pct ?>%;"></div></div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);flex-shrink:0;"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pending Expenditure Approvals -->
<?php if ($canBudget && !empty($pendingExp)): ?>
<div class="card mb-24" style="border:1px solid var(--warning-border);">
  <div class="card-header" style="background:var(--warning-dim);">
    <div class="card-title" style="color:var(--warning);"><?= icon('clock',16) ?> Pending Expenditure Approvals
      <span class="badge badge-warning" style="margin-left:8px;"><?= count($pendingExp) ?></span>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Department</th><th>Category</th><th>Description</th><th>Amount</th><th>Date</th><th>Recorded By</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($pendingExp as $e): ?>
        <tr>
          <td class="td-bold"><?= sanitize($e['dept_name']) ?></td>
          <td><?= sanitize($e['category']) ?></td>
          <td style="font-size:12.5px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize($e['description']) ?></td>
          <td class="td-bold" style="color:var(--danger);"><?= formatMoney($e['amount']) ?></td>
          <td class="td-muted"><?= formatDate($e['expenditure_date'],'M d') ?></td>
          <td class="td-muted"><?= sanitize($e['first_name'][0].'.'.$e['last_name']) ?></td>
          <td>
            <div style="display:flex;gap:4px;">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="approve_exp">
                <input type="hidden" name="exp_id" value="<?= $e['id'] ?>">
                <input type="hidden" name="new_status" value="approved">
                <button type="submit" class="btn btn-success btn-sm" data-confirm="Approve this expenditure?">✓ Approve</button>
              </form>
              <button class="btn btn-danger btn-sm" onclick="openExpRejectModal(<?= $e['id'] ?>)">✗ Reject</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Recent Approved Expenditures -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('clock') ?> Recent Approved Expenditures</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Department</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
      <tbody>
        <?php if (empty($recentExp)): ?>
        <tr><td colspan="5"><div class="empty-state" style="padding:28px;"><?= icon('inbox',28) ?><p>No expenditures recorded</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentExp as $e): ?>
        <tr>
          <td class="td-muted"><?= formatDate($e['expenditure_date'],'M d, Y') ?></td>
          <td class="td-bold"><?= sanitize($e['dept_name']) ?></td>
          <td><?= sanitize($e['category']) ?></td>
          <td style="font-size:12.5px;color:var(--text-secondary);"><?= sanitize($e['description']) ?></td>
          <td class="td-bold" style="color:var(--danger);"><?= formatMoney($e['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══ MODALS ═══ -->
<?php if ($canBudget): ?>
<div class="modal-overlay" id="setBudgetModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('layers',16) ?> Set Department Budget</div>
      <button class="modal-close" onclick="closeModal('setBudgetModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="set_budget">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Department <span style="color:var(--danger)">*</span></label>
          <select name="dept_id" class="form-control" required>
            <option value="">— Select —</option>
            <?php foreach ($allDepts as $d): ?>
            <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fiscal Year <span style="color:var(--danger)">*</span></label>
            <select name="fiscal_year" class="form-control" required>
              <?php foreach ($years as $y): ?>
              <option value="<?= $y ?>" <?= $y===$fiscalYear?'selected':'' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (ZMW) <span style="color:var(--danger)">*</span></label>
            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="1" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional justification or notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('setBudgetModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Budget</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="addExpModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Record Expenditure</div>
      <button class="modal-close" onclick="closeModal('addExpModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_expenditure">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department <span style="color:var(--danger)">*</span></label>
            <select name="dept_id" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach ($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Fiscal Year</label>
            <select name="fiscal_year" class="form-control">
              <?php foreach ($years as $y): ?>
              <option value="<?= $y ?>" <?= $y===$fiscalYear?'selected':'' ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
            <input type="text" name="category" class="form-control" placeholder="e.g. Stationery, Travel" required>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (ZMW) <span style="color:var(--danger)">*</span></label>
            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--danger)">*</span></label>
          <textarea name="description" class="form-control" rows="2" placeholder="What was this expenditure for?" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Date <span style="color:var(--danger)">*</span></label>
          <input type="date" name="exp_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <?php if (!$canBudget): ?>
        <div class="alert alert-info" style="font-size:12px;"><?= icon('clock',13) ?> Requires Bursar approval.</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addExpModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Expenditure</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="rejectExpModal">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <div class="modal-title" style="color:var(--danger);"><?= icon('x',16) ?> Reject Expenditure</div>
      <button class="modal-close" onclick="closeModal('rejectExpModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="approve_exp">
      <input type="hidden" name="new_status" value="rejected">
      <input type="hidden" name="exp_id" id="rejectExpId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--danger)">*</span></label>
          <textarea name="reason" class="form-control" rows="3" placeholder="Reason for rejection..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('rejectExpModal')">Cancel</button>
        <button type="submit" class="btn btn-danger">Reject</button>
      </div>
    </form>
  </div>
</div>

<script>
function openExpRejectModal(id) {
  document.getElementById('rejectExpId').value = id;
  openModal('rejectExpModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
