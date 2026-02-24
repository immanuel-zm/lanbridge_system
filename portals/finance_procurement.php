<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isFinanceRole($user) && (int)$user['role_level'] > 6) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'])); exit;
}

$isAuditor    = ($user['role_slug'] === 'auditor');
$isFinance    = isFinanceRole($user);
$canApprove   = in_array($user['role_slug'], ['bursar','finance_admin','ceo','principal']);
$canRequest   = !$isAuditor;
$uid          = (int)$user['id'];
$deptId       = (int)($user['department_id'] ?? 0);
$db           = getDB();

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuditor) {
    $action = $_POST['action'] ?? '';

    // Submit procurement request
    if ($action === 'submit_request') {
        $reqNo   = generateRef('PRQ');
        $dept    = $isFinance ? (int)($_POST['requesting_dept'] ?? $deptId) : $deptId;
        $item    = trim($_POST['item_name'] ?? '');
        $desc    = trim($_POST['item_description'] ?? '');
        $qty     = abs((float)($_POST['quantity'] ?? 1));
        $unit    = trim($_POST['unit'] ?? '');
        $cost    = abs((float)($_POST['estimated_cost'] ?? 0));
        $vendor  = trim($_POST['vendor_name'] ?? '');
        $vcon    = trim($_POST['vendor_contact'] ?? '');
        $urgency = $_POST['urgency'] ?? 'medium';
        if (!in_array($urgency,['low','medium','high','critical'])) $urgency = 'medium';

        if ($item && $qty > 0 && $cost > 0 && $dept) {
            $db->prepare(
                "INSERT INTO procurement_requests
                 (request_no,requesting_dept,requesting_user,item_name,item_description,
                  quantity,unit,estimated_cost,vendor_name,vendor_contact,urgency,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,'submitted')"
            )->execute([$reqNo,$dept,$uid,$item,$desc,$qty,$unit,$cost,$vendor,$vcon,$urgency]);
            $newId = (int)$db->lastInsertId();

            // Notify finance admin/bursar
            $financeStaff = $db->query(
                "SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id
                 WHERE r.slug IN ('finance_admin','bursar') AND u.is_active=1 AND u.id!=$uid"
            )->fetchAll(PDO::FETCH_COLUMN);
            foreach ($financeStaff as $fid) {
                sendNotification((int)$fid,
                    '🛒 New Procurement Request: '.$item,
                    $urgency.' priority request for '.formatMoney($cost).' from '.$user['first_name'].' '.$user['last_name'].'.',
                    $urgency==='critical'?'danger':($urgency==='high'?'warning':'info'),
                    SITE_URL.'/portals/finance_procurement.php?view='.$newId
                );
            }
            logFinanceAudit($uid,'PROCUREMENT_SUBMITTED','procurement_requests',$newId,null,
                ['item'=>$item,'cost'=>$cost,'urgency'=>$urgency]);
            logActivity($uid,'PROCUREMENT_REQUEST','Request '.$reqNo.': '.$item.' — '.formatMoney($cost));
            setFlash('success','✅ Request '.$reqNo.' submitted successfully.');
        } else {
            setFlash('danger','❌ Item name, quantity, estimated cost, and department are required.');
        }
        header('Location: finance_procurement.php'); exit;
    }

    // Finance review / approve / reject
    if ($action === 'review_request' && $canApprove) {
        $reqId      = (int)($_POST['req_id'] ?? 0);
        $newStatus  = $_POST['new_status'] ?? '';
        $notes      = trim($_POST['finance_notes'] ?? '');
        $allowed    = ['finance_review','approved','rejected','ordered','received'];
        if (!in_array($newStatus, $allowed)) { setFlash('danger','Invalid status.'); header('Location: finance_procurement.php'); exit; }

        $old = $db->query("SELECT * FROM procurement_requests WHERE id=$reqId")->fetch();
        $approvedAt = in_array($newStatus,['approved']) ? ',approved_by='.$uid.',approved_at=NOW()' : '';
        $db->prepare("UPDATE procurement_requests SET status=?,finance_notes=?,updated_at=NOW()$approvedAt WHERE id=?")
           ->execute([$newStatus,$notes,$reqId]);

        if ($old) {
            sendNotification((int)$old['requesting_user'],
                '🛒 Procurement Update: '.sanitize($old['item_name']),
                'Your request status changed to: '.ucfirst(str_replace('_',' ',$newStatus)).($notes?' — '.$notes:''),
                in_array($newStatus,['approved','received'])?'success':($newStatus==='rejected'?'danger':'info'),
                SITE_URL.'/portals/finance_procurement.php?view='.$reqId
            );
            logFinanceAudit($uid,'PROCUREMENT_'.strtoupper($newStatus),'procurement_requests',$reqId,
                ['status'=>$old['status']],['status'=>$newStatus,'notes'=>$notes]);
        }
        setFlash('success','✅ Request updated to '.ucfirst(str_replace('_',' ',$newStatus)).'.');
        header('Location: finance_procurement.php?view='.$reqId); exit;
    }
}

$pageTitle    = 'Procurement';
$pageSubtitle = 'Purchase Requests & Approval Workflow';
require_once __DIR__ . '/../includes/header.php';

// ── Single request view ───────────────────────────────────────
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $req = $db->query(
        "SELECT pr.*,
                d.name AS dept_name,
                u.first_name AS req_first, u.last_name AS req_last,
                a.first_name AS appr_first, a.last_name AS appr_last
         FROM procurement_requests pr
         JOIN departments d ON pr.requesting_dept=d.id
         JOIN users u ON pr.requesting_user=u.id
         LEFT JOIN users a ON pr.approved_by=a.id
         WHERE pr.id=$viewId"
    )->fetch();

    if (!$req) { setFlash('danger','Request not found.'); header('Location: finance_procurement.php'); exit; }

    $canView = $isFinance || (int)$req['requesting_user']===$uid || (int)$req['requesting_dept']===$deptId;
    if (!$canView) { setFlash('danger','Access denied.'); header('Location: finance_procurement.php'); exit; }

    $statusColors = ['draft'=>'var(--text-muted)','submitted'=>'var(--info)','finance_review'=>'var(--warning)',
                     'approved'=>'var(--success)','rejected'=>'var(--danger)','ordered'=>'var(--gold)','received'=>'var(--success)'];
    $urgBadge     = ['low'=>'muted','medium'=>'info','high'=>'warning','critical'=>'danger'];
?>

<div style="margin-bottom:16px;">
  <a href="finance_procurement.php" style="color:var(--text-muted);font-size:13px;text-decoration:none;"><?= icon('arrow-left',13) ?> Back to Procurement</a>
</div>

<div class="card mb-24">
  <div class="card-header">
    <div>
      <div class="card-title"><?= icon('shopping-cart') ?> <?= sanitize($req['item_name']) ?></div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
        Ref: <strong style="font-family:monospace;color:var(--gold);"><?= sanitize($req['request_no']) ?></strong>
        · <?= sanitize($req['dept_name']) ?>
        · Submitted by <?= sanitize($req['req_first'].' '.$req['req_last']) ?>
        · <?= timeAgo($req['created_at']) ?>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <span class="badge badge-<?= $urgBadge[$req['urgency']] ?>"><?= ucfirst($req['urgency']) ?></span>
      <span style="font-size:13px;font-weight:700;color:<?= $statusColors[$req['status']] ?? 'var(--text-muted)' ?>;">
        <?= ucfirst(str_replace('_',' ',$req['status'])) ?>
      </span>
    </div>
  </div>
  <div class="card-body">

    <!-- Details grid -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid var(--border);">
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">QUANTITY</div>
        <div style="font-size:16px;font-weight:700;color:var(--text-primary);"><?= number_format((float)$req['quantity'],2) ?> <span style="font-size:12px;color:var(--text-muted);"><?= sanitize($req['unit']??'') ?></span></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">ESTIMATED COST</div>
        <div style="font-size:16px;font-weight:700;color:var(--gold);"><?= formatMoney((float)$req['estimated_cost']) ?></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">VENDOR</div>
        <div style="font-size:13px;font-weight:600;"><?= sanitize($req['vendor_name']??'—') ?></div>
        <?php if ($req['vendor_contact']): ?><div class="td-muted" style="font-size:11px;"><?= sanitize($req['vendor_contact']) ?></div><?php endif; ?>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">APPROVED BY</div>
        <div style="font-size:13px;font-weight:600;"><?= $req['appr_first'] ? sanitize($req['appr_first'].' '.$req['appr_last']) : '—' ?></div>
        <?php if ($req['approved_at']): ?><div class="td-muted" style="font-size:11px;"><?= date('M d, Y',strtotime($req['approved_at'])) ?></div><?php endif; ?>
      </div>
    </div>

    <?php if ($req['item_description']): ?>
    <div style="margin-bottom:16px;">
      <div style="font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:6px;">DESCRIPTION</div>
      <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:13.5px;line-height:1.8;white-space:pre-wrap;"><?= sanitize($req['item_description']) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($req['finance_notes']): ?>
    <div style="background:rgba(201,168,76,0.06);border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:14px;margin-bottom:16px;">
      <div style="font-size:11px;font-weight:700;color:var(--gold);margin-bottom:6px;">💬 FINANCE NOTES</div>
      <div style="font-size:13px;line-height:1.7;"><?= sanitize($req['finance_notes']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Review form -->
    <?php if ($canApprove && !in_array($req['status'],['received','rejected'])): ?>
    <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:16px;">
      <div style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:12px;"><?= icon('settings',13) ?> Update Request Status</div>
      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="action" value="review_request">
        <input type="hidden" name="req_id" value="<?= $viewId ?>">
        <div>
          <label class="form-label" style="font-size:11px;">New Status</label>
          <select name="new_status" class="form-control" style="width:180px;">
            <?php foreach (['finance_review'=>'Finance Review','approved'=>'Approved','rejected'=>'Rejected','ordered'=>'Ordered','received'=>'Received'] as $s=>$l): ?>
            <option value="<?= $s ?>" <?= $req['status']===$s?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex:1;min-width:200px;">
          <label class="form-label" style="font-size:11px;">Finance Notes</label>
          <input type="text" name="finance_notes" class="form-control" value="<?= sanitize($req['finance_notes']??'') ?>" placeholder="Approval conditions, rejection reason, delivery notes…">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= icon('save',13) ?> Update</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── List view ─────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$filterUrgency= $_GET['urgency'] ?? '';
$filterDept   = (int)($_GET['dept'] ?? 0);
$tab          = $_GET['tab'] ?? 'all';

$where  = ['1=1'];
$params = [];

// Non-finance users only see their dept's requests
if (!$isFinance) {
    $where[] = "pr.requesting_dept=$deptId";
} elseif ($tab === 'pending') {
    $where[] = "pr.status IN ('submitted','finance_review')";
} elseif ($tab === 'approved') {
    $where[] = "pr.status IN ('approved','ordered','received')";
}

if ($filterStatus)  { $where[] = 'pr.status=?';          $params[] = $filterStatus; }
if ($filterUrgency) { $where[] = 'pr.urgency=?';         $params[] = $filterUrgency; }
if ($filterDept)    { $where[] = 'pr.requesting_dept=?'; $params[] = $filterDept; }

$requests = $db->prepare(
    "SELECT pr.*,
            d.name AS dept_name,
            u.first_name AS req_first, u.last_name AS req_last,
            a.first_name AS appr_first, a.last_name AS appr_last
     FROM procurement_requests pr
     JOIN departments d ON pr.requesting_dept=d.id
     JOIN users u ON pr.requesting_user=u.id
     LEFT JOIN users a ON pr.approved_by=a.id
     WHERE ".implode(' AND ',$where)."
     ORDER BY FIELD(pr.urgency,'critical','high','medium','low'),
              FIELD(pr.status,'submitted','finance_review','approved','ordered','received','rejected','draft'),
              pr.created_at DESC
     LIMIT 100"
);
$requests->execute($params);
$requests = $requests->fetchAll();

// Stats
$pendingCount    = (int)$db->query("SELECT COUNT(*) FROM procurement_requests WHERE status IN ('submitted','finance_review')")->fetchColumn();
$approvedTotal   = (float)$db->query("SELECT COALESCE(SUM(estimated_cost),0) FROM procurement_requests WHERE status IN ('approved','ordered','received') AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$receivedCount   = (int)$db->query("SELECT COUNT(*) FROM procurement_requests WHERE status='received' AND MONTH(created_at)=MONTH(NOW())")->fetchColumn();
$criticalPending = (int)$db->query("SELECT COUNT(*) FROM procurement_requests WHERE urgency='critical' AND status IN ('submitted','finance_review')")->fetchColumn();

$allDepts = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();

$statusColors = ['draft'=>'var(--text-muted)','submitted'=>'var(--info)','finance_review'=>'var(--warning)',
                 'approved'=>'var(--success)','rejected'=>'var(--danger)','ordered'=>'var(--gold)','received'=>'var(--success)'];
$urgBadge = ['low'=>'muted','medium'=>'info','high'=>'warning','critical'=>'danger'];
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card <?= $pendingCount>0?'orange':'green' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $pendingCount ?></div><div class="stat-label">Pending Review</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta <?= $pendingCount===0?'up':'down' ?>"><?= $criticalPending ?> critical pending</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number" style="font-size:22px;"><?= formatMoney($approvedTotal) ?></div><div class="stat-label">Approved This Year</div></div>
      <div class="stat-icon"><?= icon('trending-up',20) ?></div>
    </div>
    <div class="stat-delta">Total approved procurement value</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $receivedCount ?></div><div class="stat-label">Received This Month</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta up">Items delivered this month</div>
  </div>
  <div class="stat-card <?= $criticalPending>0?'':'blue' ?>" style="<?= $criticalPending>0?'border-top-color:var(--danger);':'' ?>">
    <div class="stat-top">
      <div><div class="stat-number" style="color:<?= $criticalPending>0?'var(--danger)':'var(--success)' ?>;"><?= $criticalPending ?></div><div class="stat-label">Critical Pending</div></div>
      <div class="stat-icon"><?= icon('alert-triangle',20) ?></div>
    </div>
    <div class="stat-delta <?= $criticalPending>0?'down':'up' ?>"><?= $criticalPending>0?'Urgent — action needed':'All clear' ?></div>
  </div>
</div>

<!-- Critical banner -->
<?php if ($criticalPending > 0 && $canApprove): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.4);background:rgba(232,85,106,0.04);">
  <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:22px;">🚨</span>
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:700;color:var(--danger);"><?= $criticalPending ?> critical procurement request<?= $criticalPending>1?'s':'' ?> awaiting approval</div>
      <div style="font-size:12px;color:var(--text-muted);">These have been flagged as critical urgency by requesting departments.</div>
    </div>
    <a href="finance_procurement.php?tab=pending&urgency=critical" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;">Review Now</a>
  </div>
</div>
<?php endif; ?>

<!-- Filters + New Request -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('filter') ?> Filter Requests</div>
    <?php if ($canRequest): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('newRequestModal')"><?= icon('plus',13) ?> New Request</button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <?php if ($isFinance): ?>
      <div>
        <label class="form-label" style="font-size:11px;">View</label>
        <select name="tab" class="form-control" style="width:140px;" onchange="this.form.submit()">
          <option value="all"      <?= $tab==='all'?'selected':'' ?>>All Requests</option>
          <option value="pending"  <?= $tab==='pending'?'selected':'' ?>>Pending Review</option>
          <option value="approved" <?= $tab==='approved'?'selected':'' ?>>Approved</option>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Department</label>
        <select name="dept" class="form-control" style="width:160px;">
          <option value="">All Departments</option>
          <?php foreach ($allDepts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDept==$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label class="form-label" style="font-size:11px;">Status</label>
        <select name="status" class="form-control" style="width:150px;">
          <option value="">All Statuses</option>
          <?php foreach (['draft','submitted','finance_review','approved','rejected','ordered','received'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Urgency</label>
        <select name="urgency" class="form-control" style="width:130px;">
          <option value="">All Urgencies</option>
          <?php foreach (['critical','high','medium','low'] as $u): ?>
          <option value="<?= $u ?>" <?= $filterUrgency===$u?'selected':'' ?>><?= ucfirst($u) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('search',13) ?> Filter</button>
      <a href="finance_procurement.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<!-- Requests Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('shopping-cart') ?> Procurement Requests <span class="badge badge-muted" style="margin-left:6px;"><?= count($requests) ?></span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Ref #</th>
          <th>Item</th>
          <?php if ($isFinance): ?><th>Department</th><?php endif; ?>
          <th>Urgency</th>
          <th>Est. Cost</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
        <tr><td colspan="8">
          <div class="empty-state" style="padding:48px;">
            <?= icon('shopping-cart',44) ?>
            <h3>No procurement requests found</h3>
            <p>Submit a new purchase request to get started.</p>
            <?php if ($canRequest): ?>
            <button class="btn btn-primary" style="margin-top:14px;" onclick="openModal('newRequestModal')"><?= icon('plus',14) ?> New Request</button>
            <?php endif; ?>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td>
            <span style="font-family:monospace;font-size:11px;font-weight:700;color:var(--gold);"><?= sanitize($r['request_no']) ?></span>
          </td>
          <td>
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($r['item_name']) ?></div>
            <?php if ($r['vendor_name']): ?>
            <div class="td-muted" style="font-size:10px;">Vendor: <?= sanitize($r['vendor_name']) ?></div>
            <?php endif; ?>
          </td>
          <?php if ($isFinance): ?>
          <td class="td-muted text-sm"><?= sanitize($r['dept_name']) ?></td>
          <?php endif; ?>
          <td><span class="badge badge-<?= $urgBadge[$r['urgency']] ?>"><?= ucfirst($r['urgency']) ?></span></td>
          <td style="font-weight:700;color:var(--gold);"><?= formatMoney((float)$r['estimated_cost']) ?></td>
          <td>
            <span style="font-size:12px;font-weight:600;color:<?= $statusColors[$r['status']] ?? 'var(--text-muted)' ?>;">
              <?= ucfirst(str_replace('_',' ',$r['status'])) ?>
            </span>
          </td>
          <td class="td-muted text-sm"><?= timeAgo($r['created_at']) ?></td>
          <td>
            <a href="finance_procurement.php?view=<?= $r['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- New Request Modal -->
<?php if ($canRequest): ?>
<div class="modal-overlay" id="newRequestModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('shopping-cart') ?> New Procurement Request</div>
      <button class="modal-close" onclick="closeModal('newRequestModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="submit_request">
      <div class="modal-body">
        <?php if ($isFinance): ?>
        <div class="form-group">
          <label class="form-label">Requesting Department</label>
          <select name="requesting_dept" class="form-control" required>
            <option value="">— Select Department —</option>
            <?php foreach ($allDepts as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $d['id']===$deptId?'selected':'' ?>><?= sanitize($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Item Name <span style="color:var(--danger);">*</span></label>
            <input type="text" name="item_name" class="form-control" placeholder="e.g. HP LaserJet Printer" required>
          </div>
          <div class="form-group">
            <label class="form-label">Urgency</label>
            <select name="urgency" class="form-control">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="critical">🚨 Critical</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="item_description" class="form-control" rows="3" placeholder="Why is this needed? Specifications, purpose, justification…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quantity <span style="color:var(--danger);">*</span></label>
            <input type="number" name="quantity" class="form-control" value="1" min="0.01" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" placeholder="e.g. pcs, boxes, reams">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Estimated Cost (ZMW) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="estimated_cost" class="form-control" placeholder="0.00" min="0" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Preferred Vendor</label>
            <input type="text" name="vendor_name" class="form-control" placeholder="Vendor/supplier name">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Vendor Contact</label>
          <input type="text" name="vendor_contact" class="form-control" placeholder="Phone, email or address">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('newRequestModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Submit Request</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
