<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'])); exit;
}

$isAuditor     = ($user['role_slug'] === 'auditor');
$isBursar      = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$canApproveTxn = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $db  = getDB();
    $uid = (int)$user['id'];
    $action = $_POST['action'] ?? '';

    // ── Record a transaction ──────────────────────────────────
    if ($action === 'add_transaction') {
        $ref    = generateRef('TXN');
        $type   = in_array($_POST['type'], ['income','expense','transfer','refund']) ? $_POST['type'] : 'income';
        $cat    = trim($_POST['category'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $amount = abs((float)($_POST['amount'] ?? 0));
        $method = trim($_POST['payment_method'] ?? '');
        $receipt= trim($_POST['receipt_no'] ?? '');
        $date   = $_POST['transaction_date'] ?? date('Y-m-d');

        if ($cat && $desc && $amount > 0 && $date) {
            $stmt = $db->prepare(
                "INSERT INTO transactions
                 (reference_no,type,category,description,amount,payment_method,receipt_no,recorded_by,transaction_date,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([$ref,$type,$cat,$desc,$amount,$method,$receipt,$uid,$date,
                $canApproveTxn ? 'approved' : 'pending']);
            logFinanceAudit($uid,'TRANSACTION_RECORDED','transactions',
                (int)$db->lastInsertId(), null,
                ['type'=>$type,'amount'=>$amount,'category'=>$cat]);
            logActivity($uid,'FINANCE_TXN','Recorded '.strtoupper($type).' ZMW '.$amount.' — '.$cat);
            setFlash('success','✅ Transaction recorded successfully. Ref: '.$ref);
        } else {
            setFlash('danger','❌ Please fill in all required fields with valid values.');
        }
        header('Location: finance_dashboard.php'); exit;
    }

    // ── Approve / reject a transaction ────────────────────────
    if ($action === 'approve_transaction' && $canApproveTxn) {
        $txnId  = (int)($_POST['txn_id'] ?? 0);
        $status = $_POST['status'] ?? 'approved';
        if (!in_array($status, ['approved','rejected'])) $status = 'approved';
        $reversal = trim($_POST['reversal_reason'] ?? '');
        $old = $db->prepare("SELECT * FROM transactions WHERE id=?")->execute([$txnId])
            ? $db->query("SELECT * FROM transactions WHERE id=$txnId")->fetch() : null;
        $db->prepare("UPDATE transactions SET status=?,approved_by=?,reversal_reason=? WHERE id=?")
           ->execute([$status,$uid,$reversal,$txnId]);
        logFinanceAudit($uid,'TRANSACTION_'.strtoupper($status),'transactions',$txnId,
            $old ? ['status'=>$old['status']] : null, ['status'=>$status]);
        setFlash('success','✅ Transaction '.ucfirst($status).'.');
        header('Location: finance_dashboard.php'); exit;
    }

    // ── Add student fee record ────────────────────────────────
    if ($action === 'add_fee') {
        $studentName = trim($_POST['student_name'] ?? '');
        $studentId   = trim($_POST['student_id'] ?? '');
        $programme   = trim($_POST['programme'] ?? '');
        $year        = trim($_POST['academic_year'] ?? date('Y'));
        $semester    = (int)($_POST['semester'] ?? 1);
        $feeType     = trim($_POST['fee_type'] ?? '');
        $amountDue   = abs((float)($_POST['amount_due'] ?? 0));
        $dueDate     = $_POST['due_date'] ?? null;

        if ($studentName && $studentId && $feeType && $amountDue > 0) {
            $db->prepare(
                "INSERT INTO student_fees (student_name,student_id,programme,academic_year,semester,fee_type,amount_due,due_date,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([$studentName,$studentId,$programme,$year,$semester,$feeType,$amountDue,$dueDate,$uid]);
            logFinanceAudit($uid,'FEE_CREATED','student_fees',(int)$db->lastInsertId(),null,
                ['student_id'=>$studentId,'amount_due'=>$amountDue]);
            setFlash('success','✅ Fee record added for '.$studentName);
        } else {
            setFlash('danger','❌ Fill all required fee fields.');
        }
        header('Location: finance_dashboard.php'); exit;
    }

    // ── Record fee payment ────────────────────────────────────
    if ($action === 'record_payment') {
        $feeId   = (int)($_POST['fee_id'] ?? 0);
        $payment = abs((float)($_POST['payment_amount'] ?? 0));
        $method  = trim($_POST['pay_method'] ?? '');
        $receipt = trim($_POST['pay_receipt'] ?? '');
        if ($feeId && $payment > 0) {
            // Get current fee
            $fee = $db->query("SELECT * FROM student_fees WHERE id=$feeId")->fetch();
            if ($fee) {
                $newPaid   = $fee['amount_paid'] + $payment;
                $newStatus = $newPaid >= $fee['amount_due'] ? 'paid' : 'partial';
                $db->prepare("UPDATE student_fees SET amount_paid=?,status=?,updated_at=NOW() WHERE id=?")
                   ->execute([$newPaid,$newStatus,$feeId]);
                // Log as transaction too
                $ref = generateRef('PAY');
                $db->prepare(
                    "INSERT INTO transactions (reference_no,type,category,description,amount,payment_method,receipt_no,student_fee_id,recorded_by,transaction_date,status)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([$ref,'income','Tuition Fee',
                    'Fee payment: '.$fee['student_name'].' ('.$fee['student_id'].')',
                    $payment,$method,$receipt,$feeId,$uid,date('Y-m-d'),'approved']);
                logFinanceAudit($uid,'PAYMENT_RECORDED','student_fees',$feeId,
                    ['amount_paid'=>$fee['amount_paid']],['amount_paid'=>$newPaid,'status'=>$newStatus]);
                setFlash('success','✅ Payment of ZMW '.number_format($payment,2).' recorded.');
            }
        }
        header('Location: finance_dashboard.php'); exit;
    }
}

$pageTitle    = 'Finance Dashboard';
$pageSubtitle = 'Financial Operations — ' . date('F Y');
require_once __DIR__ . '/../includes/header.php';

$db         = getDB();
$today      = date('Y-m-d');
$monthStart = date('Y-m-01');
$fiscalYear = getSetting('finance_fiscal_year', date('Y'));

// ── Summary metrics ───────────────────────────────────────────
$totalIncome  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND status='approved' AND transaction_date>='$monthStart'")->fetchColumn();
$totalExpense = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='expense' AND status='approved' AND transaction_date>='$monthStart'")->fetchColumn();
$pendingTxns  = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
$outstandingFees = (float)$db->query("SELECT COALESCE(SUM(balance),0) FROM student_fees WHERE status IN ('unpaid','partial')")->fetchColumn();
$paidFeesMonth   = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='income' AND category='Tuition Fee' AND status='approved' AND transaction_date>='$monthStart'")->fetchColumn();
$totalStudentFees= (float)$db->query("SELECT COALESCE(SUM(amount_due),0) FROM student_fees")->fetchColumn();
$collectionRate  = $totalStudentFees > 0 ? round((($totalStudentFees - $outstandingFees) / $totalStudentFees) * 100) : 0;

// Budget vs expenditure
$totalBudget = (float)$db->query("SELECT COALESCE(SUM(allocated_amount),0) FROM departmental_budget WHERE fiscal_year='$fiscalYear'")->fetchColumn();
$totalSpent  = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM expenditure WHERE fiscal_year='$fiscalYear' AND status='approved'")->fetchColumn();
$budgetUsed  = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100) : 0;

// Recent transactions
$recentTxns = $db->query(
    "SELECT t.*, u.first_name, u.last_name
     FROM transactions t JOIN users u ON t.recorded_by=u.id
     ORDER BY t.created_at DESC LIMIT 8"
)->fetchAll();

// Outstanding fees (top unpaid)
$outstandingList = $db->query(
    "SELECT * FROM student_fees WHERE status IN ('unpaid','partial')
     ORDER BY balance DESC LIMIT 8"
)->fetchAll();

// Pending transaction approvals
$pendingList = [];
if ($canApproveTxn) {
    $pendingList = $db->query(
        "SELECT t.*, u.first_name, u.last_name FROM transactions t
         JOIN users u ON t.recorded_by=u.id WHERE t.status='pending'
         ORDER BY t.created_at ASC LIMIT 10"
    )->fetchAll();
}

// Department budget overview
$deptBudgets = $db->query(
    "SELECT d.name, d.code, b.allocated_amount,
            COALESCE(SUM(e.amount),0) AS spent
     FROM departments d
     LEFT JOIN departmental_budget b ON b.department_id=d.id AND b.fiscal_year='$fiscalYear'
     LEFT JOIN expenditure e ON e.department_id=d.id AND e.fiscal_year='$fiscalYear' AND e.status='approved'
     GROUP BY d.id ORDER BY b.allocated_amount DESC"
)->fetchAll();

// All departments for fee form
$allDepts = $db->query("SELECT id,name,code FROM departments ORDER BY name")->fetchAll();
?>

<!-- ── STAT CARDS ────────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= formatMoney($totalIncome) ?></div><div class="stat-label">Income This Month</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta up"><?= icon('bar-chart',12) ?> Approved transactions only</div>
  </div>
  <div class="stat-card red" style="--card-accent:#e8556a;">
    <div class="stat-top">
      <div><div class="stat-number"><?= formatMoney($totalExpense) ?></div><div class="stat-label">Expenses This Month</div></div>
      <div class="stat-icon"><?= icon('download',20) ?></div>
    </div>
    <div class="stat-delta down"><?= icon('briefcase',12) ?> Approved expenses</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= formatMoney($outstandingFees) ?></div><div class="stat-label">Outstanding Fees</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta <?= $collectionRate >= 70 ? 'up' : 'down' ?>"><?= icon('users',12) ?> <?= $collectionRate ?>% fee collection rate</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $budgetUsed ?>%</div><div class="stat-label">Budget Utilised (<?= $fiscalYear ?>)</div></div>
      <div class="stat-icon"><?= icon('layers',20) ?></div>
    </div>
    <div class="stat-delta <?= $budgetUsed > 90 ? 'down' : 'up' ?>"><?= icon('bar-chart',12) ?> <?= formatMoney($totalSpent) ?> of <?= formatMoney($totalBudget) ?></div>
  </div>
</div>

<?php if (!$isAuditor): ?>
<!-- ── QUICK ACTIONS ─────────────────────────────────────────── -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
  <button class="btn btn-primary" onclick="openModal('txnModal')"><?= icon('send',14) ?> Record Transaction</button>
  <button class="btn btn-outline" onclick="openModal('feeModal')"><?= icon('users',14) ?> Add Student Fee</button>
  <a href="finance_fees.php" class="btn btn-outline"><?= icon('file-text',14) ?> Fee Management</a>
  <a href="finance_payroll.php" class="btn btn-outline"><?= icon('briefcase',14) ?> Payroll</a>
  <a href="finance_budget.php" class="btn btn-outline"><?= icon('bar-chart',14) ?> Budget & Expenditure</a>
  <a href="finance_procurement.php" class="btn btn-outline"><?= icon('layers',14) ?> Procurement</a>
</div>
<?php endif; ?>

<!-- ── PENDING APPROVALS ─────────────────────────────────────── -->
<?php if ($canApproveTxn && !empty($pendingList)): ?>
<div class="card mb-24" style="border:1px solid rgba(245,166,35,0.35);">
  <div class="card-header" style="background:rgba(245,166,35,0.06);">
    <div class="card-title" style="color:var(--warning);"><?= icon('clock',16) ?> Pending Transaction Approvals
      <span class="badge badge-warning" style="margin-left:8px;"><?= count($pendingList) ?></span>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ref</th><th>Recorded By</th><th>Type</th><th>Category</th><th>Amount</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($pendingList as $txn): ?>
        <tr>
          <td class="td-bold" style="font-family:monospace;font-size:12px;"><?= sanitize($txn['reference_no']) ?></td>
          <td><?= sanitize($txn['first_name'].' '.$txn['last_name']) ?></td>
          <td><?= priorityBadge($txn['type'] === 'income' ? 'medium' : 'high') ?>
              <span class="badge badge-muted" style="margin-left:4px;"><?= ucfirst($txn['type']) ?></span>
          </td>
          <td><?= sanitize($txn['category']) ?></td>
          <td class="td-bold" style="color:<?= $txn['type']==='income' ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= formatMoney($txn['amount']) ?>
          </td>
          <td><?= formatDate($txn['transaction_date']) ?></td>
          <td style="display:flex;gap:6px;">
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="approve_transaction">
              <input type="hidden" name="txn_id" value="<?= $txn['id'] ?>">
              <input type="hidden" name="status" value="approved">
              <button type="submit" class="btn btn-success btn-sm" data-confirm="Approve this transaction?">Approve</button>
            </form>
            <button class="btn btn-danger btn-sm" onclick="openRejectModal(<?= $txn['id'] ?>)">Reject</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── MAIN GRID ─────────────────────────────────────────────── -->
<div class="grid-2 mb-24">

  <!-- Recent Transactions -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('clock') ?> Recent Transactions</div>
      <a href="finance_transactions.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Ref</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recentTxns)): ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:20px;"><?= icon('inbox',24) ?><p>No transactions yet</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($recentTxns as $t): ?>
          <tr>
            <td>
              <div style="font-size:11px;font-family:monospace;color:var(--text-primary);"><?= sanitize($t['reference_no']) ?></div>
              <div class="td-muted"><?= sanitize($t['category']) ?></div>
            </td>
            <td><span class="badge <?= $t['type']==='income' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($t['type']) ?></span></td>
            <td class="td-bold" style="color:<?= $t['type']==='income' ? 'var(--success)' : 'var(--danger)' ?>;">
              <?= formatMoney($t['amount']) ?>
            </td>
            <td><?= statusBadge($t['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Outstanding Student Fees -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('users') ?> Outstanding Student Fees</div>
      <a href="finance_fees.php" class="btn btn-outline btn-sm">Manage All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Student</th><th>Programme</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($outstandingList)): ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:20px;"><?= icon('check-square',24) ?><p>No outstanding balances!</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($outstandingList as $f): ?>
          <tr>
            <td>
              <div class="td-bold"><?= sanitize($f['student_name']) ?></div>
              <div class="td-muted"><?= sanitize($f['student_id']) ?></div>
            </td>
            <td class="td-muted" style="font-size:12px;"><?= sanitize(substr($f['programme'],0,20)) ?></td>
            <td class="td-bold" style="color:var(--danger);"><?= formatMoney($f['balance']) ?></td>
            <td><?= statusBadge($f['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── BUDGET OVERVIEW ───────────────────────────────────────── -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('bar-chart') ?> Department Budget vs Expenditure — <?= $fiscalYear ?></div>
    <?php if ($isBursar): ?><a href="finance_budget.php" class="btn btn-outline btn-sm">Manage Budgets</a><?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Department</th><th>Allocated Budget</th><th>Spent</th><th>Remaining</th><th>Utilisation</th></tr></thead>
      <tbody>
        <?php foreach ($deptBudgets as $b):
          $allocated = (float)($b['allocated_amount'] ?? 0);
          $spent     = (float)$b['spent'];
          $remaining = $allocated - $spent;
          $pct       = $allocated > 0 ? min(100, round(($spent / $allocated) * 100)) : 0;
          $color     = $pct >= 90 ? 'red' : ($pct >= 70 ? 'gold' : 'green');
        ?>
        <tr>
          <td><div class="td-bold"><?= sanitize($b['name']) ?></div><div class="td-muted"><?= sanitize($b['code']) ?></div></td>
          <td><?= $allocated > 0 ? formatMoney($allocated) : '<span class="td-muted">Not set</span>' ?></td>
          <td><?= formatMoney($spent) ?></td>
          <td style="color:<?= $remaining < 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= formatMoney($remaining) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
              <div class="progress" style="flex:1;">
                <div class="progress-bar <?= $color ?>" style="width:<?= $pct ?>%;"></div>
              </div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);flex-shrink:0;"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════ -->
<!-- MODALS                                                   -->
<!-- ════════════════════════════════════════════════════════ -->

<!-- Record Transaction Modal -->
<div class="modal-overlay" id="txnModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Record Transaction</div>
      <button class="modal-close" onclick="closeModal('txnModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_transaction">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type <span style="color:var(--danger);">*</span></label>
            <select name="type" class="form-control" required>
              <option value="income">Income</option>
              <option value="expense">Expense</option>
              <option value="transfer">Transfer</option>
              <option value="refund">Refund</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (ZMW) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category <span style="color:var(--danger);">*</span></label>
          <input type="text" name="category" class="form-control" placeholder="e.g. Tuition Fee, Utilities, Salaries" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--danger);">*</span></label>
          <textarea name="description" class="form-control" rows="2" placeholder="Describe this transaction..." required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
              <option value="">— Select —</option>
              <option>Cash</option>
              <option>Mobile Money</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>Card</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Receipt / Ref No.</label>
            <input type="text" name="receipt_no" class="form-control" placeholder="Optional">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Transaction Date <span style="color:var(--danger);">*</span></label>
          <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <?php if (!$canApproveTxn): ?>
        <div class="alert alert-info"><?= icon('clock',14) ?> Your transaction will require approval from the Bursar before it is posted.</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('txnModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Record Transaction</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Student Fee Modal -->
<div class="modal-overlay" id="feeModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('users',16) ?> Add Student Fee Record</div>
      <button class="modal-close" onclick="closeModal('feeModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_fee">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Student Full Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="student_name" class="form-control" placeholder="Full name" required>
          </div>
          <div class="form-group">
            <label class="form-label">Student ID <span style="color:var(--danger);">*</span></label>
            <input type="text" name="student_id" class="form-control" placeholder="e.g. STU-2025-001" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Programme / Course</label>
          <input type="text" name="programme" class="form-control" placeholder="e.g. BSc Computer Science">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Academic Year <span style="color:var(--danger);">*</span></label>
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
            <label class="form-label">Fee Type <span style="color:var(--danger);">*</span></label>
            <select name="fee_type" class="form-control" required>
              <option value="">— Select —</option>
              <option>Tuition Fee</option>
              <option>Library Fee</option>
              <option>Examination Fee</option>
              <option>Registration Fee</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount Due (ZMW) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="amount_due" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date</label>
          <input type="date" name="due_date" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('feeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Save Fee Record</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Transaction Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <div class="modal-title" style="color:var(--danger);"><?= icon('x',16) ?> Reject Transaction</div>
      <button class="modal-close" onclick="closeModal('rejectModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="action" value="approve_transaction">
      <input type="hidden" name="status" value="rejected">
      <input type="hidden" name="txn_id" id="rejectTxnId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason for Rejection <span style="color:var(--danger);">*</span></label>
          <textarea name="reversal_reason" class="form-control" rows="3" placeholder="Provide a clear reason..." required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRejectModal(txnId) {
  document.getElementById('rejectTxnId').value = txnId;
  openModal('rejectModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
