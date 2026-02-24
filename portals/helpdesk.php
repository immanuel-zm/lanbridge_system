<?php
// ── Bootstrap ─────────────────────────────────────────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$user     = currentUser();
$uid      = (int)$user['id'];
$isItStaff= isItRole($user);
$isItAdmin= in_array($user['role_slug'], ['it_admin','ceo','principal']);

// SLA hours
$slaHours = ['critical'=>4,'high'=>24,'medium'=>48,'low'=>72];

// ── Helper: log ticket activity ───────────────────────────────
function logTicketActivity(PDO $db, int $ticketId, string $action, int $userId, string $note=''): void {
    try {
        $db->prepare("INSERT INTO it_ticket_activity_log (ticket_id,action,performed_by,note) VALUES (?,?,?,?)")
           ->execute([$ticketId,$action,$userId,$note]);
    } catch (Throwable $e) { /* table may not exist yet on old installs */ }
}

// ── Handle file upload ────────────────────────────────────────
function handleUpload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) return null;
    $f    = $_FILES[$field];
    $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','gif','pdf'];
    if (!in_array($ext,$allowed) || $f['error'] !== UPLOAD_ERR_OK) return null;
    if ($f['size'] > 5*1024*1024) return null; // 5MB max
    $dir  = __DIR__.'/../assets/uploads/helpdesk/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('att_',true).'.'.$ext;
    move_uploaded_file($f['tmp_name'], $dir.$name);
    return 'assets/uploads/helpdesk/'.$name;
}

// ── POST Actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Submit new ticket ─────────────────────────────────────
    if ($action === 'submit_ticket') {
        $subject  = trim($_POST['subject'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'other';
        $priority = $_POST['priority'] ?? 'medium';
        $cats     = ['hardware','software','network','access','email','system','other'];
        $pris     = ['low','medium','high','critical'];
        if (!in_array($category,$cats)) $category = 'other';
        if (!in_array($priority,$pris)) $priority  = 'medium';

        if ($subject && strlen($desc) >= 10) {
            // Generate ticket number: HD-YYYY-XXXX
            $year  = date('Y');
            $seq   = $db->query("SELECT COUNT(*)+1 FROM it_tickets WHERE YEAR(opened_at)=$year")->fetchColumn();
            $ticketNo = 'HD-'.$year.'-'.str_pad((int)$seq,4,'0',STR_PAD_LEFT);
            $slaDL = date('Y-m-d H:i:s', strtotime('+'.$slaHours[$priority].' hours'));
            $deptId= (int)($user['department_id'] ?? 0);
            $attach= handleUpload('attachment');

            $db->prepare(
                "INSERT INTO it_tickets (ticket_no,submitted_by,dept_id,category,priority,subject,description,attachment_path,sla_deadline,status)
                 VALUES (?,?,?,?,?,?,?,?,?,'open')"
            )->execute([$ticketNo,$uid,$deptId,$category,$priority,$subject,$desc,$attach,$slaDL]);
            $newId = (int)$db->lastInsertId();

            logTicketActivity($db,$newId,'TICKET_CREATED',$uid,'Ticket submitted: '.$subject);
            logActivity($uid,'HELPDESK_TICKET_CREATED','Ticket '.$ticketNo.': '.$subject);

            // Notify all IT staff
            $itStaff = $db->query(
                "SELECT DISTINCT u.id FROM users u
                 JOIN roles r ON u.role_id=r.id
                 LEFT JOIN departments d ON u.department_id=d.id
                 WHERE (r.slug IN ('it_admin','it_officer') OR UPPER(d.code)='IT')
                 AND u.is_active=1 AND u.id!=$uid"
            )->fetchAll(PDO::FETCH_COLUMN);
            foreach ($itStaff as $itId) {
                sendNotification((int)$itId,
                    '🎫 '.strtoupper($priority).' — '.$subject,
                    'From: '.$user['first_name'].' '.$user['last_name'].' · SLA: '.date('M d, H:i',strtotime($slaDL)),
                    $priority==='critical'?'danger':($priority==='high'?'warning':'info'),
                    SITE_URL.'/portals/it_tickets.php?view='.$newId
                );
            }
            setFlash('success','✅ Ticket <strong>'.$ticketNo.'</strong> submitted. IT team has been notified.');
        } else {
            setFlash('danger','❌ Subject and description (min 10 chars) are required.');
        }
        header('Location: helpdesk.php'); exit;
    }

    // ── Add comment ───────────────────────────────────────────
    if ($action === 'add_comment') {
        $ticketId  = (int)($_POST['ticket_id'] ?? 0);
        $comment   = trim($_POST['comment'] ?? '');
        $isInternal= ($isItStaff && !empty($_POST['is_internal'])) ? 1 : 0;

        if ($ticketId && strlen($comment) >= 2) {
            $ticket = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
            if ($ticket && ($isItStaff || (int)$ticket['submitted_by'] === $uid)) {
                $attach = handleUpload('comment_attachment');
                $db->prepare("INSERT INTO it_ticket_comments (ticket_id,user_id,comment,attachment_path,is_internal) VALUES (?,?,?,?,?)")
                   ->execute([$ticketId,$uid,$comment,$attach,$isInternal]);
                $db->prepare("UPDATE it_tickets SET updated_at=NOW() WHERE id=?")->execute([$ticketId]);

                logTicketActivity($db,$ticketId,$isInternal?'INTERNAL_NOTE':'COMMENT_ADDED',$uid,substr($comment,0,80));
                logActivity($uid,'HELPDESK_COMMENT','Commented on ticket ID '.$ticketId);

                // Notify ticket owner (if IT replying) or IT staff (if user replying)
                if ($isItStaff && (int)$ticket['submitted_by'] !== $uid && !$isInternal) {
                    sendNotification((int)$ticket['submitted_by'],
                        '💬 Update on ticket '.$ticket['ticket_no'],
                        'IT has replied to your ticket: '.sanitize(substr($comment,0,80)),
                        'info', SITE_URL.'/portals/helpdesk.php?view='.$ticketId);
                } elseif (!$isItStaff) {
                    $itStaff = $db->query(
                        "SELECT DISTINCT u.id FROM users u
                         JOIN roles r ON u.role_id=r.id
                         LEFT JOIN departments d ON u.department_id=d.id
                         WHERE (r.slug IN ('it_admin','it_officer') OR UPPER(d.code)='IT')
                         AND u.is_active=1"
                    )->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($itStaff as $itId) {
                        sendNotification((int)$itId,'💬 User replied on '.$ticket['ticket_no'],
                            sanitize($user['first_name'].' '.$user['last_name']).': '.sanitize(substr($comment,0,80)),
                            'info', SITE_URL.'/portals/it_tickets.php?view='.$ticketId);
                    }
                }
                setFlash('success','✅ Comment posted.');
            }
        }
        header('Location: helpdesk.php?view='.$ticketId); exit;
    }

    // ── Rate ticket (user only, after resolved) ───────────────
    if ($action === 'rate_ticket') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $rating   = min(5, max(1, (int)($_POST['rating'] ?? 0)));
        $ticket   = $db->query("SELECT * FROM it_tickets WHERE id=$ticketId")->fetch();
        if ($ticket && (int)$ticket['submitted_by']===$uid && $ticket['status']==='resolved') {
            $db->prepare("UPDATE it_tickets SET satisfaction_rating=?,status='closed',closed_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$rating,$ticketId]);
            logTicketActivity($db,$ticketId,'TICKET_RATED',$uid,'Rating: '.$rating.'/5');
            setFlash('success','✅ Thank you for your feedback! Ticket closed.');
        }
        header('Location: helpdesk.php'); exit;
    }
}

$pageTitle    = 'IT Help Desk';
$pageSubtitle = 'Submit and track your IT support requests';
require_once __DIR__ . '/../includes/header.php';

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

    if (!$ticket || (!$isItStaff && (int)$ticket['submitted_by'] !== $uid)) {
        setFlash('danger','Ticket not found or access denied.');
        header('Location: helpdesk.php'); exit;
    }

    $comments = $db->query(
        "SELECT c.*, u.first_name, u.last_name, u.avatar, r.slug AS role_slug
         FROM it_ticket_comments c
         JOIN users u ON c.user_id=u.id
         JOIN roles r ON u.role_id=r.id
         WHERE c.ticket_id=$viewId
         ".(!$isItStaff ? "AND c.is_internal=0" : "")."
         ORDER BY c.created_at ASC"
    )->fetchAll();

    // Activity log for IT staff
    $activityLog = [];
    if ($isItStaff) {
        try {
            $activityLog = $db->query(
                "SELECT l.*, u.first_name, u.last_name
                 FROM it_ticket_activity_log l
                 LEFT JOIN users u ON l.performed_by=u.id
                 WHERE l.ticket_id=$viewId ORDER BY l.created_at ASC"
            )->fetchAll();
        } catch (Throwable $e) {}
    }

    $breached   = $ticket['sla_deadline'] && strtotime($ticket['sla_deadline']) < time()
                  && !in_array($ticket['status'], ['resolved','closed','cancelled']);
    $remaining  = $ticket['sla_deadline'] ? strtotime($ticket['sla_deadline']) - time() : 0;
    $statusColors = ['open'=>'var(--info)','in_progress'=>'var(--warning)','pending_user'=>'var(--gold)',
                     'resolved'=>'var(--success)','closed'=>'var(--text-muted)','cancelled'=>'var(--danger)'];
    $statusCol  = $statusColors[$ticket['status']] ?? 'var(--text-muted)';
    $priColors  = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'];
?>

<!-- Breadcrumb -->
<div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-muted);">
  <a href="helpdesk.php" style="color:var(--text-muted);text-decoration:none;"><?= icon('arrow-left',13) ?> My Tickets</a>
  <span>/</span>
  <span style="color:var(--gold);font-family:monospace;"><?= sanitize($ticket['ticket_no']) ?></span>
</div>

<!-- Ticket Header Card -->
<div class="card mb-24">
  <div class="card-header" style="flex-wrap:wrap;gap:12px;">
    <div style="flex:1;min-width:0;">
      <div style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:6px;"><?= sanitize($ticket['subject']) ?></div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span class="badge badge-<?= $priColors[$ticket['priority']]??'muted' ?>"><?= ucfirst($ticket['priority']) ?></span>
        <span style="font-size:12px;font-weight:700;color:<?= $statusCol ?>;"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span>
        <?php if ($ticket['escalated']??0): ?><span class="badge badge-danger">⬆ Escalated</span><?php endif; ?>
        <?php if ($breached): ?><span class="badge badge-danger">⚡ SLA Breached</span><?php endif; ?>
        <span class="badge badge-muted" style="font-size:9px;"><?= ucfirst($ticket['category']) ?></span>
        <span style="font-size:11px;color:var(--text-muted);">Submitted <?= timeAgo($ticket['opened_at']) ?></span>
      </div>
    </div>
    <div style="text-align:right;flex-shrink:0;">
      <div style="font-family:monospace;font-size:13px;font-weight:700;color:var(--gold);"><?= sanitize($ticket['ticket_no']) ?></div>
      <?php if ($ticket['asgn_first']): ?>
      <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Assigned: <?= sanitize($ticket['asgn_first'].' '.$ticket['asgn_last']) ?></div>
      <?php else: ?>
      <div style="font-size:11px;color:var(--warning);margin-top:4px;">⏳ Awaiting assignment</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- SLA countdown -->
  <?php if ($ticket['sla_deadline'] && !in_array($ticket['status'],['resolved','closed','cancelled'])): ?>
  <div style="padding:10px 20px;border-top:1px solid var(--border);background:<?= $breached?'rgba(232,85,106,0.06)':($remaining<7200?'rgba(245,166,35,0.05)':'var(--bg-elevated)') ?>;">
    <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">SLA DEADLINE</div>
    <div id="slaTimer" data-deadline="<?= strtotime($ticket['sla_deadline']) ?>"
         style="font-size:14px;font-weight:700;color:<?= $breached?'var(--danger)':($remaining<7200?'var(--warning)':'var(--success)') ?>;">
      <?= $breached ? '⚡ SLA BREACHED' : '' ?>
    </div>
    <div style="font-size:11px;color:var(--text-muted);">Deadline: <?= date('M d, Y H:i', strtotime($ticket['sla_deadline'])) ?></div>
  </div>
  <?php endif; ?>

  <div class="card-body">
    <!-- Description -->
    <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:20px;font-size:14px;line-height:1.8;color:var(--text-primary);white-space:pre-wrap;"><?= sanitize($ticket['description']) ?></div>

    <!-- Attachment -->
    <?php if ($ticket['attachment_path']): ?>
    <div style="margin-bottom:16px;">
      <a href="<?= SITE_URL.'/'.$ticket['attachment_path'] ?>" target="_blank" class="btn btn-outline btn-sm"><?= icon('paperclip',13) ?> View Attachment</a>
    </div>
    <?php endif; ?>

    <!-- Resolution notes -->
    <?php if ($ticket['resolution_notes']): ?>
    <div style="background:rgba(45,212,160,0.06);border:1px solid rgba(45,212,160,0.2);border-radius:8px;padding:14px;margin-bottom:20px;">
      <div style="font-size:11px;font-weight:700;color:var(--success);margin-bottom:6px;"><?= icon('check-square',12) ?> RESOLUTION</div>
      <div style="font-size:13px;color:var(--text-primary);line-height:1.7;"><?= sanitize($ticket['resolution_notes']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Satisfaction rating for resolved tickets -->
    <?php if ($ticket['status']==='resolved' && (int)$ticket['submitted_by']===$uid && !$ticket['satisfaction_rating']): ?>
    <div style="background:rgba(201,168,76,0.06);border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:16px;margin-bottom:20px;">
      <div style="font-size:13px;font-weight:600;color:var(--gold);margin-bottom:10px;">How was your experience? Rate this resolution:</div>
      <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="action" value="rate_ticket">
        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
        <div style="display:flex;gap:6px;">
          <?php for ($r=1;$r<=5;$r++): ?>
          <label style="cursor:pointer;">
            <input type="radio" name="rating" value="<?= $r ?>" style="display:none;" required>
            <span class="star-btn" data-val="<?= $r ?>" style="font-size:24px;cursor:pointer;filter:grayscale(1);transition:.2s;" onmouseover="highlightStars(<?= $r ?>)" onmouseout="resetStars()">⭐</span>
          </label>
          <?php endfor; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Submit & Close Ticket</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($ticket['satisfaction_rating']): ?>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px;">
      Your rating: <?= str_repeat('⭐',(int)$ticket['satisfaction_rating']) ?> (<?= $ticket['satisfaction_rating'] ?>/5)
    </div>
    <?php endif; ?>

    <!-- Comments thread -->
    <div style="border-top:1px solid var(--border);padding-top:16px;">
      <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:14px;">
        <?= icon('message-circle',14) ?> <?= count($comments) ?> Comment<?= count($comments)!=1?'s':'' ?>
      </div>

      <?php foreach ($comments as $c): ?>
      <div style="display:flex;gap:10px;margin-bottom:16px;<?= $c['is_internal']?'opacity:0.75;background:rgba(245,166,35,0.04);border-radius:8px;padding:8px;':'' ?>">
        <?php if (!empty($c['avatar'])): ?>
        <img src="<?= sanitize(SITE_URL.'/'.ltrim($c['avatar'],'/')) ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
        <?php else: ?>
        <div class="avatar avatar-sm" style="flex-shrink:0;"><?= getInitials($c['first_name'],$c['last_name']) ?></div>
        <?php endif; ?>
        <div style="flex:1;">
          <div style="font-size:12px;margin-bottom:5px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <strong style="color:var(--text-primary);"><?= sanitize($c['first_name'].' '.$c['last_name']) ?></strong>
            <?php if (in_array($c['role_slug'],['it_admin','it_officer'])): ?>
            <span class="badge badge-info" style="font-size:9px;">IT Staff</span>
            <?php endif; ?>
            <?php if ($c['is_internal']): ?>
            <span class="badge badge-warning" style="font-size:9px;">Internal Note</span>
            <?php endif; ?>
            <span style="color:var(--text-muted);"><?= timeAgo($c['created_at']) ?></span>
          </div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;line-height:1.6;color:var(--text-primary);"><?= sanitize($c['comment']) ?></div>
          <?php if ($c['attachment_path']): ?>
          <a href="<?= SITE_URL.'/'.$c['attachment_path'] ?>" target="_blank" style="font-size:11px;color:var(--gold);margin-top:4px;display:inline-block;"><?= icon('paperclip',11) ?> Attachment</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Add comment form -->
      <?php if (!in_array($ticket['status'],['closed','cancelled'])): ?>
      <form method="POST" enctype="multipart/form-data" style="border-top:1px solid var(--border);padding-top:14px;margin-top:8px;">
        <input type="hidden" name="action" value="add_comment">
        <input type="hidden" name="ticket_id" value="<?= $viewId ?>">
        <div class="form-group">
          <textarea name="comment" class="form-control" rows="3" placeholder="Write your reply here…" required data-counter="commentCounter"></textarea>
          <div style="text-align:right;margin-top:3px;"><span class="char-counter" id="commentCounter">0 chars</span></div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <div>
            <label class="form-label" style="font-size:11px;">Attach file (optional, max 5MB)</label>
            <input type="file" name="comment_attachment" class="form-control" style="width:auto;" accept=".png,.jpg,.jpeg,.pdf">
          </div>
          <?php if ($isItStaff): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-muted);cursor:pointer;white-space:nowrap;">
            <input type="checkbox" name="is_internal" style="width:14px;height:14px;"> Internal note only
          </label>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;"><?= icon('send',13) ?> Post Reply</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Activity Log (IT Staff) -->
<?php if ($isItStaff && !empty($activityLog)): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><?= icon('activity') ?> Activity Timeline</div></div>
  <div class="card-body" style="padding:12px 20px;">
    <?php foreach ($activityLog as $al): ?>
    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;font-size:12px;">
      <div style="width:8px;height:8px;border-radius:50%;background:var(--gold);flex-shrink:0;margin-top:4px;"></div>
      <div>
        <span style="color:var(--text-secondary);font-weight:600;"><?= sanitize(str_replace('_',' ',$al['action'])) ?></span>
        <?php if ($al['first_name']): ?><span style="color:var(--text-muted);"> by <?= sanitize($al['first_name'].' '.$al['last_name']) ?></span><?php endif; ?>
        <?php if ($al['note']): ?><span style="color:var(--text-muted);"> — <?= sanitize($al['note']) ?></span><?php endif; ?>
        <span style="color:var(--text-muted);margin-left:6px;"><?= timeAgo($al['created_at']) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
// SLA countdown timer
(function() {
  const el = document.getElementById('slaTimer');
  if (!el || <?= $breached?'true':'false' ?>) return;
  const deadline = <?= strtotime($ticket['sla_deadline'] ?? 'now') ?> * 1000;
  function tick() {
    const diff = deadline - Date.now();
    if (diff <= 0) { el.textContent = '⚡ SLA BREACHED'; el.style.color='var(--danger)'; return; }
    const h = Math.floor(diff/3600000), m = Math.floor((diff%3600000)/60000), s = Math.floor((diff%60000)/1000);
    el.textContent = h+'h '+m+'m '+s+'s remaining';
    el.style.color = h < 2 ? 'var(--danger)' : (h < 6 ? 'var(--warning)' : 'var(--success)');
    setTimeout(tick,1000);
  }
  tick();
})();

// Star rating UI
function highlightStars(n) {
  document.querySelectorAll('.star-btn').forEach(s=>{s.style.filter=parseInt(s.dataset.val)<=n?'none':'grayscale(1)';});
}
function resetStars() {
  const checked = document.querySelector('input[name="rating"]:checked');
  const v = checked ? parseInt(checked.value) : 0;
  document.querySelectorAll('.star-btn').forEach(s=>{s.style.filter=parseInt(s.dataset.val)<=v?'none':'grayscale(1)';});
}
document.querySelectorAll('input[name="rating"]').forEach(r=>{
  r.addEventListener('change',()=>highlightStars(parseInt(r.value)));
});
</script>

<?php
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ── Ticket list view ──────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$page         = max(1,(int)($_GET['page']??1));
$perPage      = 20;
$offset       = ($page-1)*$perPage;

$where  = ["t.submitted_by=$uid"];
$params = [];
if ($filterStatus) { $where[]="t.status=?"; $params[]=$filterStatus; }
$whereSql = 'WHERE '.implode(' AND ',$where);

$total   = (int)$db->prepare("SELECT COUNT(*) FROM it_tickets t $whereSql")->execute($params) ?
           $db->prepare("SELECT COUNT(*) FROM it_tickets t $whereSql")->execute($params) : 0;
$cntStmt = $db->prepare("SELECT COUNT(*) FROM it_tickets t $whereSql");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$totalPages = max(1,(int)ceil($total/$perPage));

$stmt = $db->prepare(
    "SELECT t.*, a.first_name AS asgn_first, a.last_name AS asgn_last,
            (SELECT COUNT(*) FROM it_ticket_comments WHERE ticket_id=t.id) AS comments
     FROM it_tickets t
     LEFT JOIN users a ON t.assigned_to=a.id
     $whereSql
     ORDER BY FIELD(t.priority,'critical','high','medium','low'),
              FIELD(t.status,'open','in_progress','pending_user','resolved','closed','cancelled'),
              t.opened_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// My stats
$myOpen       = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE submitted_by=$uid AND status IN ('open','in_progress','pending_user')")->fetchColumn();
$myResolved   = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE submitted_by=$uid AND status IN ('resolved','closed')")->fetchColumn();
$myPending    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE submitted_by=$uid AND status='pending_user'")->fetchColumn();
$myBreached   = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE submitted_by=$uid AND sla_deadline<NOW() AND status IN ('open','in_progress','pending_user')")->fetchColumn();

$priColors  = ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'muted'];
$statusLabels = ['open'=>'Open','in_progress'=>'In Progress','pending_user'=>'Awaiting Your Response','resolved'=>'Resolved','closed'=>'Closed','cancelled'=>'Cancelled'];
$statusColors = ['open'=>'var(--info)','in_progress'=>'var(--warning)','pending_user'=>'var(--gold)','resolved'=>'var(--success)','closed'=>'var(--text-muted)','cancelled'=>'var(--danger)'];
?>

<!-- Header controls -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php foreach ([''=> 'All','open'=>'Open','in_progress'=>'In Progress','pending_user'=>'Needs Response','resolved'=>'Resolved','closed'=>'Closed'] as $val=>$label): ?>
    <a href="helpdesk.php<?= $val?'?status='.$val:'' ?>" class="btn btn-sm <?= $filterStatus===$val?'btn-primary':'btn-outline' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-primary" onclick="openModal('newTicketModal')"><?= icon('plus',13) ?> Submit New Ticket</button>
</div>

<!-- Stat cards -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card <?= $myOpen>0?'orange':'green' ?>">
    <div class="stat-top"><div><div class="stat-number"><?= $myOpen ?></div><div class="stat-label">Open</div></div><div class="stat-icon"><?= icon('message-square',18) ?></div></div>
    <div class="stat-delta">Active tickets</div>
  </div>
  <div class="stat-card <?= $myPending>0?'':'green' ?>" style="<?= $myPending>0?'border-top-color:var(--warning);':'' ?>">
    <div class="stat-top"><div><div class="stat-number" style="color:<?= $myPending>0?'var(--warning)':'var(--success)' ?>;"><?= $myPending ?></div><div class="stat-label">Your Response Needed</div></div><div class="stat-icon"><?= icon('clock',18) ?></div></div>
    <div class="stat-delta <?= $myPending>0?'down':'up' ?>"><?= $myPending>0?'Action required':'All good' ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-top"><div><div class="stat-number"><?= $myResolved ?></div><div class="stat-label">Resolved</div></div><div class="stat-icon"><?= icon('check-square',18) ?></div></div>
    <div class="stat-delta up">Completed tickets</div>
  </div>
  <div class="stat-card <?= $myBreached>0?'':'blue' ?>" style="<?= $myBreached>0?'border-top-color:var(--danger);':'' ?>">
    <div class="stat-top"><div><div class="stat-number" style="color:<?= $myBreached>0?'var(--danger)':'var(--info)' ?>;"><?= $myBreached ?></div><div class="stat-label">SLA Breached</div></div><div class="stat-icon"><?= icon('alert-triangle',18) ?></div></div>
    <div class="stat-delta"><?= $myBreached>0?'Overdue':'All on time' ?></div>
  </div>
</div>

<!-- Needs response alert -->
<?php if ($myPending > 0): ?>
<div class="card mb-24" style="border:1px solid rgba(245,166,35,0.4);background:rgba(245,166,35,0.04);">
  <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:20px;">⚠️</span>
    <div style="flex:1;">
      <div style="font-size:13px;font-weight:700;color:var(--warning);"><?= $myPending ?> ticket<?= $myPending>1?'s require':'requires' ?> your response</div>
      <div style="font-size:12px;color:var(--text-muted);">IT is waiting for additional information from you.</div>
    </div>
    <a href="helpdesk.php?status=pending_user" class="btn btn-sm" style="background:var(--warning);color:#fff;border:none;">View</a>
  </div>
</div>
<?php endif; ?>

<!-- Tickets table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('message-square') ?> My Tickets <span class="badge badge-muted" style="margin-left:6px;"><?= $total ?></span></div>
  </div>
  <?php if (empty($tickets)): ?>
  <div class="card-body">
    <div class="empty-state" style="padding:60px;">
      <?= icon('message-square',48) ?>
      <h3>No tickets yet</h3>
      <p style="color:var(--text-muted);">Submit a ticket when you have an IT issue.</p>
      <button class="btn btn-primary" style="margin-top:16px;" onclick="openModal('newTicketModal')"><?= icon('plus',13) ?> Submit First Ticket</button>
    </div>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ticket #</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>SLA</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($tickets as $t):
          $breached  = $t['sla_deadline'] && strtotime($t['sla_deadline'])<time() && !in_array($t['status'],['resolved','closed','cancelled']);
          $remaining = $t['sla_deadline'] ? max(0,strtotime($t['sla_deadline'])-time()) : 0;
          $slaColor  = $breached?'var(--danger)':($remaining<7200&&$remaining>0?'var(--warning)':'var(--text-muted)');
        ?>
        <tr style="<?= $breached?'background:rgba(232,85,106,0.03);':'' ?><?= $t['status']==='pending_user'?'background:rgba(245,166,35,0.03);':'' ?>">
          <td>
            <div style="font-family:monospace;font-size:12px;font-weight:700;color:var(--gold);"><?= sanitize($t['ticket_no']) ?></div>
            <div class="td-muted" style="font-size:10px;"><?= date('M d',strtotime($t['opened_at'])) ?></div>
          </td>
          <td>
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= sanitize($t['subject']) ?></div>
            <div style="font-size:10px;color:var(--text-muted);"><?= ucfirst($t['category']) ?><?= $t['comments']>0?' · '.$t['comments'].' comment'.($t['comments']>1?'s':''):'' ?></div>
          </td>
          <td><span class="badge badge-<?= $priColors[$t['priority']]??'muted' ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td><span style="font-size:12px;font-weight:700;color:<?= $statusColors[$t['status']]??'var(--text-muted)' ?>;"><?= $statusLabels[$t['status']]??ucfirst($t['status']) ?></span></td>
          <td class="td-muted" style="font-size:12px;"><?= $t['asgn_first']?sanitize($t['asgn_first'].' '.$t['asgn_last']):'—' ?></td>
          <td>
            <?php if ($t['sla_deadline'] && !in_array($t['status'],['resolved','closed','cancelled'])): ?>
            <span style="font-size:11px;font-weight:600;color:<?= $slaColor ?>;">
              <?= $breached?'⚡ Overdue':(floor($remaining/3600).'h left') ?>
            </span>
            <?php elseif (in_array($t['status'],['resolved','closed'])): ?>
            <span style="font-size:11px;color:var(--success);">✓ Done</span>
            <?php else: ?><span class="td-muted">—</span><?php endif; ?>
          </td>
          <td class="td-muted" style="font-size:11px;"><?= $t['updated_at']?timeAgo($t['updated_at']):timeAgo($t['opened_at']) ?></td>
          <td><a href="helpdesk.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--border);">
    <span style="font-size:12px;color:var(--text-muted);">Page <?= $page ?>/<?= $totalPages ?></span>
    <div style="display:flex;gap:6px;">
      <?php if ($page>1): ?><a href="?status=<?= $filterStatus ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← Prev</a><?php endif; ?>
      <?php if ($page<$totalPages): ?><a href="?status=<?= $filterStatus ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- New Ticket Modal -->
<div class="modal-overlay" id="newTicketModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('send',16) ?> Submit IT Support Ticket</div>
      <button class="modal-close" onclick="closeModal('newTicketModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
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
              <option value="hardware">🖥 Hardware</option>
              <option value="software">💾 Software</option>
              <option value="network">🌐 Network / Internet</option>
              <option value="access">🔑 Access / Accounts</option>
              <option value="email">📧 Email</option>
              <option value="system">⚙️ System / Server</option>
              <option value="other">❓ Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control" id="prioritySel" onchange="updateSlaInfo()">
              <option value="low">Low — Not urgent</option>
              <option value="medium" selected>Medium — Normal</option>
              <option value="high">High — Affecting my work</option>
              <option value="critical">🚨 Critical — System down</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Detailed Description <span style="color:var(--danger);">*</span></label>
          <textarea name="description" class="form-control" rows="5"
            placeholder="Describe the issue in detail:&#10;• What happened?&#10;• Any error messages?&#10;• Which device or system?&#10;• Steps to reproduce…"
            data-counter="ticketDescCounter" required></textarea>
          <div style="text-align:right;margin-top:3px;"><span class="char-counter" id="ticketDescCounter">0 chars</span></div>
        </div>
        <div class="form-group">
          <label class="form-label">Attachment <span class="form-helper">(optional — PNG, JPG, PDF, max 5MB)</span></label>
          <input type="file" name="attachment" class="form-control" accept=".png,.jpg,.jpeg,.pdf,.gif">
        </div>
        <!-- AI Suggestions panel (optional, shows after typing description) -->
        <div id="aiSuggestBox" style="display:none;background:rgba(201,168,76,0.06);border:1px solid rgba(201,168,76,0.2);border-radius:8px;padding:12px 14px;margin-bottom:8px;">
          <div style="font-size:11px;font-weight:700;color:var(--gold);margin-bottom:8px;">🤖 AI Suggestions <span style="font-weight:400;color:var(--text-muted);">(optional — you can override)</span></div>
          <div id="aiSuggestContent" style="font-size:12px;color:var(--text-secondary);line-height:1.7;"></div>
        </div>

        <!-- Similar open tickets warning -->
        <div id="similarTicketsBox" style="display:none;background:rgba(99,179,237,0.06);border:1px solid rgba(99,179,237,0.2);border-radius:8px;padding:12px 14px;margin-bottom:8px;">
          <div style="font-size:11px;font-weight:700;color:var(--info);margin-bottom:6px;">🔍 Possibly Related Open Tickets</div>
          <div id="similarTicketsContent" style="font-size:12px;color:var(--text-secondary);"></div>
        </div>

        <div id="slaInfoBox" class="alert alert-info" style="font-size:12px;padding:10px 14px;">
          <?= icon('clock',13) ?> <span id="slaInfoText">Medium priority: Response within 48 hours</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('newTicketModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',13) ?> Submit Ticket</button>
      </div>
    </form>
  </div>
</div>

<?php
// Pre-load similar open tickets as JSON for JS (only user's own open + same-category open)
$openTicketsJs = $db->query(
    "SELECT ticket_no, subject, category, status, opened_at FROM it_tickets
     WHERE status IN ('open','in_progress','pending_user')
     AND submitted_by=$uid
     ORDER BY opened_at DESC LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<script>
const slaTimes = {low:'72 hours',medium:'48 hours',high:'24 hours',critical:'4 hours — URGENT'};
function updateSlaInfo() {
  const p = document.getElementById('prioritySel').value;
  document.getElementById('slaInfoText').textContent = p.charAt(0).toUpperCase()+p.slice(1)+' priority: SLA '+slaTimes[p];
  const box = document.getElementById('slaInfoBox');
  box.className = 'alert alert-'+(p==='critical'?'danger':p==='high'?'warning':'info');
}

// ── AI Suggestions (client-side, non-blocking) ─────────────
const myOpenTickets = <?= json_encode($openTicketsJs) ?>;

// Keyword → category map
const catKeywords = {
  hardware: ['laptop','screen','monitor','keyboard','mouse','printer','device','computer','pc','desktop','cable','usb','hdmi','power','charger','battery'],
  software: ['install','software','application','app','error','crash','update','windows','office','microsoft','word','excel','zoom','virus','malware'],
  network:  ['wifi','internet','connection','network','slow','disconnected','vpn','router','ethernet','ping','bandwidth'],
  access:   ['password','login','account','locked','access','permission','reset','unlock','credentials','username','sign in'],
  email:    ['email','mail','outlook','inbox','spam','send','receive','calendar','teams'],
  system:   ['server','system','database','backup','down','outage','maintenance','domain'],
};

// Priority keywords
const priKeywords = {
  critical: ['down','outage','cannot work','system failure','cannot access','urgent','emergency','critical','all staff'],
  high:     ['affecting','cannot','not working','broken','failed','error','blocked'],
  medium:   ['slow','issue','problem','intermittent','sometimes'],
  low:      ['minor','small','would like','feature','suggestion','when possible'],
};

let aiDebounce = null;
function runAiSuggest() {
  clearTimeout(aiDebounce);
  aiDebounce = setTimeout(() => {
    const desc    = (document.querySelector('textarea[name="description"]')?.value || '').toLowerCase();
    const subject = (document.querySelector('input[name="subject"]')?.value || '').toLowerCase();
    const text    = subject + ' ' + desc;
    if (text.trim().length < 15) { document.getElementById('aiSuggestBox').style.display='none'; return; }

    // Category suggestion
    let catScores = {};
    for (const [cat, words] of Object.entries(catKeywords)) {
      catScores[cat] = words.filter(w => text.includes(w)).length;
    }
    const topCat = Object.entries(catScores).sort((a,b)=>b[1]-a[1])[0];

    // Priority suggestion
    let priScore = {critical:0,high:0,medium:0,low:0};
    for (const [pri, words] of Object.entries(priKeywords)) {
      priScore[pri] = words.filter(w => text.includes(w)).length;
    }
    const topPri = Object.entries(priScore).sort((a,b)=>b[1]-a[1])[0];

    const suggestions = [];
    if (topCat[1] > 0) {
      const confidence = Math.min(95, 50 + topCat[1] * 15);
      suggestions.push(`📂 <strong>Suggested category:</strong> ${topCat[0].charAt(0).toUpperCase()+topCat[0].slice(1)} <span style="color:var(--text-muted);">(${confidence}% confidence)</span>
        <button type="button" onclick="document.querySelector('select[name=category]').value='${topCat[0]}';this.textContent='✓ Applied';this.disabled=true;"
          style="font-size:10px;background:rgba(201,168,76,0.15);border:1px solid var(--gold);color:var(--gold);border-radius:4px;padding:1px 8px;cursor:pointer;margin-left:6px;">Apply</button>`);
    }
    if (topPri[1] > 0) {
      const confidence = Math.min(90, 45 + topPri[1] * 15);
      suggestions.push(`⚡ <strong>Suggested priority:</strong> ${topPri[0].charAt(0).toUpperCase()+topPri[0].slice(1)} <span style="color:var(--text-muted);">(${confidence}% confidence)</span>
        <button type="button" onclick="document.querySelector('select[name=priority]').value='${topPri[0]}';updateSlaInfo();this.textContent='✓ Applied';this.disabled=true;"
          style="font-size:10px;background:rgba(201,168,76,0.15);border:1px solid var(--gold);color:var(--gold);border-radius:4px;padding:1px 8px;cursor:pointer;margin-left:6px;">Apply</button>`);
    }

    const box = document.getElementById('aiSuggestBox');
    if (suggestions.length > 0) {
      document.getElementById('aiSuggestContent').innerHTML = suggestions.join('<br>');
      box.style.display = 'block';
    } else {
      box.style.display = 'none';
    }

    // Check for similar open tickets
    if (myOpenTickets.length > 0 && text.length > 10) {
      const words = text.split(/\s+/).filter(w=>w.length>3);
      const similar = myOpenTickets.filter(t => {
        const tText = (t.subject+' '+t.category).toLowerCase();
        return words.some(w => tText.includes(w));
      }).slice(0,3);

      const simBox  = document.getElementById('similarTicketsBox');
      const simCont = document.getElementById('similarTicketsContent');
      if (similar.length > 0) {
        simCont.innerHTML = similar.map(t =>
          `<div style="margin-bottom:4px;">
            <a href="helpdesk.php?view=${t.ticket_no}" style="color:var(--info);font-family:monospace;font-size:11px;">${t.ticket_no}</a>
            — ${t.subject} <span style="color:var(--text-muted);">[${t.status.replace('_',' ')}]</span>
          </div>`
        ).join('');
        simCont.innerHTML += '<div style="margin-top:6px;font-size:11px;color:var(--text-muted);">You may already have an open ticket for this issue. Check before submitting.</div>';
        simBox.style.display = 'block';
      } else {
        simBox.style.display = 'none';
      }
    }
  }, 600);
}

// Attach AI suggest to description and subject fields
document.addEventListener('DOMContentLoaded', () => {
  const desc    = document.querySelector('textarea[name="description"]');
  const subject = document.querySelector('input[name="subject"]');
  if (desc)    desc.addEventListener('input', runAiSuggest);
  if (subject) subject.addEventListener('input', runAiSuggest);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
