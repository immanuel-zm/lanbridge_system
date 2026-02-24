<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'])); exit;
}

$isAuditor   = ($user['role_slug'] === 'auditor');
$canApprove  = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$canPrepare  = !$isAuditor;
$uid         = (int)$user['id'];
$db          = getDB();

$currentPeriod = date('Y-m');
$period        = $_GET['period'] ?? $currentPeriod;
// Validate period format
if (!preg_match('/^\d{4}-\d{2}$/', $period)) $period = $currentPeriod;
$periodLabel = date('F Y', strtotime($period.'-01'));

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $action = $_POST['action'] ?? '';

    // Prepare/update a single payroll entry
    if ($action === 'save_payroll' && $canPrepare) {
        $empId      = (int)($_POST['user_id'] ?? 0);
        $per        = trim($_POST['pay_period'] ?? $period);
        $basic      = abs((float)($_POST['basic_salary'] ?? 0));
        $allowances = abs((float)($_POST['allowances'] ?? 0));
        $deductions = abs((float)($_POST['deductions'] ?? 0));
        $tax        = abs((float)($_POST['tax_amount'] ?? 0));
        $net        = $basic + $allowances - $deductions - $tax;
        $method     = trim($_POST['payment_method'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');

        if ($empId && $basic > 0 && $per) {
            // Check existing
            $existing = $db->prepare("SELECT id,status FROM payroll WHERE user_id=? AND pay_period=?");
            $existing->execute([$empId,$per]);
            $row = $existing->fetch();

            if ($row && $row['status'] === 'paid') {
                setFlash('danger','❌ Cannot modify a payroll record that has already been paid.');
            } elseif ($row) {
                $db->prepare(
                    "UPDATE payroll SET basic_salary=?,allowances=?,deductions=?,tax_amount=?,
                     net_salary=?,payment_method=?,notes=?,status='draft' WHERE id=?"
                )->execute([$basic,$allowances,$deductions,$tax,$net,$method,$notes,$row['id']]);
                logFinanceAudit($uid,'PAYROLL_UPDATED','payroll',$row['id'],null,['net'=>$net,'period'=>$per]);
                setFlash('success','✅ Payroll updated for period '.$per.'.');
            } else {
                $db->prepare(
                    "INSERT INTO payroll (user_id,pay_period,basic_salary,allowances,deductions,
                     tax_amount,net_salary,payment_method,notes,prepared_by,status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,'draft')"
                )->execute([$empId,$per,$basic,$allowances,$deductions,$tax,$net,$method,$notes,$uid]);
                $newId = (int)$db->lastInsertId();
                logFinanceAudit($uid,'PAYROLL_PREPARED','payroll',$newId,null,['user'=>$empId,'net'=>$net,'period'=>$per]);
                setFlash('success','✅ Payroll entry prepared.');
            }
        } else {
            setFlash('danger','❌ Employee and basic salary are required.');
        }
        header('Location: finance_payroll.php?period='.$per); exit;
    }

    // Approve entire period payroll
    if ($action === 'approve_period' && $canApprove) {
        $per = trim($_POST['pay_period'] ?? $period);
        $db->prepare(
            "UPDATE payroll SET status='approved',approved_by=? WHERE pay_period=? AND status='draft'"
        )->execute([$uid,$per]);
        $count = $db->rowCount();
        logFinanceAudit($uid,'PAYROLL_PERIOD_APPROVED','payroll',null,null,['period'=>$per,'count'=>$count]);
        logActivity($uid,'PAYROLL_APPROVED','Payroll approved for period '.$per.' ('.$count.' records)');
        setFlash('success','✅ '.$count.' payroll records approved for '.$per.'.');
        header('Location: finance_payroll.php?period='.$per); exit;
    }

    // Mark as paid
    if ($action === 'mark_paid' && $canApprove) {
        $per    = trim($_POST['pay_period'] ?? $period);
        $payRef = trim($_POST['payment_ref'] ?? generateRef('PAY'));
        $db->prepare(
            "UPDATE payroll SET status='paid',payment_ref=?,paid_at=NOW() WHERE pay_period=? AND status='approved'"
        )->execute([$payRef,$per]);
        $count = $db->rowCount();
        logFinanceAudit($uid,'PAYROLL_PAID','payroll',null,null,['period'=>$per,'ref'=>$payRef,'count'=>$count]);
        logActivity($uid,'PAYROLL_PAID','Payroll marked paid for '.$per.'. Ref: '.$payRef);

        // Notify each paid employee
        $paid = $db->query(
            "SELECT p.user_id, p.net_salary FROM payroll p WHERE p.pay_period='$per' AND p.status='paid'"
        )->fetchAll();
        foreach ($paid as $p) {
            sendNotification((int)$p['user_id'],
                '💰 Salary Processed — '.$periodLabel,
                'Your net salary of '.formatMoney((float)$p['net_salary']).' has been processed. Ref: '.$payRef,
                'success', SITE_URL.'/portals/profile.php'
            );
        }
        setFlash('success','✅ '.$count.' payments processed. Ref: '.$payRef);
        header('Location: finance_payroll.php?period='.$per); exit;
    }
}

$pageTitle    = 'Payroll';
$pageSubtitle = 'Staff Salary Management — '.$periodLabel;
require_once __DIR__ . '/../includes/header.php';

// ── Period payroll data ───────────────────────────────────────
$payrollRecords = $db->query(
    "SELECT p.*,
            u.first_name, u.last_name, u.position, u.avatar,
            d.name AS dept_name,
            pr.first_name AS prep_first, pr.last_name AS prep_last,
            ap.first_name AS appr_first, ap.last_name AS appr_last
     FROM payroll p
     JOIN users u ON p.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users pr ON p.prepared_by=pr.id
     LEFT JOIN users ap ON p.approved_by=ap.id
     WHERE p.pay_period='$period'
     ORDER BY d.name, u.first_name"
)->fetchAll();

// All active staff for adding entries
$allStaff = $db->query(
    "SELECT u.id, u.first_name, u.last_name, u.position, d.name AS dept_name,
            (SELECT id FROM payroll WHERE user_id=u.id AND pay_period='$period') AS has_payroll
     FROM users u
     LEFT JOIN departments d ON u.department_id=d.id
     WHERE u.is_active=1
     ORDER BY d.name, u.first_name"
)->fetchAll();
$staffWithout = array_filter($allStaff, fn($s) => !$s['has_payroll']);

// Summary stats for this period
$totalGross  = (float)$db->query("SELECT COALESCE(SUM(basic_salary+allowances),0) FROM payroll WHERE pay_period='$period'")->fetchColumn();
$totalNet    = (float)$db->query("SELECT COALESCE(SUM(net_salary),0) FROM payroll WHERE pay_period='$period'")->fetchColumn();
$totalTax    = (float)$db->query("SELECT COALESCE(SUM(tax_amount),0) FROM payroll WHERE pay_period='$period'")->fetchColumn();
$draftCount  = (int)$db->query("SELECT COUNT(*) FROM payroll WHERE pay_period='$period' AND status='draft'")->fetchColumn();
$approvedCnt = (int)$db->query("SELECT COUNT(*) FROM payroll WHERE pay_period='$period' AND status='approved'")->fetchColumn();
$paidCount   = (int)$db->query("SELECT COUNT(*) FROM payroll WHERE pay_period='$period' AND status='paid'")->fetchColumn();
$totalCount  = count($payrollRecords);

// Period status
$periodStatus = $paidCount > 0 && $paidCount === $totalCount ? 'paid'
    : ($approvedCnt > 0 && ($approvedCnt + $paidCount) === $totalCount ? 'approved'
    : ($totalCount > 0 ? 'draft' : 'empty'));

// Available periods for selector (last 12 months + next month)
$periods = [];
for ($i = -11; $i <= 1; $i++) {
    $p = date('Y-m', strtotime("$i months"));
    $periods[$p] = date('F Y', strtotime($p.'-01'));
}
krsort($periods);

$statusColor = ['draft'=>'var(--warning)','approved'=>'var(--info)','paid'=>'var(--success)','empty'=>'var(--text-muted)'];
$statusBadge = ['draft'=>'badge-warning','approved'=>'badge-info','paid'=>'badge-success','empty'=>'badge-muted'];
?>

<!-- Period selector + controls -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
  <form method="GET" style="display:flex;align-items:center;gap:8px;">
    <label style="font-size:13px;color:var(--text-muted);">Pay Period:</label>
    <select name="period" class="form-control" style="width:170px;" onchange="this.form.submit()">
      <?php foreach ($periods as $p => $l): ?>
      <option value="<?= $p ?>" <?= $p===$period?'selected':'' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <span class="badge <?= $statusBadge[$periodStatus] ?>" style="font-size:12px;padding:6px 12px;">
    <?= ucfirst($periodStatus) === 'Empty' ? 'No records yet' : ucfirst($periodStatus) ?>
  </span>
  <div style="margin-left:auto;display:flex;gap:8px;">
    <?php if ($canPrepare && count($staffWithout) > 0): ?>
    <button class="btn btn-outline btn-sm" onclick="openModal('addPayrollModal')"><?= icon('plus',13) ?> Add Entry</button>
    <?php endif; ?>
    <?php if ($canApprove && $draftCount > 0): ?>
    <form method="POST">
      <input type="hidden" name="action" value="approve_period">
      <input type="hidden" name="pay_period" value="<?= $period ?>">
      <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Approve all <?= $draftCount ?> draft entries for <?= $periodLabel ?>?')"><?= icon('check-square',13) ?> Approve All (<?= $draftCount ?>)</button>
    </form>
    <?php endif; ?>
    <?php if ($canApprove && $approvedCnt > 0): ?>
    <button class="btn btn-sm" style="background:var(--success);color:#fff;border:none;" onclick="openModal('markPaidModal')"><?= icon('dollar-sign',13) ?> Process Payment (<?= $approvedCnt ?>)</button>
    <?php endif; ?>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:20px;"><?= formatMoney($totalGross) ?></div><div class="stat-label">Total Gross</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta"><?= $totalCount ?> staff on payroll</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:20px;"><?= formatMoney($totalNet) ?></div><div class="stat-label">Total Net Pay</div></div>
      <div class="stat-icon"><?= icon('dollar-sign',20) ?></div>
    </div>
    <div class="stat-delta up">After deductions and tax</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:20px;"><?= formatMoney($totalTax) ?></div><div class="stat-label">Total Tax / PAYE</div></div>
      <div class="stat-icon"><?= icon('percent',20) ?></div>
    </div>
    <div class="stat-delta">Tax withheld this period</div>
  </div>
  <div class="stat-card <?= $paidCount===$totalCount&&$totalCount>0?'green':($approvedCnt>0?'blue':'orange') ?>">
    <div class="stat-top">
      <div>
        <div class="stat-number"><?= $paidCount ?>/<?= $totalCount ?></div>
        <div class="stat-label">Paid / Total</div>
      </div>
      <div class="stat-icon"><?= icon('users',20) ?></div>
    </div>
    <div class="stat-delta"><?= $draftCount ?> draft · <?= $approvedCnt ?> approved</div>
  </div>
</div>

<!-- Payroll Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('dollar-sign') ?> <?= $periodLabel ?> Payroll</div>
    <?php if ($totalCount > 0): ?>
    <a href="export.php?type=payroll&period=<?= $period ?>" class="btn btn-outline btn-sm"><?= icon('download',13) ?> Export</a>
    <?php endif; ?>
  </div>

  <?php if (empty($payrollRecords)): ?>
  <div class="card-body">
    <div class="empty-state" style="padding:56px 0;">
      <?= icon('dollar-sign',44) ?>
      <h3>No payroll records for <?= $periodLabel ?></h3>
      <p style="color:var(--text-muted);">Add staff payroll entries to get started for this period.</p>
      <?php if ($canPrepare): ?>
      <button class="btn btn-primary" style="margin-top:14px;" onclick="openModal('addPayrollModal')"><?= icon('plus',14) ?> Add Payroll Entry</button>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Staff</th>
          <th>Department</th>
          <th>Basic</th>
          <th>Allowances</th>
          <th>Deductions</th>
          <th>Tax/PAYE</th>
          <th style="color:var(--gold);">Net Pay</th>
          <th>Status</th>
          <?php if ($canPrepare): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payrollRecords as $p): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <?php if (!empty($p['avatar'])): ?>
              <img src="<?= sanitize(SITE_URL.'/'.ltrim($p['avatar'],'/')) ?>" style="width:28px;height:28px;border-radius:50%;object-fit:cover;" alt="">
              <?php else: ?>
              <div class="avatar avatar-sm"><?= getInitials($p['first_name'],$p['last_name']) ?></div>
              <?php endif; ?>
              <div>
                <div class="td-bold"><?= sanitize($p['first_name'].' '.$p['last_name']) ?></div>
                <div class="td-muted" style="font-size:10px;"><?= sanitize($p['position']??'—') ?></div>
              </div>
            </div>
          </td>
          <td class="td-muted text-sm"><?= sanitize($p['dept_name']??'—') ?></td>
          <td style="font-weight:600;"><?= formatMoney((float)$p['basic_salary']) ?></td>
          <td style="color:var(--success);">+<?= formatMoney((float)$p['allowances']) ?></td>
          <td style="color:var(--danger);">-<?= formatMoney((float)$p['deductions']) ?></td>
          <td style="color:var(--warning);">-<?= formatMoney((float)$p['tax_amount']) ?></td>
          <td style="font-weight:800;font-size:14px;color:var(--gold);"><?= formatMoney((float)$p['net_salary']) ?></td>
          <td>
            <span style="font-size:12px;font-weight:700;color:<?= ['draft'=>'var(--warning)','approved'=>'var(--info)','paid'=>'var(--success)'][$p['status']] ?? 'var(--text-muted)' ?>;">
              <?= ucfirst($p['status']) ?>
            </span>
            <?php if ($p['paid_at']): ?>
            <div class="td-muted" style="font-size:10px;"><?= date('M d',strtotime($p['paid_at'])) ?></div>
            <?php endif; ?>
          </td>
          <?php if ($canPrepare): ?>
          <td>
            <?php if ($p['status'] !== 'paid'): ?>
            <button class="btn btn-outline btn-sm" onclick='openEditPayroll(<?= json_encode([
                "id"=>$p["id"],"user_id"=>$p["user_id"],"name"=>$p["first_name"]." ".$p["last_name"],
                "basic"=>$p["basic_salary"],"allow"=>$p["allowances"],"deduct"=>$p["deductions"],
                "tax"=>$p["tax_amount"],"method"=>$p["payment_method"],"notes"=>$p["notes"]
            ]) ?>)'>
              <?= icon('edit',12) ?> Edit
            </button>
            <?php else: ?>
            <span class="td-muted" style="font-size:11px;">Locked</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <!-- Totals footer -->
      <tfoot>
        <tr style="background:var(--bg-elevated);font-weight:700;">
          <td colspan="2" style="padding:12px 16px;font-size:13px;color:var(--text-secondary);">TOTALS — <?= $totalCount ?> staff</td>
          <td style="padding:12px 16px;"><?= formatMoney((float)$db->query("SELECT COALESCE(SUM(basic_salary),0) FROM payroll WHERE pay_period='$period'")->fetchColumn()) ?></td>
          <td style="color:var(--success);padding:12px 16px;">+<?= formatMoney((float)$db->query("SELECT COALESCE(SUM(allowances),0) FROM payroll WHERE pay_period='$period'")->fetchColumn()) ?></td>
          <td style="color:var(--danger);padding:12px 16px;">-<?= formatMoney((float)$db->query("SELECT COALESCE(SUM(deductions),0) FROM payroll WHERE pay_period='$period'")->fetchColumn()) ?></td>
          <td style="color:var(--warning);padding:12px 16px;">-<?= formatMoney($totalTax) ?></td>
          <td style="color:var(--gold);font-size:15px;padding:12px 16px;"><?= formatMoney($totalNet) ?></td>
          <td colspan="<?= $canPrepare?2:1 ?>"></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ── MODALS ───────────────────────────────────────────────── -->

<!-- Add Payroll Modal -->
<?php if ($canPrepare && count($staffWithout) > 0): ?>
<div class="modal-overlay" id="addPayrollModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('plus-circle') ?> Add Payroll Entry — <?= $periodLabel ?></div>
      <button class="modal-close" onclick="closeModal('addPayrollModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_payroll">
      <input type="hidden" name="pay_period" value="<?= $period ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Staff Member <span style="color:var(--danger);">*</span></label>
          <select name="user_id" class="form-control" required>
            <option value="">— Select staff member —</option>
            <?php foreach ($staffWithout as $s): ?>
            <option value="<?= $s['id'] ?>"><?= sanitize($s['first_name'].' '.$s['last_name']) ?> — <?= sanitize($s['dept_name']??'') ?><?= $s['position']?' ('.sanitize($s['position']).')':'' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Basic Salary (ZMW) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="basic_salary" id="basicSalary" class="form-control" placeholder="0.00" min="0" step="0.01" required oninput="calcNet()">
          </div>
          <div class="form-group">
            <label class="form-label">Allowances (ZMW)</label>
            <input type="number" name="allowances" id="allowances" class="form-control" value="0" min="0" step="0.01" oninput="calcNet()">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Deductions (ZMW)</label>
            <input type="number" name="deductions" id="deductions" class="form-control" value="0" min="0" step="0.01" oninput="calcNet()">
          </div>
          <div class="form-group">
            <label class="form-label">Tax / PAYE (ZMW)</label>
            <input type="number" name="tax_amount" id="taxAmount" class="form-control" value="0" min="0" step="0.01" oninput="calcNet()">
          </div>
        </div>
        <!-- Net pay preview -->
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center;margin-bottom:16px;">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">NET PAY (PREVIEW)</div>
          <div id="netPreview" style="font-size:24px;font-weight:800;color:var(--gold);">ZMW 0.00</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
              <option value="">— Select —</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="cash">Cash</option>
              <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Any notes…">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addPayrollModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save',14) ?> Save Entry</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Edit Payroll Modal -->
<?php if ($canPrepare): ?>
<div class="modal-overlay" id="editPayrollModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('edit') ?> Edit Payroll — <span id="editPayrollName"></span></div>
      <button class="modal-close" onclick="closeModal('editPayrollModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save_payroll">
      <input type="hidden" name="pay_period" value="<?= $period ?>">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Basic Salary (ZMW) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="basic_salary" id="editBasic" class="form-control" min="0" step="0.01" required oninput="calcEditNet()">
          </div>
          <div class="form-group">
            <label class="form-label">Allowances (ZMW)</label>
            <input type="number" name="allowances" id="editAllow" class="form-control" min="0" step="0.01" oninput="calcEditNet()">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Deductions (ZMW)</label>
            <input type="number" name="deductions" id="editDeduct" class="form-control" min="0" step="0.01" oninput="calcEditNet()">
          </div>
          <div class="form-group">
            <label class="form-label">Tax / PAYE (ZMW)</label>
            <input type="number" name="tax_amount" id="editTax" class="form-control" min="0" step="0.01" oninput="calcEditNet()">
          </div>
        </div>
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center;margin-bottom:16px;">
          <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">NET PAY (PREVIEW)</div>
          <div id="editNetPreview" style="font-size:24px;font-weight:800;color:var(--gold);">ZMW 0.00</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="editMethod" class="form-control">
              <option value="">— Select —</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="cash">Cash</option>
              <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" id="editNotes" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editPayrollModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save',14) ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Mark Paid Modal -->
<?php if ($canApprove && $approvedCnt > 0): ?>
<div class="modal-overlay" id="markPaidModal">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('dollar-sign') ?> Process Payment — <?= $periodLabel ?></div>
      <button class="modal-close" onclick="closeModal('markPaidModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="mark_paid">
      <input type="hidden" name="pay_period" value="<?= $period ?>">
      <div class="modal-body">
        <div style="background:rgba(45,212,160,0.06);border:1px solid rgba(45,212,160,0.2);border-radius:8px;padding:16px;margin-bottom:16px;text-align:center;">
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">TOTAL NET PAY TO PROCESS</div>
          <div style="font-size:28px;font-weight:800;color:var(--success);"><?= formatMoney((float)$db->query("SELECT COALESCE(SUM(net_salary),0) FROM payroll WHERE pay_period='$period' AND status='approved'")->fetchColumn()) ?></div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= $approvedCnt ?> approved records</div>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Reference</label>
          <input type="text" name="payment_ref" class="form-control" placeholder="e.g. BANK-BATCH-001 or auto-generated">
          <div class="form-helper">Leave blank to auto-generate a reference number</div>
        </div>
        <div class="alert alert-warning" style="font-size:12px;padding:10px 14px;">
          ⚠️ This action will mark all <?= $approvedCnt ?> approved records as paid and notify each employee. This cannot be undone.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('markPaidModal')">Cancel</button>
        <button type="submit" class="btn btn-sm" style="background:var(--success);color:#fff;border:none;"><?= icon('check-square',14) ?> Confirm Payment</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function calcNet() {
  const b = parseFloat(document.getElementById('basicSalary').value)||0;
  const a = parseFloat(document.getElementById('allowances').value)||0;
  const d = parseFloat(document.getElementById('deductions').value)||0;
  const t = parseFloat(document.getElementById('taxAmount').value)||0;
  const net = b + a - d - t;
  document.getElementById('netPreview').textContent = 'ZMW ' + net.toLocaleString('en-ZM',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('netPreview').style.color = net >= 0 ? 'var(--gold)' : 'var(--danger)';
}
function calcEditNet() {
  const b = parseFloat(document.getElementById('editBasic').value)||0;
  const a = parseFloat(document.getElementById('editAllow').value)||0;
  const d = parseFloat(document.getElementById('editDeduct').value)||0;
  const t = parseFloat(document.getElementById('editTax').value)||0;
  const net = b + a - d - t;
  document.getElementById('editNetPreview').textContent = 'ZMW ' + net.toLocaleString('en-ZM',{minimumFractionDigits:2,maximumFractionDigits:2});
}
function openEditPayroll(data) {
  document.getElementById('editPayrollName').textContent = data.name;
  document.getElementById('editUserId').value  = data.user_id;
  document.getElementById('editBasic').value   = data.basic;
  document.getElementById('editAllow').value   = data.allow;
  document.getElementById('editDeduct').value  = data.deduct;
  document.getElementById('editTax').value     = data.tax;
  document.getElementById('editMethod').value  = data.method || '';
  document.getElementById('editNotes').value   = data.notes || '';
  calcEditNet();
  openModal('editPayrollModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
