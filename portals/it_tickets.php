<?php
// ── Bootstrap ─────────────────────────────────────────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isItRole($user) && (int)($user['role_level'] ?? 9) > 2) {
    header('Location: '.SITE_URL.'/portals/'.getRoleDashboard($user['role_slug'], $user)); exit;
}

$isItStaff = isItRole($user);
$isItAdmin = in_array($user['role_slug'], ['it_admin','ceo','principal']) || (($user['role_slug']==='dept_head') && strtoupper($user['dept_code']??'')=='IT');
$uid       = (int)$user['id'];
$db        = getDB();

$pageTitle    = 'IT Help Desk Management';
$pageSubtitle = 'Ticket control centre — '.$user['first_name'];

$slaHours = ['critical'=>4,'high'=>24,'medium'=>48,'low'=>72];

// ── Helper ────────────────────────────────────────────────────
function logTicketAct(PDO $db, int $ticketId, string $action, int $by, string $note=''): void {
    try {
        $db->prepare("INSERT INTO it_ticket_activity_log (ticket_id,action,performed_by,note) VALUES (?,?,?,?)")
           ->execute([$ticketId,$action,$by,$note]);
    } catch (Throwable $e) {}
}

function slaBadge(array $t): string {
    if (in_array($t['status'],['resolved','closed','cancelled'])) return '<span style="color:var(--success);font-size:11px;">✓ Done</span>';
    if (!$t['sla_deadline']) return '<span class="td-muted">—</span>';
    $rem = strtotime($t['sla_deadline']) - time();
    if ($rem < 0) return '<span style="color:var(--danger);font-size:11px;font-weight:700;">⚡ Breached</span>';
    $h = floor($rem/3600);
    $c = $h<2?'var(--danger)':($h<6?'var(--warning)':'var(--text-muted)');
    return "<span style=\"color:$c;font-size:11px;\">{$h}h left</span>";
}

function statusColor(string $s): string {
    return ['open'=>'var(--info)','in_progress'=>'var(--warning)','pending_user'=>'var(--gold)',
            'resolved'=>'var(--success)','closed'=>'var(--text-muted)','cancelled'=>'var(--danger)'][$s] ?? 'var(--text-muted)';
}

// ── POST Actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update ticket (status, assign, resolve, escalate)
    if ($action === 'update_ticket' && $isItStaff) {
        $ticketId   = (int)($_POST['ticket_id'] ?? 0);
        $newStatus  = $_POST['new_status'] ?? '';
        $assignTo   = (int)($_POST['assign_to'] ?? 0) ?: null;
        $resolution = trim($_POST['resolution_notes'] ?? '');
        $allowed    = ['open','in_progress','pending_user','resolved','closed','cancelled'];
        if (!in_array($newStatus,$allowed)) { setFlash('danger','Invalid status.'); header('Location: it_tickets.php'); exit; }

        $ticket = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if (!$ticket) { header('Location: it_tickets.php'); exit; }

        $resolvedAt = in_array($newStatus,['resolved','closed']) ? ',resolved_at=NOW()' : '';
        $closedAt   = $newStatus==='closed' ? ',closed_at=NOW()' : '';
        $db->prepare("UPDATE it_tickets SET status=?,assigned_to=?,resolution_notes=?,updated_at=NOW()$resolvedAt$closedAt WHERE id=?")
           ->execute([$newStatus,$assignTo,$resolution,$ticketId]);

        // Notify if status changed
        if ($ticket['status'] !== $newStatus) {
            sendNotification((int)$ticket['submitted_by'],
                '🎫 Ticket '.$ticket['ticket_no'].' — '.ucfirst(str_replace('_',' ',$newStatus)),
                'Your ticket status was updated'.($resolution?' — '.$resolution:''),
                in_array($newStatus,['resolved','closed'])?'success':'info',
                SITE_URL.'/portals/helpdesk.php?view='.$ticketId);
        }
        // Notify if assigned to someone new
        if ($assignTo && $assignTo != $ticket['assigned_to']) {
            sendNotification($assignTo,
                '🎫 Ticket assigned: '.$ticket['ticket_no'],
                ucfirst($ticket['priority']).' — '.sanitize($ticket['subject']),
                $ticket['priority']==='critical'?'danger':'warning',
                SITE_URL.'/portals/it_tickets.php?view='.$ticketId);
            logTicketAct($db,$ticketId,'ASSIGNED',$uid,'Assigned to user ID '.$assignTo);
        }
        logTicketAct($db,$ticketId,'STATUS_CHANGED',$uid,$ticket['status'].' → '.$newStatus);
        logActivity($uid,'IT_TICKET_UPDATED','Ticket ID '.$ticketId.' → '.$newStatus);
        setFlash('success','✅ Ticket updated.');
        $redir = isset($_POST['view_after'])&&$_POST['view_after']?'it_tickets.php?view='.$ticketId:'it_tickets.php';
        header('Location: '.$redir); exit;
    }

    // Add comment / internal note
    if ($action === 'add_comment' && $isItStaff) {
        $ticketId  = (int)($_POST['ticket_id'] ?? 0);
        $comment   = trim($_POST['comment'] ?? '');
        $isInternal= !empty($_POST['is_internal']) ? 1 : 0;

        if ($ticketId && strlen($comment) >= 2) {
            $ticket = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
            if ($ticket) {
                $db->prepare("INSERT INTO it_ticket_comments (ticket_id,user_id,comment,is_internal) VALUES (?,?,?,?)")
                   ->execute([$ticketId,$uid,$comment,$isInternal]);
                $db->prepare("UPDATE it_tickets SET updated_at=NOW() WHERE id=?")->execute([$ticketId]);
                logTicketAct($db,$ticketId,$isInternal?'INTERNAL_NOTE':'PUBLIC_REPLY',$uid,substr($comment,0,80));

                if (!$isInternal) {
                    sendNotification((int)$ticket['submitted_by'],
                        '💬 IT replied on '.$ticket['ticket_no'],
                        sanitize(substr($comment,0,100)),
                        'info', SITE_URL.'/portals/helpdesk.php?view='.$ticketId);
                }
                setFlash('success','✅ '.($isInternal?'Internal note':'Reply').' posted.');
            }
        }
        header('Location: it_tickets.php?view='.$ticketId); exit;
    }

    // Escalate ticket
    if ($action === 'escalate' && $isItAdmin) {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $note     = trim($_POST['escalation_note'] ?? '');
        $ticket   = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if ($ticket) {
            $db->prepare("UPDATE it_tickets SET escalated=1,escalation_note=?,priority='critical',updated_at=NOW() WHERE id=?")
               ->execute([$note,$ticketId]);
            logTicketAct($db,$ticketId,'ESCALATED',$uid,$note);
            logActivity($uid,'IT_TICKET_ESCALATED','Escalated ticket ID '.$ticketId);
            // Notify all IT admins
            $admins = $db->query("SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='it_admin' AND u.is_active=1")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $aid) {
                sendNotification((int)$aid,'🚨 Ticket Escalated: '.$ticket['ticket_no'],$note?:'Ticket has been escalated to critical','danger',SITE_URL.'/portals/it_tickets.php?view='.$ticketId);
            }
            setFlash('success','✅ Ticket escalated to Critical.');
        }
        header('Location: it_tickets.php?view='.$ticketId); exit;
    }
}

// ── Quick assign to self ──────────────────────────────────────
    if ($action === 'quick_assign' && $isItStaff) {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ticket   = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if ($ticket && in_array($ticket['status'],['open','in_progress','pending_user'])) {
            $db->prepare("UPDATE it_tickets SET assigned_to=?,status='in_progress',updated_at=NOW() WHERE id=?")
               ->execute([$uid,$ticketId]);
            logTicketAct($db,$ticketId,'ASSIGNED',$uid,'Self-assigned and set to In Progress');
            logActivity($uid,'IT_TICKET_ASSIGNED','Claimed ticket ID '.$ticketId);
            // Notify the submitter
            sendNotification((int)$ticket['submitted_by'],
                '🔧 Ticket '.$ticket['ticket_no'].' — Assigned',
                'Your ticket has been picked up and is now in progress.',
                'info', SITE_URL.'/portals/helpdesk.php?view='.$ticketId);
            setFlash('success','✅ Ticket claimed — status set to In Progress.');
        }
        header('Location: it_tickets.php?view='.$ticketId); exit;
    }

// ── View single ticket ────────────────────────────────────────
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $ticket = $db->query(
        "SELECT t.*,
                u.first_name AS sub_first, u.last_name AS sub_last, u.avatar AS sub_avatar,
                d.name AS dept_name,
                a.first_name AS asgn_first, a.last_name AS asgn_last
         FROM it_tickets t
         JOIN users u ON t.submitted_by=u.id
         JOIN departments d ON t.dept_id=d.id
         LEFT JOIN users a ON t.assigned_to=a.id
         WHERE t.id=$viewId"
    )->fetch();

    if (!$ticket) { setFlash('danger','Ticket not found.'); header('Location: it_tickets.php'); exit; }

    $comments = $db->query(
        "SELECT c.*, u.first_name, u.last_name, u.avatar, r.slug AS role_slug
         FROM it_ticket_comments c
         JOIN users u ON c.user_id=u.id
         JOIN roles r ON u.role_id=r.id
         WHERE c.ticket_id=$viewId ORDER BY c.created_at ASC"
    )->fetchAll();

    $actLog = [];
    try {
        $actLog = $db->query("SELECT l.*,u.first_name,u.last_name FROM it_ticket_activity_log l LEFT JOIN users u ON l.performed_by=u.id WHERE l.ticket_id=$viewId ORDER BY l.created_at ASC")->fetchAll();
    } catch (Throwable $e) {}

    $itUsers = $db->query(
        "SELECT DISTINCT u.id,u.first_name,u.last_name FROM users u
         JOIN roles r ON u.role_id=r.id
         LEFT JOIN departments d ON u.department_id=d.id
         WHERE (r.slug IN ('it_admin','it_officer') OR UPPER(d.code)='IT')
         AND u.is_active=1 ORDER BY u.first_name"
    )->fetchAll();
    $breached = $ticket['sla_deadline'] && strtotime($ticket['sla_deadline'])<time() && !in_array($ticket['status'],['resolved','closed','cancelled']);
    $priColors= ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'];

    require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:14px;">
  <a href="it_tickets.php" style="font-size:13px;color:var(--text-muted);text-decoration:none;"><?= icon('arrow-left',13) ?> All Tickets</a>
</div>

<div class="grid-2">
  <!-- Ticket detail (left, full width on small) -->
  <div style="grid-column:1/3;">
    <div class="card mb-24">
      <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div style="flex:1;min-width:0;">
          <div style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:6px;"><?= sanitize($ticket['subject']) ?></div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <span style="font-family:monospace;font-size:12px;color:var(--gold);"><?= sanitize($ticket['ticket_no']) ?></span>
            <span class="badge badge-<?= $priColors[$ticket['priority']] ?>"><?= ucfirst($ticket['priority']) ?></span>
            <span style="font-size:12px;font-weight:700;color:<?= statusColor($ticket['status']) ?>;"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span>
            <?php if ($ticket['escalated']??0): ?><span class="badge badge-danger">⬆ Escalated</span><?php endif; ?>
            <?php if ($breached): ?><span class="badge badge-danger">⚡ SLA Breached</span><?php endif; ?>
            <span class="badge badge-muted" style="font-size:9px;"><?= ucfirst($ticket['category']) ?></span>
          </div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
            Submitted by <strong><?= sanitize($ticket['sub_first'].' '.$ticket['sub_last']) ?></strong>
            · <?= sanitize($ticket['dept_name']) ?> · <?= timeAgo($ticket['opened_at']) ?>
            <?php if ($ticket['asgn_first']): ?> · Assigned: <strong><?= sanitize($ticket['asgn_first'].' '.$ticket['asgn_last']) ?></strong><?php endif; ?>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap;">
          <?php if (!in_array($ticket['status'],['resolved','closed','cancelled'])): ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('updateModal')"><?= icon('settings',13) ?> Update</button>
          <?php if ($isItAdmin && !($ticket['escalated']??0)): ?>
          <button class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;" onclick="openModal('escalateModal')"><?= icon('alert-triangle',13) ?> Escalate</button>
          <?php endif; ?>
          <?php endif; ?>
          <a href="helpdesk.php?view=<?= $viewId ?>" class="btn btn-outline btn-sm" target="_blank"><?= icon('external-link',12) ?> User View</a>
        </div>
      </div>

      <!-- SLA bar -->
      <?php if ($ticket['sla_deadline'] && !in_array($ticket['status'],['resolved','closed','cancelled'])): ?>
      <?php
        $totalSla = $slaHours[$ticket['priority']] * 3600;
        $elapsed  = time() - strtotime($ticket['opened_at']);
        $pct      = min(100, round($elapsed / $totalSla * 100));
        $rem      = max(0, strtotime($ticket['sla_deadline']) - time());
        $barCol   = $breached?'#e8556a':($pct>80?'#f5a623':'#2dd4a0');
      ?>
      <div style="padding:10px 20px;border-top:1px solid var(--border);background:var(--bg-elevated);">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:5px;">
          <span>SLA Progress (<?= $slaHours[$ticket['priority']] ?>h SLA)</span>
          <span style="color:<?= $barCol ?>;font-weight:700;"><?= $breached?'BREACHED':(floor($rem/3600).'h '.floor(($rem%3600)/60).'m left') ?></span>
        </div>
        <div style="height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $barCol ?>;border-radius:3px;transition:.3s;"></div>
        </div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">Deadline: <?= date('M d, Y H:i',strtotime($ticket['sla_deadline'])) ?></div>
      </div>
      <?php endif; ?>

      <div class="card-body">
        <!-- Description -->
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:18px;font-size:13.5px;line-height:1.8;white-space:pre-wrap;"><?= sanitize($ticket['description']) ?></div>
        <?php if ($ticket['attachment_path']): ?><div style="margin-bottom:14px;"><a href="<?= SITE_URL.'/'.$ticket['attachment_path'] ?>" target="_blank" class="btn btn-outline btn-sm"><?= icon('paperclip',12) ?> View Attachment</a></div><?php endif; ?>

        <?php if ($ticket['resolution_notes']): ?>
        <div style="background:rgba(45,212,160,0.06);border:1px solid rgba(45,212,160,0.2);border-radius:8px;padding:14px;margin-bottom:18px;">
          <div style="font-size:11px;font-weight:700;color:var(--success);margin-bottom:6px;">✅ RESOLUTION NOTES</div>
          <div style="font-size:13px;line-height:1.7;"><?= sanitize($ticket['resolution_notes']) ?></div>
        </div>
        <?php endif; ?>

        <?php if (($ticket['escalation_note']??'')): ?>
        <div style="background:rgba(232,85,106,0.06);border:1px solid rgba(232,85,106,0.2);border-radius:8px;padding:14px;margin-bottom:18px;">
          <div style="font-size:11px;font-weight:700;color:var(--danger);margin-bottom:6px;">⬆ ESCALATION NOTE</div>
          <div style="font-size:13px;"><?= sanitize($ticket['escalation_note']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Comments -->
        <div style="border-top:1px solid var(--border);padding-top:16px;">
          <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:14px;"><?= icon('message-circle',14) ?> <?= count($comments) ?> Comment<?= count($comments)!=1?'s':'' ?></div>
          <?php foreach ($comments as $c): ?>
          <div style="display:flex;gap:10px;margin-bottom:14px;<?= $c['is_internal']?'background:rgba(245,166,35,0.04);border:1px solid rgba(245,166,35,0.1);border-radius:8px;padding:8px;':'' ?>">
            <?php if (!empty($c['avatar'])): ?><img src="<?= sanitize(SITE_URL.'/'.ltrim($c['avatar'],'/')) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt=""><?php else: ?><div class="avatar avatar-sm" style="flex-shrink:0;"><?= getInitials($c['first_name'],$c['last_name']) ?></div><?php endif; ?>
            <div style="flex:1;">
              <div style="font-size:12px;margin-bottom:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                <strong><?= sanitize($c['first_name'].' '.$c['last_name']) ?></strong>
                <?php if (in_array($c['role_slug'],['it_admin','it_officer'])): ?><span class="badge badge-info" style="font-size:9px;">IT</span><?php endif; ?>
                <?php if ($c['is_internal']): ?><span class="badge badge-warning" style="font-size:9px;">Internal Note</span><?php endif; ?>
                <span style="color:var(--text-muted);"><?= timeAgo($c['created_at']) ?></span>
              </div>
              <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;line-height:1.6;"><?= sanitize($c['comment']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if (!in_array($ticket['status'],['closed','cancelled'])): ?>
          <form method="POST" style="border-top:1px solid var(--border);padding-top:14px;">
            <input type="hidden" name="action" value="add_comment">
            <input type="hidden" name="ticket_id" value="<?= $viewId ?>">
            <div class="form-group"><textarea name="comment" class="form-control" rows="3" placeholder="Write a reply or internal note…" required></textarea></div>
            <div style="display:flex;gap:10px;align-items:center;">
              <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);cursor:pointer;">
                <input type="checkbox" name="is_internal" style="width:14px;height:14px;"> Internal note (staff only)
              </label>
              <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;"><?= icon('send',13) ?> Post</button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Activity Log -->
  <?php if (!empty($actLog)): ?>
  <div style="grid-column:1/3;">
    <div class="card">
      <div class="card-header"><div class="card-title"><?= icon('activity') ?> Activity Timeline</div></div>
      <div class="card-body" style="padding:12px 20px;">
        <?php foreach ($actLog as $al): ?>
        <div style="display:flex;gap:10px;margin-bottom:8px;font-size:12px;align-items:flex-start;">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--gold);flex-shrink:0;margin-top:3px;"></div>
          <div>
            <span style="color:var(--text-secondary);font-weight:600;"><?= sanitize(str_replace('_',' ',$al['action'])) ?></span>
            <?php if ($al['first_name']): ?><span style="color:var(--text-muted);"> by <?= sanitize($al['first_name'].' '.$al['last_name']) ?></span><?php endif; ?>
            <?php if ($al['note']): ?><span style="color:var(--text-muted);"> — <?= sanitize(substr($al['note'],0,80)) ?></span><?php endif; ?>
            <span style="color:var(--text-muted);margin-left:6px;"><?= timeAgo($al['created_at']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Update Modal -->
<?php if (!in_array($ticket['status'],['closed','cancelled'])): ?>
<div class="modal-overlay" id="updateModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header"><div class="modal-title"><?= icon('settings') ?> Update Ticket</div><button class="modal-close" onclick="closeModal('updateModal')"><?= icon('x',18) ?></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="update_ticket">
      <input type="hidden" name="ticket_id" value="<?= $viewId ?>">
      <input type="hidden" name="view_after" value="1">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="new_status" class="form-control">
              <?php foreach (['open','in_progress','pending_user','resolved','closed','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $ticket['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign To</label>
            <select name="assign_to" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach ($itUsers as $u): ?>
              <option value="<?= $u['id'] ?>" <?= $ticket['assigned_to']==$u['id']?'selected':'' ?>><?= sanitize($u['first_name'].' '.$u['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Resolution Notes</label>
          <textarea name="resolution_notes" class="form-control" rows="4" placeholder="Describe the fix or next steps…"><?= sanitize($ticket['resolution_notes']??'') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('updateModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('save',13) ?> Save Update</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Escalate Modal -->
<?php if ($isItAdmin && !($ticket['escalated']??0)): ?>
<div class="modal-overlay" id="escalateModal">
  <div class="modal" style="max-width:440px;">
    <div class="modal-header"><div class="modal-title" style="color:var(--danger);"><?= icon('alert-triangle') ?> Escalate Ticket</div><button class="modal-close" onclick="closeModal('escalateModal')"><?= icon('x',18) ?></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="escalate">
      <input type="hidden" name="ticket_id" value="<?= $viewId ?>">
      <div class="modal-body">
        <div class="alert alert-danger" style="font-size:12px;margin-bottom:16px;">Escalating will set this ticket to Critical priority and notify all IT admins.</div>
        <div class="form-group">
          <label class="form-label">Escalation Note</label>
          <textarea name="escalation_note" class="form-control" rows="3" placeholder="Why is this being escalated?" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('escalateModal')">Cancel</button>
        <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;"><?= icon('alert-triangle',13) ?> Escalate Now</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Ticket list + analytics ───────────────────────────────────
$tab            = $_GET['tab'] ?? 'all';
$filterStatus   = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterDept     = (int)($_GET['dept'] ?? 0);
$filterAssign   = (int)($_GET['assign'] ?? 0);
$search         = trim($_GET['search'] ?? '');
$page           = max(1,(int)($_GET['page']??1));
$perPage        = 25;
$offset         = ($page-1)*$perPage;

$where  = ['1=1'];
$params = [];
if ($tab==='mine')      { $where[]="t.assigned_to=$uid"; }
elseif ($tab==='open')  { $where[]="t.status IN ('open','in_progress','pending_user')"; }
elseif ($tab==='breach'){ $where[]="t.sla_deadline<NOW() AND t.status IN ('open','in_progress','pending_user')"; }
if ($filterStatus)   { $where[]="t.status=?";         $params[]=$filterStatus; }
if ($filterPriority) { $where[]="t.priority=?";       $params[]=$filterPriority; }
if ($filterDept)     { $where[]="t.dept_id=?";        $params[]=$filterDept; }
if ($filterAssign)   { $where[]="t.assigned_to=?";    $params[]=$filterAssign; }
if ($search)         { $where[]="(t.subject LIKE ? OR t.ticket_no LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s,$s,$s]); }
$whereSql = 'WHERE '.implode(' AND ',$where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM it_tickets t JOIN users u ON t.submitted_by=u.id $whereSql");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$totalPages = max(1,(int)ceil($total/$perPage));

$stmt = $db->prepare(
    "SELECT t.*,
            u.first_name AS sub_first, u.last_name AS sub_last,
            d.name AS dept_name,
            a.first_name AS asgn_first, a.last_name AS asgn_last,
            (SELECT COUNT(*) FROM it_ticket_comments WHERE ticket_id=t.id) AS comments
     FROM it_tickets t
     JOIN users u ON t.submitted_by=u.id
     JOIN departments d ON t.dept_id=d.id
     LEFT JOIN users a ON t.assigned_to=a.id
     $whereSql
     ORDER BY FIELD(t.priority,'critical','high','medium','low'),
              FIELD(t.status,'open','in_progress','pending_user','pending_user','resolved','closed','cancelled'),
              t.opened_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// ── Dashboard metrics ─────────────────────────────────────────
$openCount     = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE status IN ('open','in_progress','pending_user')")->fetchColumn();
$breachedCount = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE sla_deadline<NOW() AND status IN ('open','in_progress','pending_user')")->fetchColumn();
$criticalOpen  = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE priority='critical' AND status IN ('open','in_progress')")->fetchColumn();
$resolvedToday = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE status='resolved' AND DATE(resolved_at)=CURDATE()")->fetchColumn();
$myAssigned    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE assigned_to=$uid AND status IN ('open','in_progress','pending_user')")->fetchColumn();

// Avg resolution time (hours)
$avgRes = (float)$db->query("SELECT AVG(TIMESTAMPDIFF(HOUR,opened_at,resolved_at)) FROM it_tickets WHERE status IN ('resolved','closed') AND resolved_at IS NOT NULL AND resolved_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();

// SLA compliance last 30 days
$totalLast30    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE opened_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
$onTimeLast30   = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE opened_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) AND (sla_deadline IS NULL OR resolved_at<=sla_deadline OR status NOT IN ('resolved','closed'))")->fetchColumn();
$slaCompliance  = $totalLast30>0 ? round($onTimeLast30/$totalLast30*100) : 100;

// Analytics data
$byDept = $db->query(
    "SELECT d.name AS dept, COUNT(t.id) AS total,
            SUM(t.status IN ('resolved','closed')) AS resolved,
            SUM(t.sla_deadline<NOW() AND t.status IN ('open','in_progress','pending_user')) AS breached
     FROM it_tickets t JOIN departments d ON t.dept_id=d.id
     WHERE t.opened_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
     GROUP BY d.id ORDER BY total DESC LIMIT 8"
)->fetchAll();

$byCategory = $db->query(
    "SELECT category, COUNT(*) AS cnt FROM it_tickets
     WHERE opened_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
     GROUP BY category ORDER BY cnt DESC"
)->fetchAll();

// Monthly trend (last 6 months)
$monthlyTrend = [];
for ($i=5;$i>=0;$i--) {
    $m  = date('Y-m',strtotime("-$i months"));
    $ms = $m.'-01'; $me = date('Y-m-t',strtotime($ms));
    $monthlyTrend[] = [
        'label'    => date('M',strtotime($ms)),
        'total'    => (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE opened_at BETWEEN '$ms' AND '$me 23:59:59'")->fetchColumn(),
        'resolved' => (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE resolved_at BETWEEN '$ms' AND '$me 23:59:59'")->fetchColumn(),
    ];
}

// Technician performance
$techPerf = $db->query(
    "SELECT u.first_name, u.last_name,
            COUNT(t.id) AS assigned,
            SUM(t.status IN ('resolved','closed')) AS resolved,
            AVG(CASE WHEN t.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR,t.opened_at,t.resolved_at) END) AS avg_hours,
            AVG(t.satisfaction_rating) AS avg_rating,
            SUM(t.sla_deadline<NOW() AND t.status IN ('open','in_progress','pending_user')) AS breached
     FROM users u
     JOIN roles r ON u.role_id=r.id
     LEFT JOIN it_tickets t ON t.assigned_to=u.id AND t.opened_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
     WHERE r.slug IN ('it_admin','it_officer') AND u.is_active=1
     GROUP BY u.id ORDER BY resolved DESC"
)->fetchAll();

$allDepts  = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();
$itUsers   = $db->query(
    "SELECT DISTINCT u.id,u.first_name,u.last_name FROM users u
     JOIN roles r ON u.role_id=r.id
     LEFT JOIN departments d ON u.department_id=d.id
     WHERE (r.slug IN ('it_admin','it_officer') OR UPPER(d.code)='IT')
     AND u.is_active=1 ORDER BY u.first_name"
)->fetchAll();
$priColors = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'];

$pageTitle    = 'IT Help Desk Management';
$pageSubtitle = 'Centralized ticket control';

// ── Unassigned / new tickets for incoming panel ───────────────
$unassigned = $db->query(
    "SELECT t.*,
            u.first_name AS sub_first, u.last_name AS sub_last, u.avatar AS sub_avatar,
            d.name AS dept_name
     FROM it_tickets t
     JOIN users u ON t.submitted_by=u.id
     JOIN departments d ON t.dept_id=d.id
     WHERE t.assigned_to IS NULL AND t.status='open'
     ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.opened_at ASC
     LIMIT 20"
)->fetchAll();
$unassignedCount = count($unassigned);

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($unassignedCount > 0): ?>
<!-- ═══════════════════════════════════════ INCOMING TICKETS PANEL ═ -->
<div class="card mb-24" style="border:1px solid rgba(201,168,76,0.5);background:rgba(201,168,76,0.04);">
  <div class="card-header" style="background:rgba(201,168,76,0.08);border-bottom:1px solid rgba(201,168,76,0.2);">
    <div class="card-title" style="color:var(--gold);"><?= icon('inbox',16) ?>
      🔔 Incoming — Unassigned Tickets
      <span class="badge badge-warning" style="margin-left:8px;"><?= $unassignedCount ?> new</span>
    </div>
    <a href="it_tickets.php?status=open" class="btn btn-outline btn-sm">View All Open</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Ticket #</th>
          <th>Subject</th>
          <th>From</th>
          <th>Priority</th>
          <th>SLA</th>
          <th>Submitted</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($unassigned as $t):
          $slaRem    = $t['sla_deadline'] ? strtotime($t['sla_deadline']) - time() : null;
          $slaBreached = $slaRem !== null && $slaRem < 0;
          $slaColor  = $slaBreached ? 'var(--danger)' : ($slaRem !== null && $slaRem < 7200 ? 'var(--warning)' : 'var(--text-muted)');
          $priColor  = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'][$t['priority']] ?? 'muted';
        ?>
        <tr style="<?= $slaBreached?'background:rgba(232,85,106,0.05);':'' ?><?= $t['priority']==='critical'?'border-left:3px solid var(--danger);':'' ?>">
          <td>
            <div style="font-family:monospace;font-size:12px;font-weight:700;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></div>
            <div style="font-size:10px;color:var(--text-muted);text-transform:capitalize;"><?= sanitize($t['category']) ?></div>
          </td>
          <td>
            <a href="it_tickets.php?view=<?= $t['id'] ?>" style="font-size:13px;font-weight:600;color:var(--text-primary);text-decoration:none;display:block;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($t['subject']) ?>">
              <?= sanitize($t['subject']) ?>
            </a>
            <div style="font-size:10px;color:var(--text-muted);"><?= sanitize($t['dept_name']) ?></div>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:7px;">
              <?php if (!empty($t['sub_avatar'])): ?>
              <img src="<?= sanitize(SITE_URL.'/'.ltrim($t['sub_avatar'],'/')) ?>" style="width:26px;height:26px;border-radius:50%;object-fit:cover;" alt="">
              <?php else: ?>
              <div class="avatar avatar-sm" style="width:26px;height:26px;font-size:9px;"><?= getInitials($t['sub_first'],$t['sub_last']) ?></div>
              <?php endif; ?>
              <div>
                <div style="font-size:12px;font-weight:600;"><?= sanitize($t['sub_first'].' '.$t['sub_last']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-<?= $priColor ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td>
            <span style="font-size:12px;font-weight:700;color:<?= $slaColor ?>;">
              <?php if ($slaBreached): ?>
                ⚡ Breached
              <?php elseif ($slaRem !== null): ?>
                <?= floor($slaRem/3600) ?>h <?= floor(($slaRem%3600)/60) ?>m left
              <?php else: ?>—<?php endif; ?>
            </span>
          </td>
          <td style="font-size:11px;color:var(--text-muted);"><?= timeAgo($t['opened_at']) ?></td>
          <td>
            <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
              <!-- Assign to me -->
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="quick_assign">
                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm" title="Assign to me and set In Progress">
                  <?= icon('user-check',12) ?> Claim
                </button>
              </form>
              <!-- View / Respond -->
              <a href="it_tickets.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm">
                <?= icon('eye',12) ?> View
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="stat-grid">
  <div class="stat-card <?= $openCount>10?'orange':($openCount>0?'gold':'green') ?>">
    <div class="stat-top"><div><div class="stat-number"><?= $openCount ?></div><div class="stat-label">Open Tickets</div></div><div class="stat-icon"><?= icon('message-square',20) ?></div></div>
    <div class="stat-delta"><?= $myAssigned ?> assigned to me</div>
  </div>
  <div class="stat-card <?= $breachedCount>0?'':'green' ?>" style="<?= $breachedCount>0?'border-top-color:var(--danger);':'' ?>">
    <div class="stat-top"><div><div class="stat-number" style="color:<?= $breachedCount>0?'var(--danger)':'var(--success)' ?>;"><?= $breachedCount ?></div><div class="stat-label">SLA Breaches</div></div><div class="stat-icon"><?= icon('alert-triangle',20) ?></div></div>
    <div class="stat-delta <?= $breachedCount>0?'down':'up' ?>"><?= $breachedCount>0?'Require urgent action':'All within SLA!' ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top"><div><div class="stat-number"><?= $slaCompliance ?>%</div><div class="stat-label">SLA Compliance</div></div><div class="stat-icon"><?= icon('award',20) ?></div></div>
    <div class="stat-delta <?= $slaCompliance>=80?'up':'down' ?>">Last 30 days</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top"><div><div class="stat-number"><?= $avgRes ? round($avgRes,1).'h' : '—' ?></div><div class="stat-label">Avg Resolution</div></div><div class="stat-icon"><?= icon('clock',20) ?></div></div>
    <div class="stat-delta"><?= $resolvedToday ?> resolved today</div>
  </div>
</div>

<?php if ($breachedCount > 0): ?>
<div class="card mb-24" style="border:1px solid rgba(232,85,106,0.4);background:rgba(232,85,106,0.04);">
  <div class="card-body" style="padding:12px 20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:20px;">⚡</span>
    <div style="flex:1;font-size:13px;font-weight:700;color:var(--danger);"><?= $breachedCount ?> ticket<?= $breachedCount>1?'s':'' ?> have breached SLA deadline</div>
    <a href="it_tickets.php?tab=breach" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;">View Breached</a>
  </div>
</div>
<?php endif; ?>

<!-- Analytics row -->
<div class="grid-2 mb-24">
  <!-- Monthly trend -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('trending-up') ?> Monthly Ticket Trend</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="monthlyChart"></canvas></div></div>
  </div>
  <!-- By category -->
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('pie-chart') ?> Tickets by Category (30d)</div></div>
    <div class="card-body"><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></div>
  </div>
</div>

<!-- Dept breakdown + technician performance -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('layers') ?> By Department (30d)</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Department</th><th>Total</th><th>Resolved</th><th>Breached</th></tr></thead>
        <tbody>
          <?php foreach ($byDept as $d): ?>
          <tr>
            <td class="td-bold" style="font-size:12px;"><?= sanitize($d['dept']) ?></td>
            <td><span class="badge badge-info"><?= (int)$d['total'] ?></span></td>
            <td><span class="badge badge-success"><?= (int)$d['resolved'] ?></span></td>
            <td><?= (int)$d['breached']>0?'<span class="badge badge-danger">'.(int)$d['breached'].'</span>':'<span style="color:var(--success);font-size:11px;">✓</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($byDept)): ?><tr><td colspan="4"><div class="empty-state" style="padding:20px;"><?= icon('inbox',28) ?><p>No data</p></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= icon('user') ?> Technician Performance (30d)</div></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Technician</th><th>Assigned</th><th>Resolved</th><th>Avg Time</th><th>Rating</th></tr></thead>
        <tbody>
          <?php foreach ($techPerf as $t): ?>
          <tr>
            <td>
              <div class="avatar avatar-sm" style="width:24px;height:24px;font-size:8px;display:inline-flex;margin-right:6px;"><?= getInitials($t['first_name'],$t['last_name']) ?></div>
              <span class="td-bold" style="font-size:12px;"><?= sanitize($t['first_name'].' '.$t['last_name']) ?></span>
            </td>
            <td><?= (int)$t['assigned'] ?></td>
            <td><span class="badge badge-success"><?= (int)$t['resolved'] ?></span></td>
            <td class="td-muted" style="font-size:11px;"><?= $t['avg_hours']?round($t['avg_hours'],1).'h':'—' ?></td>
            <td style="font-size:12px;color:var(--gold);"><?= $t['avg_rating']?str_repeat('★',round($t['avg_rating'])).' '.round($t['avg_rating'],1):'—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tab bar + filters -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
  <?php foreach (['all'=>'All','mine'=>'Assigned to Me','open'=>'Open','breach'=>'SLA Breached'] as $t=>$l): ?>
  <a href="?tab=<?= $t ?>" class="btn btn-sm <?= $tab===$t?'btn-primary':'btn-outline' ?>"><?= $l ?><?= $t==='breach'&&$breachedCount>0?' ('.$breachedCount.')':'' ?></a>
  <?php endforeach; ?>
  <a href="helpdesk.php" class="btn btn-outline btn-sm" style="margin-left:auto;"><?= icon('external-link',12) ?> User Portal</a>
</div>

<div class="card mb-24">
  <div class="card-header"><div class="card-title"><?= icon('filter') ?> Filters</div></div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="tab" value="<?= $tab ?>">
      <div><label class="form-label" style="font-size:10px;">Status</label>
        <select name="status" class="form-control" style="width:140px;">
          <option value="">All</option>
          <?php foreach (['open','in_progress','pending_user','resolved','closed','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?></select></div>
      <div><label class="form-label" style="font-size:10px;">Priority</label>
        <select name="priority" class="form-control" style="width:120px;">
          <option value="">All</option>
          <?php foreach (['critical','high','medium','low'] as $p): ?>
          <option value="<?= $p ?>" <?= $filterPriority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?></select></div>
      <div><label class="form-label" style="font-size:10px;">Department</label>
        <select name="dept" class="form-control" style="width:150px;">
          <option value="0">All Depts</option>
          <?php foreach ($allDepts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDept===$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?></select></div>
      <div><label class="form-label" style="font-size:10px;">Assignee</label>
        <select name="assign" class="form-control" style="width:150px;">
          <option value="0">All</option>
          <?php foreach ($itUsers as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $filterAssign===$u['id']?'selected':'' ?>><?= sanitize($u['first_name'].' '.$u['last_name']) ?></option>
          <?php endforeach; ?></select></div>
      <div style="flex:1;min-width:150px;"><label class="form-label" style="font-size:10px;">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Ticket #, subject, name…" value="<?= sanitize($search) ?>"></div>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('search',12) ?> Filter</button>
      <a href="it_tickets.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<!-- Tickets table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('message-square') ?> Tickets <span class="badge badge-muted" style="margin-left:6px;"><?= $total ?></span></div>
  </div>
  <?php if (empty($tickets)): ?>
  <div class="card-body"><div class="empty-state" style="padding:48px;"><?= icon('check-circle',44) ?><h3 style="color:var(--success);">No tickets match filters</h3></div></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ticket #</th><th>Subject</th><th>Priority</th><th>Status</th><th>Department</th><th>Assigned</th><th>SLA</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($tickets as $t):
          $breached = $t['sla_deadline'] && strtotime($t['sla_deadline'])<time() && !in_array($t['status'],['resolved','closed','cancelled']);
        ?>
        <tr style="<?= $breached?'background:rgba(232,85,106,0.03);':'' ?><?= ($t['escalated']??0)?'border-left:3px solid var(--danger);':'' ?>">
          <td>
            <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></div>
            <div class="td-muted" style="font-size:10px;"><?= timeAgo($t['opened_at']) ?></div>
          </td>
          <td>
            <div style="font-size:12.5px;font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize($t['subject']) ?></div>
            <div style="font-size:10px;color:var(--text-muted);"><?= ucfirst($t['category']) ?><?= ($t['escalated']??0)?' · <span style="color:var(--danger);">⬆ Escalated</span>':'' ?><?= $t['comments']>0?' · '.$t['comments'].'💬':'' ?></div>
          </td>
          <td><span class="badge badge-<?= $priColors[$t['priority']]??'muted' ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td><span style="font-size:12px;font-weight:700;color:<?= statusColor($t['status']) ?>;"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
          <td class="td-muted" style="font-size:11px;"><?= sanitize($t['dept_name']) ?></td>
          <td class="td-muted" style="font-size:11px;"><?= $t['asgn_first']?sanitize($t['asgn_first'][0].'.'.$t['asgn_last']):'<span style="color:var(--warning);">Unassigned</span>' ?></td>
          <td><?= slaBadge($t) ?></td>
          <td class="td-muted" style="font-size:10px;"><?= $t['updated_at']?timeAgo($t['updated_at']):timeAgo($t['opened_at']) ?></td>
          <td>
            <a href="it_tickets.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages>1): ?>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--border);">
    <span style="font-size:12px;color:var(--text-muted);">Page <?= $page ?>/<?= $totalPages ?> · <?= $total ?> tickets</span>
    <div style="display:flex;gap:6px;">
      <?php if ($page>1): ?><a href="?tab=<?= $tab ?>&page=<?= $page-1 ?>&status=<?= $filterStatus ?>&priority=<?= $filterPriority ?>&dept=<?= $filterDept ?>&assign=<?= $filterAssign ?>" class="btn btn-outline btn-sm">← Prev</a><?php endif; ?>
      <?php if ($page<$totalPages): ?><a href="?tab=<?= $tab ?>&page=<?= $page+1 ?>&status=<?= $filterStatus ?>&priority=<?= $filterPriority ?>&dept=<?= $filterDept ?>&assign=<?= $filterAssign ?>" class="btn btn-outline btn-sm">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const gc='rgba(255,255,255,0.05)',tc='#9ba3b8';
const bs={x:{ticks:{color:tc,font:{size:11}},grid:{color:gc}},y:{ticks:{color:tc,font:{size:11}},grid:{color:gc},beginAtZero:true}};

new Chart(document.getElementById('monthlyChart'),{type:'bar',data:{
  labels:<?= json_encode(array_column($monthlyTrend,'label')) ?>,
  datasets:[
    {label:'Created',  data:<?= json_encode(array_column($monthlyTrend,'total')) ?>,    backgroundColor:'rgba(201,168,76,0.75)',  borderRadius:5},
    {label:'Resolved', data:<?= json_encode(array_column($monthlyTrend,'resolved')) ?>, backgroundColor:'rgba(45,212,160,0.75)', borderRadius:5},
  ]
},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:tc,font:{size:11}}}},scales:bs}});

new Chart(document.getElementById('categoryChart'),{type:'doughnut',data:{
  labels:<?= json_encode(array_column($byCategory,'category')) ?>,
  datasets:[{data:<?= json_encode(array_column($byCategory,'cnt')) ?>,backgroundColor:['#c9a84c','#2dd4a0','#e8556a','#63b3ed','#f5a623','#9f7aea','#48bb78'],borderColor:'#1f2435',borderWidth:3}]
},options:{responsive:true,maintainAspectRatio:false,cutout:'55%',plugins:{legend:{position:'right',labels:{color:tc,font:{size:11},padding:12}}}}});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
