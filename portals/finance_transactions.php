<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'])); exit;
}

$isAuditor  = ($user['role_slug'] === 'auditor');
$canApprove = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$uid        = (int)$user['id'];
$db         = getDB();

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_transaction') {
        $ref    = generateRef('TXN');
        $type   = in_array($_POST['type']??'',['income','expense','transfer','refund']) ? $_POST['type'] : 'income';
        $cat    = trim($_POST['category']     ?? '');
        $desc   = trim($_POST['description']  ?? '');
        $amount = abs((float)($_POST['amount'] ?? 0));
        $method = trim($_POST['payment_method'] ?? '');
        $rcpt   = trim($_POST['receipt_no']   ?? '');
        $date   = $_POST['transaction_date']  ?? date('Y-m-d');

        if ($cat && $desc && $amount > 0 && $date) {
            $autoApprove = $canApprove ? 'approved' : 'pending';
            $approvedBy  = $canApprove ? $uid : null;
            $db->prepare(
                "INSERT INTO transactions
                 (reference_no,type,category,description,amount,payment_method,receipt_no,
                  recorded_by,approved_by,transaction_date,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([$ref,$type,$cat,$desc,$amount,$method,$rcpt,$uid,$approvedBy,$date,$autoApprove]);
            $txnId = (int)$db->lastInsertId();
            logFinanceAudit($uid,'TXN_RECORDED','transactions',$txnId,null,
                ['type'=>$type,'amount'=>$amount,'category'=>$cat,'status'=>$autoApprove]);
            logActivity($uid,'FINANCE_TXN','Recorded '.strtoupper($type).' '.formatMoney($amount).' — '.$cat);
            setFlash('success','✅ Transaction recorded. Ref: '.$ref);
        } else {
            setFlash('danger','❌ All required fields must be filled in.');
        }
        header('Location: finance_transactions.php'); exit;
    }

    if ($action === 'approve_txn' && $canApprove) {
        $txnId  = (int)($_POST['txn_id'] ?? 0);
        $status = ($_POST['new_status'] ?? '') === 'rejected' ? 'rejected' : 'approved';
        $reason = trim($_POST['reason'] ?? '');
        $old    = $db->query("SELECT * FROM transactions WHERE id=$txnId")->fetch();
        if ($old) {
            $db->prepare(
                "UPDATE transactions SET status=?,approved_by=?,reversal_reason=? WHERE id=?"
            )->execute([$status,$uid,$reason,$txnId]);
            logFinanceAudit($uid,'TXN_'.strtoupper($status),'transactions',$txnId,
                ['status'=>$old['status']],['status'=>$status,'reason'=>$reason]);
            setFlash('success','✅ Transaction '.ucfirst($status).'.');
        }
        header('Location: finance_transactions.php'); exit;
    }
}

// ── Filters ───────────────────────────────────────────────────
$typeF   = $_GET['type']   ?? '';
$statusF = $_GET['status'] ?? '';
$catF    = $_GET['cat']    ?? '';
$from    = $_GET['from']   ?? date('Y-m-01');
$to      = $_GET['to']     ?? date('Y-m-d');
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = ['transaction_date BETWEEN ? AND ?'];
$params = [$from, $to];
if ($typeF)   { $where[] = 'type = ?';     $params[] = $typeF;   }
if ($statusF) { $where[] = 'status = ?';   $params[] = $statusF; }
if ($catF)    { $where[] = 'category LIKE ?'; $params[] = "%$catF%"; }

$whereStr = implode(' AND ', $where);

$totalRows = (int)$db->prepare("SELECT COUNT(*) FROM transactions WHERE $whereStr")->execute($params) ?
    $db->prepare("SELECT COUNT(*) FROM transactions WHERE $whereStr")->execute($params) && 0 : 0;
$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE $whereStr");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare(
    "SELECT t.*, u.first_name AS rec_first, u.last_name AS rec_last,
            a.first_name AS apr_first, a.last_name AS apr_last
     FROM transactions t
     JOIN  users u ON t.recorded_by = u.id
     LEFT JOIN users a ON t.approved_by = a.id
     WHERE $whereStr
     ORDER BY t.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Summary for filtered period
$sumStmt = $db->prepare("SELECT type, SUM(amount) AS total FROM transactions WHERE $whereStr AND status='approved' GROUP BY type");
$sumStmt->execute($params);
$sums = [];
foreach ($sumStmt->fetchAll() as $row) $sums[$row['type']] = (float)$row['total'];

$pageTitle    = 'Transaction Ledger';
$pageSubtitle = 'All Financial Transactions';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Summary Bar -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <?php
  $cards = [
    ['Income',   $sums['income']   ?? 0, 'var(--success)', 'trending-up'],
    ['Expense',  $sums['expense']  ?? 0, 'var(--danger)',  'download'],
    ['Transfer', $sums['transfer'] ?? 0, 'var(--info)',    'send'],
    ['Net',      (($sums['income']??0)-($sums['expense']??0)), 'var(--gold)', 'bar-chart'],
  ];
  foreach ($cards as [$label,$val,$color,$ic]):
  ?>
  <div class="card" style="border-top:3px solid <?= $color ?>;">
    <div class="card-body" style="padding:16px;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <span style="color:<?= $color ?>;"><?= icon($ic,14) ?></span>
        <span class="td-muted" style="font-size:12px;"><?= $label ?> (<?= date('M',strtotime($from)).' – '.date('M',strtotime($to)) ?>)</span>
      </div>
      <div style="font-size:17px;font-weight:700;color:<?= $color ?>;"><?= formatMoney($val) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters + Add -->
<div class="card mb-24">
  <div class="card-body" style="padding:16px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <?php if (!$isAuditor): ?>
      <button class="btn btn-primary" onclick="openModal('addTxnModal')"><?= icon('send',14) ?> Record Transaction</button>
      <?php endif; ?>
      <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;flex:1;">
        <input type="date" name="from" class="form-control" value="<?= $from ?>" style="width:140px;">
        <input type="date" name="to"   class="form-control" value="<?= $to ?>"   style="width:140px;">
        <select name="type" class="form-control" style="width:120px;">
          <option value="">All Types</option>
          <?php foreach (['income','expense','transfer','refund'] as $t): ?>
          <option value="<?= $t ?>" <?= $typeF===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" class="form-control" style="width:120px;">
          <option value="">All Status</option>
          <?php foreach (['pending','approved','rejected','reversed'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusF===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="cat" class="form-control" value="<?= sanitize($catF) ?>" placeholder="Category..." style="width:150px;">
        <button type="submit" class="btn btn-outline"><?= icon('bar-chart',14) ?> Filter</button>
        <a href="finance_transactions.php" class="btn btn-outline"><?= icon('x',14) ?> Reset</a>
      </form>
    </div>
  </div>
</div>

<!-- Transaction Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('clock') ?> Transactions
      <span class="badge badge-muted" style="margin-left:8px;"><?= $totalRows ?> total</span>
    </div>
    <span style="font-size:12px;color:var(--text-muted);"><?= formatDate($from,'M d') ?> – <?= formatDate($to,'M d, Y') ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Reference</th><th>Date</th><th>Type</th><th>Category</th>
          <th>Description</th><th>Amount</th><th>Recorded By</th><th>Status</th>
          <?php if ($canApprove): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="9">
          <div class="empty-state" style="padding:36px;"><?= icon('inbox',36) ?><p>No transactions in this period</p></div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($transactions as $t): ?>
        <tr>
          <td>
            <div style="font-family:monospace;font-size:11px;color:var(--gold);"><?= sanitize($t['reference_no']) ?></div>
            <?php if ($t['receipt_no']): ?>
            <div class="td-muted" style="font-size:10px;">Rcpt: <?= sanitize($t['receipt_no']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-size:13px;"><?= formatDate($t['transaction_date'],'M d, Y') ?></div>
            <div class="td-muted"><?= timeAgo($t['created_at']) ?></div>
          </td>
          <td>
            <span class="badge <?= $t['type']==='income' ? 'badge-success' : ($t['type']==='expense'?'badge-danger':'badge-info') ?>">
              <?= ucfirst($t['type']) ?>
            </span>
          </td>
          <td style="font-size:13px;"><?= sanitize($t['category']) ?></td>
          <td style="font-size:12.5px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= sanitize($t['description']) ?>
          </td>
          <td class="td-bold" style="color:<?= $t['type']==='income'?'var(--success)':'var(--danger)' ?>;">
            <?= $t['type']==='income' ? '+' : '−' ?><?= formatMoney($t['amount']) ?>
          </td>
          <td class="td-muted"><?= sanitize($t['rec_first'][0].'.'.$t['rec_last']) ?></td>
          <td><?= statusBadge($t['status']) ?></td>
          <?php if ($canApprove): ?>
          <td>
            <?php if ($t['status'] === 'pending'): ?>
            <div style="display:flex;gap:4px;">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="approve_txn">
                <input type="hidden" name="txn_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="new_status" value="approved">
                <button type="submit" class="btn btn-success btn-sm"
                  data-confirm="Approve this transaction?">✓</button>
              </form>
              <button class="btn btn-danger btn-sm"
                onclick="openRejectModal(<?= $t['id'] ?>)">✗</button>
            </div>
            <?php else: ?>
            <span class="td-muted" style="font-size:11px;">
              <?= $t['apr_first'] ? sanitize($t['apr_first'][0].'.'.$t['apr_last']) : '—' ?>
            </span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="padding:16px;display:flex;gap:6px;justify-content:center;">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"
       class="btn btn-sm <?= $p===$page?'btn-primary':'btn-outline' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ═══ MODALS ═══ -->
<div class="modal-overlay" id="addTxnModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Record Transaction</div>
      <button class="modal-close" onclick="closeModal('addTxnModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_transaction">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type <span style="color:var(--danger)">*</span></label>
            <select name="type" class="form-control" required>
              <option value="income">Income</option>
              <option value="expense">Expense</option>
              <option value="transfer">Transfer</option>
              <option value="refund">Refund</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (ZMW) <span style="color:var(--danger)">*</span></label>
            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
          <input type="text" name="category" class="form-control" placeholder="e.g. Tuition Fee, Utilities, Salaries" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--danger)">*</span></label>
          <textarea name="description" class="form-control" rows="2" placeholder="Describe this transaction..." required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
              <option value="">— Select —</option>
              <option>Cash</option><option>Mobile Money</option>
              <option>Bank Transfer</option><option>Cheque</option><option>Card</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Receipt / Ref No.</label>
            <input type="text" name="receipt_no" class="form-control" placeholder="Optional">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Date <span style="color:var(--danger)">*</span></label>
          <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <?php if (!$canApprove): ?>
        <div class="alert alert-info" style="font-size:12px;"><?= icon('clock',13) ?> Requires Bursar approval before posting.</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTxnModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Record</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="rejectTxnModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <div class="modal-title" style="color:var(--danger);"><?= icon('x',16) ?> Reject Transaction</div>
      <button class="modal-close" onclick="closeModal('rejectTxnModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="approve_txn">
      <input type="hidden" name="new_status" value="rejected">
      <input type="hidden" name="txn_id" id="rejectTxnId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason <span style="color:var(--danger)">*</span></label>
          <textarea name="reason" class="form-control" rows="3" placeholder="Why is this transaction being rejected?" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('rejectTxnModal')">Cancel</button>
        <button type="submit" class="btn btn-danger">Reject</button>
      </div>
    </form>
  </div>
</div>

<script>
function openRejectModal(id) {
  document.getElementById('rejectTxnId').value = id;
  openModal('rejectTxnModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
