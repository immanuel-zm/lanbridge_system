<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isItRole($user)) {
    header('Location: ' . SITE_URL . '/portals/' . getRoleDashboard($user['role_slug'], $user)); exit;
}

$isItAdmin = in_array($user['role_slug'], ['it_admin','ceo','principal']) || (($user['role_slug']==='dept_head') && strtoupper($user['dept_code']??'')=='IT');
$uid       = (int)$user['id'];
$db        = getDB();

$sla = [
    'critical' => (int)getSetting('sla_critical_hours', '2'),
    'high'     => (int)getSetting('sla_high_hours', '8'),
    'medium'   => (int)getSetting('sla_medium_hours', '24'),
    'low'      => (int)getSetting('sla_low_hours', '72'),
];

// ── Handle quick POST (submit ticket from dashboard) ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit_ticket') {
        $subject  = trim($_POST['subject'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'other';
        $priority = $_POST['priority'] ?? 'medium';
        if (!in_array($category, ['hardware','software','network','access','email','other'])) $category = 'other';
        if (!in_array($priority, ['low','medium','high','critical'])) $priority = 'medium';

        if ($subject && strlen($desc) >= 10) {
            $ticketNo = generateRef('TKT');
            $slaHours = $sla[$priority];
            $slaDL    = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));
            $deptId   = (int)$user['department_id'];

            $db->prepare(
                "INSERT INTO it_tickets (ticket_no,submitted_by,dept_id,category,priority,subject,description,sla_deadline)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$ticketNo,$uid,$deptId,$category,$priority,$subject,$desc,$slaDL]);

            $itStaff = $db->query(
                "SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id
                 WHERE r.slug IN ('it_admin','it_officer') AND u.is_active=1 AND u.id != $uid"
            )->fetchAll(PDO::FETCH_COLUMN);
            foreach ($itStaff as $itId) {
                sendNotification((int)$itId,
                    '🎫 New '.$priority.' ticket: '.$subject,
                    'From '.$user['first_name'].' '.$user['last_name'].' ('.$user['dept_name'].'). SLA: '.date('M d H:i',strtotime($slaDL)),
                    $priority === 'critical' ? 'danger' : ($priority === 'high' ? 'warning' : 'info'),
                    SITE_URL.'/portals/it_tickets.php'
                );
            }
            logActivity($uid,'IT_TICKET_SUBMITTED','Ticket '.$ticketNo.': '.$subject);
            setFlash('success','✅ Ticket '.$ticketNo.' submitted. IT team notified.');
        } else {
            setFlash('danger','❌ Subject and description (min 10 chars) are required.');
        }
        header('Location: it_dashboard.php'); exit;
    }

    if ($action === 'update_ticket' && $isItAdmin) {
        $ticketId   = (int)($_POST['ticket_id'] ?? 0);
        $newStatus  = $_POST['new_status'] ?? '';
        $assignTo   = (int)($_POST['assign_to'] ?? 0) ?: null;
        $resolution = trim($_POST['resolution_notes'] ?? '');
        $allowed    = ['open','in_progress','pending_user','resolved','closed','cancelled'];
        if (!in_array($newStatus, $allowed)) { setFlash('danger','Invalid status.'); header('Location: it_dashboard.php'); exit; }

        $resolvedAt = in_array($newStatus,['resolved','closed']) ? ',resolved_at=NOW()' : '';
        $db->prepare("UPDATE it_tickets SET status=?,assigned_to=?,resolution_notes=?,updated_at=NOW()$resolvedAt WHERE id=?")
           ->execute([$newStatus,$assignTo,$resolution,$ticketId]);

        $ticket = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if ($ticket) {
            sendNotification((int)$ticket['submitted_by'],
                '🎫 Ticket '.$ticket['ticket_no'].' updated',
                'Status changed to: '.ucfirst(str_replace('_',' ',$newStatus)).($resolution?' — '.$resolution:''),
                in_array($newStatus,['resolved','closed']) ? 'success' : 'info',
                SITE_URL.'/portals/it_tickets.php'
            );
        }
        logActivity($uid,'IT_TICKET_UPDATE','Ticket ID '.$ticketId.' → '.$newStatus);
        setFlash('success','✅ Ticket updated.');
        header('Location: it_dashboard.php'); exit;
    }

    // Quick assign to self
    if ($action === 'quick_assign') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ticket   = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if ($ticket && in_array($ticket['status'],['open','in_progress','pending_user'])) {
            $db->prepare("UPDATE it_tickets SET assigned_to=?,status='in_progress',updated_at=NOW() WHERE id=?")
               ->execute([$uid,$ticketId]);
            try {
                $db->prepare("INSERT INTO it_ticket_activity_log (ticket_id,action,performed_by,note) VALUES (?,?,?,?)")
                   ->execute([$ticketId,'ASSIGNED',$uid,'Self-assigned via dashboard']);
            } catch(Throwable $e) {}
            sendNotification((int)$ticket['submitted_by'],
                '🔧 Ticket '.$ticket['ticket_no'].' — Picked Up',
                'Your ticket has been assigned and is now in progress.',
                'info', SITE_URL.'/portals/helpdesk.php?view='.$ticketId);
            logActivity($uid,'IT_TICKET_ASSIGNED','Claimed ticket ID '.$ticketId);
            setFlash('success','✅ Ticket claimed — set to In Progress.');
        }
        header('Location: it_dashboard.php'); exit;
    }
}

$pageTitle    = 'IT Dashboard';
$pageSubtitle = 'Systems, Helpdesk & Asset Operations';
require_once __DIR__ . '/../includes/header.php';

// ── Metrics ───────────────────────────────────────────────────
$openTickets    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE status IN ('open','in_progress','pending_user')")->fetchColumn();
$resolvedToday  = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE status='resolved' AND DATE(resolved_at)='".date('Y-m-d')."'")->fetchColumn();
$criticalOpen   = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE priority='critical' AND status IN ('open','in_progress')")->fetchColumn();
$slaBreached    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE sla_deadline < NOW() AND status IN ('open','in_progress','pending_user')")->fetchColumn();
$totalAssets    = (int)$db->query("SELECT COUNT(*) FROM it_assets")->fetchColumn();
$assetsIssued   = (int)$db->query("SELECT COUNT(*) FROM it_assets WHERE assigned_to IS NOT NULL")->fetchColumn();
$warrantyExpiring = (int)$db->query("SELECT COUNT(*) FROM it_assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 90 DAY)")->fetchColumn();
$myAssigned     = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE assigned_to=$uid AND status IN ('open','in_progress','pending_user')")->fetchColumn();

// Unassigned tickets (need immediate action)
$unassignedTickets = $db->query(
    "SELECT t.*, u.first_name, u.last_name, d.name AS dept_name
     FROM it_tickets t
     JOIN users u ON t.submitted_by=u.id
     JOIN departments d ON t.dept_id=d.id
     WHERE t.assigned_to IS NULL AND t.status='open'
     ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.opened_at ASC
     LIMIT 10"
)->fetchAll();
$unassignedCount = count($unassignedTickets);

// Recent open tickets (latest 8)
$recentTickets = $db->query(
    "SELECT t.*,
            u.first_name, u.last_name,
            d.name AS dept_name,
            a.first_name AS asgn_first, a.last_name AS asgn_last
     FROM it_tickets t
     JOIN users u ON t.submitted_by=u.id
     JOIN departments d ON t.dept_id=d.id
     LEFT JOIN users a ON t.assigned_to=a.id
     WHERE t.status IN ('open','in_progress','pending_user')
     ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.opened_at ASC
     LIMIT 8"
)->fetchAll();

// Asset breakdown by type
$assetBreakdown = $db->query(
    "SELECT asset_type, COUNT(*) AS total,
            SUM(assigned_to IS NOT NULL) AS assigned,
            SUM(condition_status='decommissioned') AS decom,
            SUM(condition_status IN ('poor','fair')) AS needs_attention
     FROM it_assets GROUP BY asset_type ORDER BY total DESC"
)->fetchAll();

// Ticket volume by category (last 30 days)
$ticketsByCategory = $db->query(
    "SELECT category, COUNT(*) AS cnt
     FROM it_tickets
     WHERE opened_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY category ORDER BY cnt DESC"
)->fetchAll();

// IT staff for assignment
$itStaffList = $db->query(
    "SELECT u.id,u.first_name,u.last_name FROM users u
     JOIN roles r ON u.role_id=r.id
     WHERE r.slug IN ('it_admin','it_officer') AND u.is_active=1
     ORDER BY u.first_name"
)->fetchAll();

function slaBadge(array $t): string {
    if (in_array($t['status'], ['resolved','closed','cancelled'])) return '<span style="color:var(--success);font-size:11px;">✓ Done</span>';
    if (!$t['sla_deadline']) return '<span class="td-muted">—</span>';
    $remaining = strtotime($t['sla_deadline']) - time();
    if ($remaining < 0) return '<span style="color:var(--danger);font-size:11px;font-weight:700;">⚡ Breached</span>';
    $hrs = floor($remaining / 3600);
    $col = $hrs < 2 ? 'var(--danger)' : ($hrs < 6 ? 'var(--warning)' : 'var(--text-muted)');
    return "<span style=\"color:$col;font-size:11px;\">{$hrs}h left</span>";
}

function ticketStatusColor(string $s): string {
    return ['open'=>'var(--info)','in_progress'=>'var(--warning)','pending_user'=>'var(--gold)',
            'resolved'=>'var(--success)','closed'=>'var(--text-muted)','cancelled'=>'var(--danger)'][$s] ?? 'var(--text-muted)';
}

$typeIcons = ['laptop'=>'💻','desktop'=>'🖥️','printer'=>'🖨️','server'=>'🗄️',
              'switch'=>'🔀','projector'=>'📽️','phone'=>'📱','tablet'=>'📟','other'=>'🔧'];
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- STAT CARDS                                                  -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stat-grid">
  <div class="stat-card <?= $openTickets > 10 ? 'orange' : ($openTickets > 0 ? 'gold' : 'green') ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $openTickets ?></div><div class="stat-label">Open Tickets</div></div>
      <div class="stat-icon"><?= icon('message-square',20) ?></div>
    </div>
    <div class="stat-delta <?= $openTickets === 0 ? 'up' : '' ?>"><?= $myAssigned ?> assigned to me</div>
  </div>
  <div class="stat-card <?= $slaBreached > 0 ? '' : 'green' ?>" style="<?= $slaBreached > 0 ? 'border-top-color:var(--danger);' : '' ?>">
    <div class="stat-top">
      <div><div class="stat-number" style="color:<?= $slaBreached>0?'var(--danger)':'var(--success)' ?>;"><?= $slaBreached ?></div><div class="stat-label">SLA Breaches</div></div>
      <div class="stat-icon"><?= icon('alert-triangle',20) ?></div>
    </div>
    <div class="stat-delta <?= $slaBreached > 0 ? 'down' : 'up' ?>"><?= $slaBreached > 0 ? 'Requires urgent action' : 'All within SLA!' ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $resolvedToday ?></div><div class="stat-label">Resolved Today</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta up"><?= $criticalOpen ?> critical still open</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalAssets ?></div><div class="stat-label">Total Assets</div></div>
      <div class="stat-icon"><?= icon('layers',20) ?></div>
    </div>
    <div class="stat-delta"><?= $assetsIssued ?> assigned · <?= $warrantyExpiring ?> warranty expiring</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- SLA BREACH ALERT BANNER                                     -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php if ($slaBreached > 0): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.4);background:rgba(232,85,106,0.04);">
  <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:22px;">⚡</span>
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:700;color:var(--danger);"><?= $slaBreached ?> ticket<?= $slaBreached>1?'s':'' ?> have breached their SLA deadline</div>
      <div style="font-size:12px;color:var(--text-muted);">These require immediate attention to maintain service quality.</div>
    </div>
    <a href="it_tickets.php?priority=&status=open" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;">View Breached Tickets</a>
  </div>
</div>
<?php endif; ?>

<?php if ($unassignedCount > 0): ?>
<!-- ═══════════════════════════════════════ UNASSIGNED INCOMING ═ -->
<div class="card mb-24" style="border:1px solid rgba(201,168,76,0.5);background:rgba(201,168,76,0.03);">
  <div class="card-header" style="background:rgba(201,168,76,0.07);border-bottom:1px solid rgba(201,168,76,0.15);">
    <div class="card-title" style="color:var(--gold);"><?= icon('inbox',16) ?>
      🔔 Incoming — Unassigned Tickets
      <span class="badge badge-warning" style="margin-left:8px;"><?= $unassignedCount ?> waiting</span>
    </div>
    <a href="it_tickets.php" class="btn btn-outline btn-sm"><?= icon('arrow-right',12) ?> Manage All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ticket</th><th>Subject</th><th>From</th><th>Priority</th><th>SLA</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($unassignedTickets as $t):
          $slaRem = $t['sla_deadline'] ? strtotime($t['sla_deadline']) - time() : null;
          $breached = $slaRem !== null && $slaRem < 0;
          $slaColor = $breached ? 'var(--danger)' : ($slaRem !== null && $slaRem<7200 ? 'var(--warning)' : 'var(--text-muted)');
          $priColor = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'][$t['priority']] ?? 'muted';
        ?>
        <tr style="<?= $breached?'background:rgba(232,85,106,0.04);':'' ?><?= $t['priority']==='critical'?'border-left:3px solid var(--danger);':'' ?>">
          <td>
            <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></div>
            <div style="font-size:10px;color:var(--text-muted);"><?= timeAgo($t['opened_at']) ?></div>
          </td>
          <td>
            <a href="it_tickets.php?view=<?= $t['id'] ?>" style="font-size:12.5px;font-weight:600;color:var(--text-primary);text-decoration:none;display:block;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= sanitize($t['subject']) ?>
            </a>
            <div style="font-size:10px;color:var(--text-muted);text-transform:capitalize;"><?= $t['category'] ?> · <?= sanitize($t['dept_name']) ?></div>
          </td>
          <td style="font-size:12px;"><?= sanitize($t['first_name'].' '.$t['last_name']) ?></td>
          <td><span class="badge badge-<?= $priColor ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td>
            <span style="font-size:12px;font-weight:700;color:<?= $slaColor ?>;">
              <?= $breached ? '⚡ Breached' : ($slaRem !== null ? floor($slaRem/3600).'h '.floor(($slaRem%3600)/60).'m' : '—') ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="quick_assign">
                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm"><?= icon('user-check',12) ?> Claim</button>
              </form>
              <a href="it_tickets.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- QUICK ACTION TILES                                          -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="action-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <a href="it_tickets.php" class="action-tile">
    <?= icon('message-square',28) ?>
    <div class="action-tile-label">All Tickets</div>
    <?php if ($openTickets): ?><div class="action-tile-count"><?= $openTickets ?> open</div><?php endif; ?>
  </a>
  <a href="it_tickets.php?tab=mine" class="action-tile">
    <?= icon('user',28) ?>
    <div class="action-tile-label">My Assigned</div>
    <?php if ($myAssigned): ?><div class="action-tile-count"><?= $myAssigned ?> pending</div><?php endif; ?>
  </a>
  <a href="it_assets.php" class="action-tile">
    <?= icon('layers',28) ?>
    <div class="action-tile-label">Asset Register</div>
    <div class="action-tile-count"><?= $totalAssets ?> assets</div>
  </a>
  <button class="action-tile" onclick="openModal('newTicketModal')" style="background:none;border:1px solid var(--border);cursor:pointer;width:100%;text-align:center;">
    <?= icon('plus-circle',28) ?>
    <div class="action-tile-label">Submit Ticket</div>
    <div class="action-tile-count">New request</div>
  </button>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MAIN GRID: Open Tickets + Asset Breakdown                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="grid-2 mb-24">

  <!-- Open Tickets -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('message-square') ?> Open Tickets
        <span class="badge <?= $openTickets>0?'badge-warning':'badge-success' ?>" style="margin-left:6px;"><?= $openTickets ?></span>
      </div>
      <a href="it_tickets.php" class="btn btn-outline btn-sm"><?= icon('external-link',12) ?> Full Helpdesk</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Ticket</th><th>Subject</th><th>Priority</th><th>SLA</th></tr></thead>
        <tbody>
          <?php if (empty($recentTickets)): ?>
          <tr><td colspan="4">
            <div class="empty-state" style="padding:28px;">
              <?= icon('check-circle',32) ?>
              <h3 style="color:var(--success);">All clear!</h3>
              <p>No open tickets at this time.</p>
            </div>
          </td></tr>
          <?php else: ?>
          <?php foreach ($recentTickets as $t):
            $breached = $t['sla_deadline'] && strtotime($t['sla_deadline']) < time();
          ?>
          <tr style="<?= $breached ? 'background:rgba(232,85,106,0.04);' : '' ?>">
            <td>
              <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></div>
              <div class="td-muted" style="font-size:10px;"><?= sanitize($t['first_name'].' '.$t['last_name']) ?></div>
            </td>
            <td>
              <a href="it_tickets.php?view=<?= $t['id'] ?>" style="font-size:12px;font-weight:600;color:var(--text-primary);text-decoration:none;display:block;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($t['subject']) ?>">
                <?= sanitize($t['subject']) ?>
              </a>
              <span style="font-size:10px;color:var(--text-muted);"><?= ucfirst($t['category']) ?> · <?= sanitize($t['dept_name']) ?></span>
            </td>
            <td>
              <span class="badge badge-<?= ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'][$t['priority']] ?>">
                <?= ucfirst($t['priority']) ?>
              </span>
            </td>
            <td><?= slaBadge($t) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($recentTickets) >= 8): ?>
    <div style="padding:10px 16px;border-top:1px solid var(--border);text-align:center;">
      <a href="it_tickets.php" style="font-size:12px;color:var(--gold);text-decoration:none;">View all open tickets →</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right column: Asset breakdown + Ticket by category -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Asset Breakdown -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('layers') ?> Asset Inventory</div>
        <a href="it_assets.php" class="btn btn-outline btn-sm"><?= icon('external-link',12) ?> Manage</a>
      </div>
      <?php if (empty($assetBreakdown)): ?>
      <div class="card-body">
        <div class="empty-state" style="padding:20px;"><?= icon('layers',28) ?><p>No assets logged yet</p>
          <?php if ($isItAdmin): ?>
          <a href="it_assets.php" class="btn btn-primary btn-sm" style="margin-top:8px;">Add First Asset</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Type</th><th>Total</th><th>Assigned</th><th>Issues</th></tr></thead>
          <tbody>
            <?php foreach ($assetBreakdown as $a): ?>
            <tr>
              <td>
                <span style="font-size:16px;"><?= $typeIcons[$a['asset_type']] ?? '🔧' ?></span>
                <span class="td-bold" style="font-size:12px;margin-left:4px;text-transform:capitalize;"><?= sanitize($a['asset_type']) ?></span>
              </td>
              <td style="font-weight:700;color:var(--gold);"><?= $a['total'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:6px;">
                  <div style="width:40px;height:5px;background:var(--bg-elevated);border-radius:3px;overflow:hidden;">
                    <div style="height:100%;width:<?= $a['total']>0?min(100,round($a['assigned']/$a['total']*100)):0 ?>%;background:var(--info);border-radius:3px;"></div>
                  </div>
                  <span style="font-size:11px;color:var(--text-muted);"><?= $a['assigned'] ?></span>
                </div>
              </td>
              <td>
                <?php if ((int)$a['needs_attention'] > 0): ?>
                <span class="badge badge-warning" style="font-size:10px;"><?= $a['needs_attention'] ?> fair/poor</span>
                <?php else: ?>
                <span style="font-size:11px;color:var(--success);">✓</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Summary bar -->
      <div class="card-body" style="padding:10px 16px;border-top:1px solid var(--border);">
        <div style="display:flex;gap:16px;font-size:11px;">
          <span style="color:var(--text-muted);">Total: <strong style="color:var(--gold);"><?= $totalAssets ?></strong></span>
          <span style="color:var(--text-muted);">Assigned: <strong style="color:var(--info);"><?= $assetsIssued ?></strong></span>
          <span style="color:var(--text-muted);">Free: <strong style="color:var(--success);"><?= $totalAssets - $assetsIssued ?></strong></span>
          <?php if ($warrantyExpiring): ?>
          <span style="color:var(--warning);">⚠️ <?= $warrantyExpiring ?> warranty expiring</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tickets by Category (last 30 days) -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('bar-chart') ?> Tickets by Category <span class="badge badge-muted" style="font-size:10px;">Last 30 days</span></div>
      </div>
      <div class="card-body">
        <?php if (empty($ticketsByCategory)): ?>
        <div class="empty-state" style="padding:16px 0;"><?= icon('bar-chart',24) ?><p style="font-size:12px;">No tickets in last 30 days</p></div>
        <?php else:
          $maxCat = max(array_column($ticketsByCategory,'cnt'));
        ?>
        <?php foreach ($ticketsByCategory as $cat): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <span style="font-size:11px;text-transform:capitalize;width:70px;flex-shrink:0;color:var(--text-muted);"><?= sanitize($cat['category']) ?></span>
          <div style="flex:1;height:18px;background:var(--bg-elevated);border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:<?= $maxCat>0?round($cat['cnt']/$maxCat*100):0 ?>%;background:var(--gold);border-radius:4px;"></div>
          </div>
          <span style="font-size:12px;font-weight:700;color:var(--gold);flex-shrink:0;width:20px;text-align:right;"><?= $cat['cnt'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MY ASSIGNED TICKETS (IT Staff only)                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php
$myTickets = $db->query(
    "SELECT t.*,
            u.first_name AS sub_first, u.last_name AS sub_last,
            d.name AS dept_name
     FROM it_tickets t
     JOIN users u ON t.submitted_by=u.id
     JOIN departments d ON t.dept_id=d.id
     WHERE t.assigned_to=$uid AND t.status IN ('open','in_progress','pending_user')
     ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.opened_at ASC
     LIMIT 10"
)->fetchAll();

if (!empty($myTickets)): ?>
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('user') ?> My Assigned Tickets
      <span class="badge badge-warning" style="margin-left:6px;"><?= count($myTickets) ?></span>
    </div>
    <a href="it_tickets.php?tab=mine" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ticket</th><th>Subject</th><th>From</th><th>Priority</th><th>Status</th><th>SLA</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($myTickets as $t):
          $breached = $t['sla_deadline'] && strtotime($t['sla_deadline']) < time();
        ?>
        <tr style="<?= $breached ? 'background:rgba(232,85,106,0.04);' : '' ?>">
          <td><span style="font-family:monospace;font-size:11px;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></span></td>
          <td>
            <div style="font-size:12.5px;font-weight:600;color:var(--text-primary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= sanitize($t['subject']) ?>
            </div>
            <span class="td-muted" style="font-size:10px;"><?= ucfirst($t['category']) ?></span>
          </td>
          <td>
            <div style="font-size:12px;"><?= sanitize($t['sub_first'].' '.$t['sub_last']) ?></div>
            <div class="td-muted" style="font-size:10px;"><?= sanitize($t['dept_name']) ?></div>
          </td>
          <td><span class="badge badge-<?= ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'][$t['priority']] ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td><span style="font-size:12px;font-weight:600;color:<?= ticketStatusColor($t['status']) ?>;"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
          <td><?= slaBadge($t) ?></td>
          <td>
            <a href="it_tickets.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a>
            <?php if ($isItAdmin): ?>
            <button class="btn btn-primary btn-sm" onclick="openUpdateModal(<?= $t['id'] ?>, '<?= $t['status'] ?>', <?= (int)$t['assigned_to'] ?>)">Update</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- WARRANTY ALERT (IT Admin only)                              -->
<!-- ═══════════════════════════════════════════════════════════ -->
<?php
$expiringAssets = $db->query(
    "SELECT a.*, d.name AS dept_name, u.first_name, u.last_name
     FROM it_assets a
     LEFT JOIN departments d ON a.department_id=d.id
     LEFT JOIN users u ON a.assigned_to=u.id
     WHERE a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 90 DAY)
     ORDER BY a.warranty_expiry ASC LIMIT 5"
)->fetchAll();

if ($isItAdmin && !empty($expiringAssets)): ?>
<div class="card mb-24" style="border:1px solid rgba(245,166,35,0.3);">
  <div class="card-header">
    <div class="card-title" style="color:var(--warning);"><?= icon('alert-triangle',15) ?> Warranties Expiring Within 90 Days</div>
    <a href="it_assets.php" class="btn btn-outline btn-sm">Manage Assets</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Asset Tag</th><th>Device</th><th>Assigned To</th><th>Expiry</th><th>Days Left</th></tr></thead>
      <tbody>
        <?php foreach ($expiringAssets as $a):
          $daysLeft = (int)floor((strtotime($a['warranty_expiry']) - time()) / 86400);
          $col = $daysLeft <= 14 ? 'var(--danger)' : ($daysLeft <= 30 ? 'var(--warning)' : '#f59e0b');
        ?>
        <tr>
          <td><span style="font-family:monospace;font-size:12px;font-weight:700;color:var(--gold);"><?= sanitize($a['asset_tag']) ?></span></td>
          <td>
            <div class="td-bold"><?= sanitize($a['make'].' '.($a['model']??'')) ?></div>
            <div class="td-muted" style="font-size:10px;text-transform:capitalize;"><?= sanitize($a['asset_type']) ?></div>
          </td>
          <td class="td-muted text-sm"><?= $a['first_name'] ? sanitize($a['first_name'].' '.$a['last_name']) : ($a['dept_name'] ? sanitize($a['dept_name']) : '—') ?></td>
          <td style="font-size:12px;color:<?= $col ?>;"><?= date('M d, Y',strtotime($a['warranty_expiry'])) ?></td>
          <td><span class="badge badge-<?= $daysLeft<=14?'danger':($daysLeft<=30?'warning':'gold') ?>"><?= $daysLeft ?> days</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- MODALS                                                      -->
<!-- ═══════════════════════════════════════════════════════════ -->

<!-- New Ticket Modal -->
<div class="modal-overlay" id="newTicketModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Submit IT Support Ticket</div>
      <button class="modal-close" onclick="closeModal('newTicketModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="submit_ticket">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Subject <span style="color:var(--danger);">*</span></label>
          <input type="text" name="subject" class="form-control" placeholder="Brief description of the issue" required maxlength="255">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
              <option value="hardware">Hardware</option>
              <option value="software">Software</option>
              <option value="network">Network / Internet</option>
              <option value="access">Access / Accounts</option>
              <option value="email">Email</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <option value="low">Low — Not urgent</option>
              <option value="medium" selected>Medium — Normal</option>
              <option value="high">High — Affecting work</option>
              <option value="critical">Critical — System down</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Detailed Description <span style="color:var(--danger);">*</span></label>
          <textarea name="description" class="form-control" rows="4"
            placeholder="Describe the issue in detail. Include: what happened, error messages, steps to reproduce…"
            data-counter="newTicketCounter" required></textarea>
          <div style="text-align:right;margin-top:4px;"><span class="char-counter" id="newTicketCounter">0 chars</span></div>
        </div>
        <div class="alert alert-info" style="font-size:12px;padding:10px 14px;">
          <?= icon('clock',13) ?> SLA: Critical 2h · High 8h · Medium 24h · Low 72h
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('newTicketModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Submit Ticket</button>
      </div>
    </form>
  </div>
</div>

<!-- Update Ticket Modal (IT Admin) -->
<?php if ($isItAdmin): ?>
<div class="modal-overlay" id="updateTicketModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('settings',16) ?> Update Ticket</div>
      <button class="modal-close" onclick="closeModal('updateTicketModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_ticket">
      <input type="hidden" name="ticket_id" id="updateTicketId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">New Status</label>
          <select name="new_status" id="updateTicketStatus" class="form-control">
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="pending_user">Pending User Response</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Assign To</label>
          <select name="assign_to" class="form-control">
            <option value="">— Unassigned —</option>
            <?php foreach ($itStaffList as $s): ?>
            <option value="<?= $s['id'] ?>"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Resolution Notes</label>
          <textarea name="resolution_notes" class="form-control" rows="3"
            placeholder="Describe what was done to resolve this issue…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('updateTicketModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save',14) ?> Save Update</button>
      </div>
    </form>
  </div>
</div>
<script>
function openUpdateModal(ticketId, currentStatus, assignedTo) {
    document.getElementById('updateTicketId').value = ticketId;
    const sel = document.getElementById('updateTicketStatus');
    if (sel) sel.value = currentStatus;
    openModal('updateTicketModal');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
