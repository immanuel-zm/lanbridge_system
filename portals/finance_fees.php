<?php
// ── Bootstrap ─────────────────────────────────────────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug']));
    exit;
}

$isAuditor     = ($user['role_slug'] === 'auditor');
$canApprove    = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$uid           = (int)$user['id'];
$db            = getDB();

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_fee') {
        $sName  = trim($_POST['student_name']  ?? '');
        $sId    = trim($_POST['student_id']    ?? '');
        $prog   = trim($_POST['programme']     ?? '');
        $year   = trim($_POST['academic_year'] ?? date('Y'));
        $sem    = (int)($_POST['semester']     ?? 1);
        $type   = trim($_POST['fee_type']      ?? '');
        $due    = abs((float)($_POST['amount_due'] ?? 0));
        $dDate  = $_POST['due_date'] ?: null;

        if ($sName && $sId && $type && $due > 0) {
            $db->prepare(
                "INSERT INTO student_fees
                 (student_name,student_id,programme,academic_year,semester,fee_type,amount_due,due_date,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([$sName,$sId,$prog,$year,$sem,$type,$due,$dDate,$uid]);
            $feeId = (int)$db->lastInsertId();
            logFinanceAudit($uid,'FEE_CREATED','student_fees',$feeId,null,[
                'student_id'=>$sId,'amount_due'=>$due,'fee_type'=>$type
            ]);
            logActivity($uid,'FINANCE_FEE_ADD','Added fee record for '.$sName.' ('.$sId.')');
            setFlash('success','✅ Fee record added for '.$sName.'.');
        } else {
            setFlash('danger','❌ Student name, ID, fee type and amount are required.');
        }
        header('Location: finance_fees.php'); exit;
    }

    if ($action === 'record_payment') {
        $feeId   = (int)($_POST['fee_id']         ?? 0);
        $payment = abs((float)($_POST['amount']   ?? 0));
        $method  = trim($_POST['method']          ?? 'Cash');
        $receipt = trim($_POST['receipt']         ?? '');

        if ($feeId && $payment > 0) {
            $fee = $db->query("SELECT * FROM student_fees WHERE id = $feeId")->fetch();
            if ($fee) {
                $oldPaid   = (float)$fee['amount_paid'];
                $newPaid   = $oldPaid + $payment;
                $newStatus = $newPaid >= (float)$fee['amount_due'] ? 'paid' : 'partial';
                if ((float)$fee['amount_due'] == 0) $newStatus = 'paid';

                $db->prepare(
                    "UPDATE student_fees SET amount_paid=?,status=?,updated_at=NOW() WHERE id=?"
                )->execute([$newPaid,$newStatus,$feeId]);

                // Also record as a transaction
                $ref = generateRef('PAY');
                $db->prepare(
                    "INSERT INTO transactions
                     (reference_no,type,category,description,amount,payment_method,receipt_no,
                      student_fee_id,recorded_by,transaction_date,status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([
                    $ref,'income','Tuition Fee',
                    'Payment: '.$fee['student_name'].' ('.$fee['student_id'].') - '.$fee['fee_type'],
                    $payment,$method,$receipt,$feeId,$uid,date('Y-m-d'),'approved'
                ]);

                logFinanceAudit($uid,'PAYMENT_RECORDED','student_fees',$feeId,
                    ['amount_paid'=>$oldPaid,'status'=>$fee['status']],
                    ['amount_paid'=>$newPaid,'status'=>$newStatus]
                );
                logActivity($uid,'FINANCE_PAYMENT','Payment ZMW '.number_format($payment,2).' for '.$fee['student_name']);
                setFlash('success','✅ Payment of '.formatMoney($payment).' recorded. Status: '.ucfirst($newStatus).'.');
            }
        } else {
            setFlash('danger','❌ Invalid payment data.');
        }
        header('Location: finance_fees.php'); exit;
    }

    if ($action === 'waive_fee') {
        $feeId = (int)($_POST['fee_id'] ?? 0);
        $note  = trim($_POST['waive_note'] ?? '');
        if ($feeId && $canApprove) {
            $fee = $db->query("SELECT * FROM student_fees WHERE id=$feeId")->fetch();
            $db->prepare("UPDATE student_fees SET status='waived',notes=?,updated_at=NOW() WHERE id=?")
               ->execute([$note,$feeId]);
            logFinanceAudit($uid,'FEE_WAIVED','student_fees',$feeId,
                ['status'=>$fee['status']],['status'=>'waived','note'=>$note]);
            setFlash('success','✅ Fee waived.');
        }
        header('Location: finance_fees.php'); exit;
    }
}

// ── Filters ───────────────────────────────────────────────────
$search     = trim($_GET['q']       ?? '');
$statusF    = $_GET['status']       ?? '';
$yearF      = $_GET['year']         ?? '';
$semF       = $_GET['semester']     ?? '';

$where = ['1=1'];
$params = [];
if ($search) {
    $where[]  = '(student_name LIKE ? OR student_id LIKE ? OR programme LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusF) { $where[] = 'status = ?'; $params[] = $statusF; }
if ($yearF)   { $where[] = 'academic_year = ?'; $params[] = $yearF; }
if ($semF)    { $where[] = 'semester = ?'; $params[] = (int)$semF; }

$whereStr = implode(' AND ', $where);

$stmt = $db->prepare(
    "SELECT * FROM student_fees WHERE $whereStr ORDER BY
     FIELD(status,'unpaid','partial','paid','waived','scholarship'), created_at DESC LIMIT 100"
);
$stmt->execute($params);
$fees = $stmt->fetchAll();

// Summary stats
$totalOwed    = (float)$db->query("SELECT COALESCE(SUM(amount_due),0) FROM student_fees")->fetchColumn();
$totalPaid    = (float)$db->query("SELECT COALESCE(SUM(amount_paid),0) FROM student_fees")->fetchColumn();
$totalOutstanding = $totalOwed - $totalPaid;
$countUnpaid  = (int)$db->query("SELECT COUNT(*) FROM student_fees WHERE status='unpaid'")->fetchColumn();
$countPartial = (int)$db->query("SELECT COUNT(*) FROM student_fees WHERE status='partial'")->fetchColumn();
$countPaid    = (int)$db->query("SELECT COUNT(*) FROM student_fees WHERE status='paid'")->fetchColumn();

// Unique years for filter
$years = $db->query("SELECT DISTINCT academic_year FROM student_fees ORDER BY academic_year DESC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle    = 'Student Fee Management';
$pageSubtitle = 'Ledger, Payments & Outstanding Balances';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── STAT CARDS ─────────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:18px;"><?= formatMoney($totalOwed) ?></div><div class="stat-label">Total Fees Invoiced</div></div>
      <div class="stat-icon"><?= icon('layers',20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('users',12) ?> All academic years</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:18px;"><?= formatMoney($totalPaid) ?></div><div class="stat-label">Total Collected</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta up"><?= $countPaid ?> fully paid accounts</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:18px;"><?= formatMoney($totalOutstanding) ?></div><div class="stat-label">Outstanding Balance</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta down"><?= $countUnpaid ?> unpaid · <?= $countPartial ?> partial</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalOwed > 0 ? round(($totalPaid/$totalOwed)*100) : 0 ?>%</div><div class="stat-label">Collection Rate</div></div>
      <div class="stat-icon"><?= icon('bar-chart',20) ?></div>
    </div>
    <div class="stat-delta"><?= icon('check-square',12) ?> Fees collected vs invoiced</div>
  </div>
</div>

<!-- ── ACTIONS + FILTERS ──────────────────────────────────────── -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px;">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <?php if (!$isAuditor): ?>
      <button class="btn btn-primary" onclick="openModal('addFeeModal')"><?= icon('send',14) ?> Add Fee Record</button>
      <?php endif; ?>

      <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;flex:1;">
        <input type="text" name="q" class="form-control" placeholder="Search student name or ID..."
          value="<?= sanitize($search) ?>" style="min-width:200px;flex:1;">
        <select name="status" class="form-control" style="min-width:130px;">
          <option value="">All Statuses</option>
          <?php foreach (['unpaid','partial','paid','waived','scholarship'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="year" class="form-control" style="min-width:100px;">
          <option value="">All Years</option>
          <?php foreach ($years as $y): ?>
          <option value="<?= $y ?>" <?= $yearF===$y?'selected':'' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <select name="semester" class="form-control" style="min-width:130px;">
          <option value="">All Semesters</option>
          <option value="1" <?= $semF==='1'?'selected':'' ?>>Semester 1</option>
          <option value="2" <?= $semF==='2'?'selected':'' ?>>Semester 2</option>
        </select>
        <button type="submit" class="btn btn-outline"><?= icon('bar-chart',14) ?> Filter</button>
        <?php if ($search||$statusF||$yearF||$semF): ?>
        <a href="finance_fees.php" class="btn btn-outline"><?= icon('x',14) ?> Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<!-- ── FEES TABLE ─────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('users') ?> Student Fee Ledger
      <span class="badge badge-muted" style="margin-left:8px;"><?= count($fees) ?> records</span>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Programme / Year</th>
          <th>Fee Type</th>
          <th>Amount Due</th>
          <th>Paid</th>
          <th>Balance</th>
          <th>Status</th>
          <th>Due Date</th>
          <?php if (!$isAuditor): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($fees)): ?>
        <tr><td colspan="9">
          <div class="empty-state" style="padding:40px;">
            <?= icon('inbox',36) ?><p>No fee records found<?= ($search||$statusF) ? ' matching your filters' : '' ?></p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($fees as $f):
          $balance  = (float)$f['amount_due'] - (float)$f['amount_paid'];
          $isOverdue= $f['due_date'] && $f['due_date'] < date('Y-m-d') && $f['status'] !== 'paid';
          $statusMap= ['unpaid'=>'badge-danger','partial'=>'badge-warning','paid'=>'badge-success','waived'=>'badge-muted','scholarship'=>'badge-info'];
          $sBadge   = $statusMap[$f['status']] ?? 'badge-muted';
        ?>
        <tr style="<?= $isOverdue ? 'background:rgba(232,85,106,0.04);' : '' ?>">
          <td>
            <div class="td-bold"><?= sanitize($f['student_name']) ?></div>
            <div class="td-muted" style="font-family:monospace;font-size:11px;"><?= sanitize($f['student_id']) ?></div>
          </td>
          <td>
            <div style="font-size:13px;"><?= sanitize($f['programme'] ?: '—') ?></div>
            <div class="td-muted"><?= sanitize($f['academic_year']) ?> · Sem <?= $f['semester'] ?></div>
          </td>
          <td><?= sanitize($f['fee_type']) ?></td>
          <td class="td-bold"><?= formatMoney($f['amount_due']) ?></td>
          <td style="color:var(--success);"><?= formatMoney($f['amount_paid']) ?></td>
          <td class="td-bold" style="color:<?= $balance > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
            <?= formatMoney(max(0,$balance)) ?>
          </td>
          <td>
            <span class="badge <?= $sBadge ?>"><?= ucfirst($f['status']) ?></span>
            <?php if ($isOverdue): ?><br><span style="font-size:10px;color:var(--danger);">OVERDUE</span><?php endif; ?>
          </td>
          <td class="td-muted"><?= $f['due_date'] ? formatDate($f['due_date'],'M d, Y') : '—' ?></td>
          <?php if (!$isAuditor): ?>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              <?php if (in_array($f['status'],['unpaid','partial'])): ?>
              <button class="btn btn-success btn-sm"
                onclick="openPaymentModal(<?= $f['id'] ?>,'<?= sanitize($f['student_name']) ?>',<?= $balance ?>)">
                Pay
              </button>
              <?php endif; ?>
              <?php if ($canApprove && $f['status'] !== 'waived'): ?>
              <button class="btn btn-outline btn-sm"
                onclick="openWaiveModal(<?= $f['id'] ?>,'<?= sanitize($f['student_name']) ?>')">
                Waive
              </button>
              <?php endif; ?>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══════════ MODALS ═══════════ -->

<!-- Add Fee Modal -->
<div class="modal-overlay" id="addFeeModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Add Student Fee Record</div>
      <button class="modal-close" onclick="closeModal('addFeeModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_fee">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Student Full Name <span style="color:var(--danger)">*</span></label>
            <input type="text" name="student_name" class="form-control" placeholder="Full name" required>
          </div>
          <div class="form-group">
            <label class="form-label">Student ID <span style="color:var(--danger)">*</span></label>
            <input type="text" name="student_id" class="form-control" placeholder="e.g. LBC-2025-001" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Programme / Course</label>
          <input type="text" name="programme" class="form-control" placeholder="e.g. BSc Computer Science">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Academic Year <span style="color:var(--danger)">*</span></label>
            <input type="text" name="academic_year" class="form-control" value="<?= date('Y') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-control">
              <option value="1">Semester 1</option>
              <option value="2">Semester 2</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fee Type <span style="color:var(--danger)">*</span></label>
            <select name="fee_type" class="form-control" required>
              <option value="">— Select —</option>
              <option>Tuition Fee</option>
              <option>Library Fee</option>
              <option>Examination Fee</option>
              <option>Registration Fee</option>
              <option>Accommodation</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount Due (ZMW) <span style="color:var(--danger)">*</span></label>
            <input type="number" name="amount_due" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date</label>
          <input type="date" name="due_date" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addFeeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Save Record</button>
      </div>
    </form>
  </div>
</div>

<!-- Record Payment Modal -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title" style="color:var(--success);"><?= icon('check-square',16) ?> Record Payment</div>
      <button class="modal-close" onclick="closeModal('paymentModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="record_payment">
      <input type="hidden" name="fee_id" id="payFeeId">
      <div class="modal-body">
        <div style="background:var(--bg-elevated);border-radius:var(--radius);padding:12px;margin-bottom:16px;">
          <div class="td-muted" style="font-size:12px;">Student</div>
          <div class="td-bold" id="payStudentName">—</div>
          <div class="td-muted" style="margin-top:6px;font-size:12px;">Outstanding Balance</div>
          <div id="payBalance" style="font-size:18px;font-weight:700;color:var(--danger);">—</div>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Amount (ZMW) <span style="color:var(--danger)">*</span></label>
          <input type="number" name="amount" id="payAmount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="method" class="form-control">
              <option>Cash</option>
              <option>Mobile Money</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>Card</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Receipt No.</label>
            <input type="text" name="receipt" class="form-control" placeholder="Optional">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('paymentModal')">Cancel</button>
        <button type="submit" class="btn btn-success"><?= icon('check-square',14) ?> Confirm Payment</button>
      </div>
    </form>
  </div>
</div>

<!-- Waive Fee Modal -->
<div class="modal-overlay" id="waiveModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('shield',16) ?> Waive Fee</div>
      <button class="modal-close" onclick="closeModal('waiveModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="waive_fee">
      <input type="hidden" name="fee_id" id="waiveFeeId">
      <div class="modal-body">
        <p style="color:var(--text-muted);margin-bottom:14px;">
          Waiving fee for: <strong id="waiveStudentName" style="color:var(--text-primary);">—</strong>
        </p>
        <div class="form-group">
          <label class="form-label">Reason for Waiver <span style="color:var(--danger)">*</span></label>
          <textarea name="waive_note" class="form-control" rows="3" placeholder="Scholarship, financial hardship, error correction..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('waiveModal')">Cancel</button>
        <button type="submit" class="btn btn-warning">Confirm Waiver</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPaymentModal(feeId, studentName, balance) {
  document.getElementById('payFeeId').value       = feeId;
  document.getElementById('payStudentName').textContent = studentName;
  document.getElementById('payBalance').textContent = 'ZMW ' + parseFloat(balance).toLocaleString('en',{minimumFractionDigits:2});
  document.getElementById('payAmount').max = balance;
  openModal('paymentModal');
}
function openWaiveModal(feeId, studentName) {
  document.getElementById('waiveFeeId').value          = feeId;
  document.getElementById('waiveStudentName').textContent = studentName;
  openModal('waiveModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
