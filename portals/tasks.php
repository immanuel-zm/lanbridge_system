<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db     = getDB();
$user   = currentUser();
$uid    = (int)$user['id'];
$level  = (int)$user['role_level'];
$deptId = (int)($user['department_id'] ?? 0);

$canCreate = $level <= 4; // dept heads and above
$isManager = $level <= 3; // VP and above

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create task
    if ($action === 'create_task' && $canCreate) {
        $title      = trim($_POST['title'] ?? '');
        $desc       = trim($_POST['description'] ?? '');
        $priority   = $_POST['priority'] ?? 'medium';
        $deadline   = $_POST['deadline'] ?: null;
        $assignDept = (int)($_POST['assigned_dept_id'] ?? 0) ?: null;
        $assignUser = (int)($_POST['assigned_user_id'] ?? 0) ?: null;
        if (!in_array($priority, ['low','medium','high','critical'])) $priority = 'medium';

        if ($title && strlen($desc) >= 10) {
            $db->prepare(
                "INSERT INTO tasks (title,description,requesting_user_id,requesting_dept_id,
                 assigned_dept_id,assigned_user_id,priority,deadline,status)
                 VALUES (?,?,?,?,?,?,?,?,'open')"
            )->execute([$title,$desc,$uid,$deptId ?: null,$assignDept,$assignUser,$priority,$deadline]);
            $newId = (int)$db->lastInsertId();

            if ($assignUser) {
                sendNotification($assignUser,
                    '📋 New Task: '.$title,
                    ucfirst($priority).' priority task from '.$user['first_name'].' '.$user['last_name'].'.',
                    $priority === 'critical' ? 'danger' : ($priority === 'high' ? 'warning' : 'info'),
                    SITE_URL.'/portals/tasks.php?view='.$newId
                );
            } elseif ($assignDept) {
                $head = $db->query("SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='dept_head' AND u.department_id=$assignDept AND u.is_active=1 LIMIT 1")->fetch();
                if ($head) {
                    sendNotification((int)$head['id'],
                        '📋 New Task for Your Department: '.$title,
                        ucfirst($priority).' priority task assigned to your department.',
                        $priority === 'critical' ? 'danger' : 'info',
                        SITE_URL.'/portals/tasks.php?view='.$newId
                    );
                }
            }
            logActivity($uid,'TASK_CREATED','Task: '.$title);
            setFlash('success','✅ Task created successfully.');
        } else {
            setFlash('danger','❌ Title and description (min 10 chars) are required.');
        }
        header('Location: tasks.php'); exit;
    }

    // Update status
    if ($action === 'update_status') {
        $taskId    = (int)($_POST['task_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        $note      = trim($_POST['completion_note'] ?? '');
        if (!in_array($newStatus, ['open','in_progress','pending_approval','completed','cancelled'])) {
            setFlash('danger','Invalid status.'); header('Location: tasks.php'); exit;
        }
        $task = $db->query("SELECT * FROM tasks WHERE id=$taskId")->fetch();
        if (!$task) { setFlash('danger','Task not found.'); header('Location: tasks.php'); exit; }

        $canAct = $isManager
            || (int)$task['requesting_user_id'] === $uid
            || (int)$task['assigned_user_id']   === $uid
            || (int)$task['assigned_dept_id']   === $deptId;
        if (!$canAct) { setFlash('danger','Permission denied.'); header('Location: tasks.php'); exit; }

        $resolvedAt = in_array($newStatus,['completed','pending_approval']) ? ',completed_at=NOW()' : '';
        $db->prepare("UPDATE tasks SET status=?,completion_note=?,updated_at=NOW()$resolvedAt WHERE id=?")
           ->execute([$newStatus,$note,$taskId]);

        if (in_array($newStatus,['completed','pending_approval']) && (int)$task['requesting_user_id'] !== $uid) {
            sendNotification((int)$task['requesting_user_id'],
                '📋 Task '.ucfirst(str_replace('_',' ',$newStatus)).': '.sanitize($task['title']),
                'Updated by '.$user['first_name'].' '.$user['last_name'].($note ? ' — '.$note : ''),
                'success', SITE_URL.'/portals/tasks.php?view='.$taskId
            );
        }
        logActivity($uid,'TASK_UPDATED','Task #'.$taskId.' → '.$newStatus);
        setFlash('success','✅ Task updated to '.ucfirst(str_replace('_',' ',$newStatus)).'.');
        header('Location: tasks.php?view='.$taskId); exit;
    }

    // Add comment
    if ($action === 'add_comment') {
        $taskId  = (int)($_POST['task_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $task    = $db->query("SELECT * FROM tasks WHERE id=$taskId")->fetch();
        if ($task && strlen($comment) >= 2) {
            $allowed = $isManager
                || (int)$task['requesting_user_id'] === $uid
                || (int)$task['assigned_user_id']   === $uid
                || (int)$task['assigned_dept_id']   === $deptId;
            if ($allowed) {
                $db->prepare("INSERT INTO task_comments (task_id,user_id,comment) VALUES (?,?,?)")
                   ->execute([$taskId,$uid,$comment]);
                $db->prepare("UPDATE tasks SET updated_at=NOW() WHERE id=?")->execute([$taskId]);
                setFlash('success','✅ Comment added.');
            }
        }
        header('Location: tasks.php?view='.$taskId); exit;
    }

    // Delete
    if ($action === 'delete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $task   = $db->query("SELECT * FROM tasks WHERE id=$taskId")->fetch();
        if ($task && ($isManager || (int)$task['requesting_user_id'] === $uid)) {
            $db->prepare("DELETE FROM task_comments WHERE task_id=?")->execute([$taskId]);
            $db->prepare("DELETE FROM tasks WHERE id=?")->execute([$taskId]);
            logActivity($uid,'TASK_DELETED','Task #'.$taskId.' deleted');
            setFlash('success','✅ Task deleted.');
        }
        header('Location: tasks.php'); exit;
    }
}

$pageTitle    = 'Tasks';
$pageSubtitle = 'Assign, track and manage tasks across departments';
require_once __DIR__ . '/../includes/header.php';

// ════════════════════════════════════════════════════════════
// SINGLE TASK VIEW
// ════════════════════════════════════════════════════════════
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId) {
    $task = $db->query(
        "SELECT t.*,
                ru.first_name AS req_first, ru.last_name AS req_last,
                rd.name AS req_dept,
                au.first_name AS asgn_first, au.last_name AS asgn_last,
                ad.name AS asgn_dept
         FROM tasks t
         JOIN users ru ON t.requesting_user_id=ru.id
         LEFT JOIN departments rd ON t.requesting_dept_id=rd.id
         LEFT JOIN users au ON t.assigned_user_id=au.id
         LEFT JOIN departments ad ON t.assigned_dept_id=ad.id
         WHERE t.id=$viewId"
    )->fetch();

    if (!$task) { setFlash('danger','Task not found.'); header('Location: tasks.php'); exit; }

    $comments = $db->query(
        "SELECT c.*, u.first_name, u.last_name, u.avatar
         FROM task_comments c JOIN users u ON c.user_id=u.id
         WHERE c.task_id=$viewId ORDER BY c.created_at ASC"
    )->fetchAll();

    $isCreator  = (int)$task['requesting_user_id'] === $uid;
    $isAssignee = (int)$task['assigned_user_id'] === $uid;
    $isDeptMatch= (int)$task['assigned_dept_id'] === $deptId;
    $canAct     = $isCreator || $isAssignee || $isDeptMatch || $isManager;

    $isOverdue = $task['deadline']
        && strtotime($task['deadline']) < time()
        && !in_array($task['status'],['completed','cancelled']);

    $statusColor = [
        'open'=>'var(--info)','in_progress'=>'var(--warning)',
        'pending_approval'=>'var(--gold)','completed'=>'var(--success)',
        'cancelled'=>'var(--danger)','overdue'=>'var(--danger)',
    ][$task['status']] ?? 'var(--text-muted)';

    $prioBadge = ['low'=>'muted','medium'=>'info','high'=>'warning','critical'=>'danger'];
?>

<div style="margin-bottom:16px;">
  <a href="tasks.php" style="color:var(--text-muted);font-size:13px;text-decoration:none;"><?= icon('arrow-left',13) ?> Back to Tasks</a>
</div>

<!-- Task Card -->
<div class="card mb-24">
  <div class="card-header">
    <div style="flex:1;">
      <div class="card-title"><?= icon('clipboard') ?> <?= sanitize($task['title']) ?></div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
        By <?= sanitize($task['req_first'].' '.$task['req_last']) ?>
        <?= $task['req_dept'] ? '· '.sanitize($task['req_dept']) : '' ?>
        · <?= timeAgo($task['created_at']) ?>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <span class="badge badge-<?= $prioBadge[$task['priority']] ?? 'muted' ?>"><?= ucfirst($task['priority']) ?> Priority</span>
      <span style="font-size:13px;font-weight:700;color:<?= $statusColor ?>;">
        <?= ucfirst(str_replace('_',' ',$task['status'])) ?>
      </span>
      <?php if ($isOverdue): ?><span class="badge badge-danger">⚠️ Overdue</span><?php endif; ?>
      <?php if ($isCreator || $isManager): ?>
      <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this task permanently?')">
        <input type="hidden" name="action" value="delete_task">
        <input type="hidden" name="task_id" value="<?= $viewId ?>">
        <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;"><?= icon('trash',12) ?> Delete</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">

    <!-- Meta row -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding-bottom:20px;margin-bottom:20px;border-bottom:1px solid var(--border);">
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:5px;">ASSIGNED TO</div>
        <?php if ($task['asgn_first']): ?>
        <div style="font-weight:600;font-size:13px;"><?= sanitize($task['asgn_first'].' '.$task['asgn_last']) ?></div>
        <?php elseif ($task['asgn_dept']): ?>
        <div style="font-weight:600;font-size:13px;"><?= sanitize($task['asgn_dept']) ?> <span class="td-muted">(dept)</span></div>
        <?php else: ?>
        <div class="td-muted">Unassigned</div>
        <?php endif; ?>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:5px;">DEADLINE</div>
        <div style="font-weight:600;font-size:13px;color:<?= $isOverdue?'var(--danger)':'var(--text-primary)' ?>;">
          <?= $task['deadline'] ? date('M d, Y',strtotime($task['deadline'])) : '—' ?>
          <?= $isOverdue ? ' ⚠️' : '' ?>
        </div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:5px;">COMPLETED AT</div>
        <div style="font-weight:600;font-size:13px;color:var(--success);">
          <?= $task['completed_at'] ? date('M d, Y H:i',strtotime($task['completed_at'])) : '—' ?>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div style="margin-bottom:20px;">
      <div style="font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:8px;">DESCRIPTION</div>
      <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:16px;font-size:13.5px;line-height:1.8;white-space:pre-wrap;color:var(--text-primary);"><?= sanitize($task['description']) ?></div>
    </div>

    <!-- Completion note -->
    <?php if ($task['completion_note']): ?>
    <div style="background:rgba(45,212,160,0.06);border:1px solid rgba(45,212,160,0.2);border-radius:8px;padding:14px;margin-bottom:20px;">
      <div style="font-size:11px;font-weight:700;color:var(--success);margin-bottom:6px;">✅ COMPLETION NOTE</div>
      <div style="font-size:13px;line-height:1.7;"><?= sanitize($task['completion_note']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Update status -->
    <?php if ($canAct && !in_array($task['status'],['completed','cancelled'])): ?>
    <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:24px;">
      <div style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:12px;"><?= icon('settings',13) ?> Update Status</div>
      <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="task_id" value="<?= $viewId ?>">
        <div>
          <label class="form-label" style="font-size:11px;">New Status</label>
          <select name="new_status" class="form-control" style="width:190px;">
            <?php foreach (['open','in_progress','pending_approval','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $task['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex:1;min-width:180px;">
          <label class="form-label" style="font-size:11px;">Note (optional)</label>
          <input type="text" name="completion_note" class="form-control" placeholder="What was done, or why status changed…">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><?= icon('save',13) ?> Update</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Comments -->
    <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
      <?= icon('message-circle',14) ?> <?= count($comments) ?> Comment<?= count($comments)!=1?'s':'' ?>
    </div>

    <?php foreach ($comments as $c): ?>
    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <?php if (!empty($c['avatar'])): ?>
      <img src="<?= sanitize(SITE_URL.'/'.ltrim($c['avatar'],'/')) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
      <?php else: ?>
      <div class="avatar avatar-sm" style="flex-shrink:0;"><?= getInitials($c['first_name'],$c['last_name']) ?></div>
      <?php endif; ?>
      <div style="flex:1;">
        <div style="font-size:12px;margin-bottom:4px;">
          <strong style="color:var(--text-primary);"><?= sanitize($c['first_name'].' '.$c['last_name']) ?></strong>
          <span style="color:var(--text-muted);margin-left:6px;"><?= timeAgo($c['created_at']) ?></span>
        </div>
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;line-height:1.65;"><?= sanitize($c['comment']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Add comment -->
    <?php if ($canAct): ?>
    <form method="POST" style="display:flex;gap:8px;margin-top:12px;align-items:flex-start;">
      <input type="hidden" name="action" value="add_comment">
      <input type="hidden" name="task_id" value="<?= $viewId ?>">
      <div class="avatar avatar-sm" style="flex-shrink:0;margin-top:4px;"><?= getInitials($user['first_name'],$user['last_name']) ?></div>
      <textarea name="comment" class="form-control" rows="2" placeholder="Add a comment…" style="flex:1;" required></textarea>
      <button type="submit" class="btn btn-primary btn-sm" style="margin-top:4px;"><?= icon('send',13) ?></button>
    </form>
    <?php endif; ?>

  </div>
</div>

<?php
    require_once __DIR__ . '/../includes/footer.php'; exit;
}

// ════════════════════════════════════════════════════════════
// TASK LIST VIEW
// ════════════════════════════════════════════════════════════
$tab          = $_GET['tab'] ?? 'all';
$filterPrio   = $_GET['priority'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$where  = ['1=1'];
$params = [];

if ($tab === 'mine') {
    $where[] = "(t.assigned_user_id=$uid OR t.assigned_dept_id=$deptId)";
} elseif ($tab === 'created') {
    $where[] = "t.requesting_user_id=$uid";
} elseif ($tab === 'open') {
    $where[] = "t.status IN ('open','in_progress','pending_approval')";
    if (!$isManager) $where[] = "(t.requesting_user_id=$uid OR t.assigned_user_id=$uid OR t.assigned_dept_id=$deptId)";
} else {
    if (!$isManager) $where[] = "(t.requesting_user_id=$uid OR t.assigned_user_id=$uid OR t.assigned_dept_id=$deptId)";
}

if ($filterPrio)   { $where[] = 't.priority=?';  $params[] = $filterPrio; }
if ($filterStatus) { $where[] = 't.status=?';    $params[] = $filterStatus; }

$stmt = $db->prepare(
    "SELECT t.*,
            ru.first_name AS req_first, ru.last_name AS req_last,
            rd.name AS req_dept,
            au.first_name AS asgn_first, au.last_name AS asgn_last,
            ad.name AS asgn_dept,
            (SELECT COUNT(*) FROM task_comments WHERE task_id=t.id) AS comment_count
     FROM tasks t
     JOIN users ru ON t.requesting_user_id=ru.id
     LEFT JOIN departments rd ON t.requesting_dept_id=rd.id
     LEFT JOIN users au ON t.assigned_user_id=au.id
     LEFT JOIN departments ad ON t.assigned_dept_id=ad.id
     WHERE ".implode(' AND ',$where)."
     ORDER BY
       FIELD(t.priority,'critical','high','medium','low'),
       FIELD(t.status,'open','in_progress','pending_approval','overdue','completed','cancelled'),
       t.created_at DESC
     LIMIT 150"
);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Counts for stat cards
$myOpen      = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE (assigned_user_id=$uid OR assigned_dept_id=$deptId) AND status IN ('open','in_progress')")->fetchColumn();
$myCreated   = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE requesting_user_id=$uid AND status NOT IN ('completed','cancelled')")->fetchColumn();
$needApprove = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE requesting_user_id=$uid AND status='pending_approval'")->fetchColumn();
$overdue     = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE deadline < CURDATE() AND status IN ('open','in_progress') AND (assigned_user_id=$uid OR assigned_dept_id=$deptId)")->fetchColumn();

// For create modal
$allDepts = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();
$allUsers = $db->query(
    "SELECT u.id,u.first_name,u.last_name,d.name AS dept_name
     FROM users u LEFT JOIN departments d ON u.department_id=d.id
     WHERE u.is_active=1 ORDER BY u.first_name"
)->fetchAll();

$prioBadge   = ['low'=>'muted','medium'=>'info','high'=>'warning','critical'=>'danger'];
$statusColor = ['open'=>'var(--info)','in_progress'=>'var(--warning)','pending_approval'=>'var(--gold)','completed'=>'var(--success)','cancelled'=>'var(--text-muted)','overdue'=>'var(--danger)'];
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card <?= $myOpen>0?'orange':'green' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $myOpen ?></div><div class="stat-label">Assigned to Me</div></div>
      <div class="stat-icon"><?= icon('clipboard',20) ?></div>
    </div>
    <div class="stat-delta <?= $myOpen===0?'up':'' ?>"><?= $myOpen===0?'All done!':'Pending action' ?></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $myCreated ?></div><div class="stat-label">Created by Me</div></div>
      <div class="stat-icon"><?= icon('plus-circle',20) ?></div>
    </div>
    <div class="stat-delta">Active tasks I created</div>
  </div>
  <div class="stat-card <?= $needApprove>0?'blue':'green' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $needApprove ?></div><div class="stat-label">Awaiting My Approval</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta"><?= $needApprove>0?'Review these tasks':'Nothing to approve' ?></div>
  </div>
  <div class="stat-card <?= $overdue>0?'':'green' ?>" style="<?= $overdue>0?'border-top-color:var(--danger);':'' ?>">
    <div class="stat-top">
      <div><div class="stat-number" style="color:<?= $overdue>0?'var(--danger)':'var(--success)' ?>;"><?= $overdue ?></div><div class="stat-label">Overdue</div></div>
      <div class="stat-icon"><?= icon('alert-triangle',20) ?></div>
    </div>
    <div class="stat-delta <?= $overdue>0?'down':'up' ?>"><?= $overdue>0?'Past deadline!':'No overdue tasks' ?></div>
  </div>
</div>

<!-- Pending approval banner -->
<?php if ($needApprove > 0): ?>
<div class="card mb-24" style="border:1px solid rgba(201,168,76,0.4);background:rgba(201,168,76,0.04);">
  <div class="card-body" style="padding:14px 20px;display:flex;align-items:center;gap:12px;">
    <span style="font-size:20px;">⏳</span>
    <div style="flex:1;">
      <div style="font-size:14px;font-weight:700;color:var(--gold);"><?= $needApprove ?> task<?= $needApprove>1?'s':'' ?> awaiting your approval</div>
      <div style="font-size:12px;color:var(--text-muted);">Staff marked these complete — review and confirm or reopen.</div>
    </div>
    <a href="tasks.php?tab=created&status=pending_approval" class="btn btn-sm" style="background:var(--gold);color:#0d0f14;border:none;">Review Now</a>
  </div>
</div>
<?php endif; ?>

<!-- Filters + Create button -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('filter') ?> Filter Tasks</div>
    <?php if ($canCreate): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('createTaskModal')"><?= icon('plus',13) ?> Create Task</button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label class="form-label" style="font-size:11px;">View</label>
        <select name="tab" class="form-control" style="width:150px;" onchange="this.form.submit()">
          <option value="all"     <?= $tab==='all'?'selected':'' ?>>All Tasks</option>
          <option value="mine"    <?= $tab==='mine'?'selected':'' ?>>Assigned to Me</option>
          <option value="created" <?= $tab==='created'?'selected':'' ?>>Created by Me</option>
          <option value="open"    <?= $tab==='open'?'selected':'' ?>>Open / Pending</option>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Priority</label>
        <select name="priority" class="form-control" style="width:130px;">
          <option value="">All Priorities</option>
          <?php foreach (['critical','high','medium','low'] as $p): ?>
          <option value="<?= $p ?>" <?= $filterPrio===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Status</label>
        <select name="status" class="form-control" style="width:160px;">
          <option value="">All Statuses</option>
          <?php foreach (['open','in_progress','pending_approval','completed','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('search',13) ?> Filter</button>
      <a href="tasks.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<!-- Task Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('clipboard') ?> Tasks <span class="badge badge-muted" style="margin-left:6px;"><?= count($tasks) ?></span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Task</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Assigned To</th>
          <th>From</th>
          <th>Deadline</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tasks)): ?>
        <tr><td colspan="7">
          <div class="empty-state" style="padding:48px;">
            <?= icon('clipboard',44) ?>
            <h3>No tasks found</h3>
            <p style="color:var(--text-muted);"><?= $canCreate ? 'Create your first task to get started.' : 'No tasks assigned to you yet.' ?></p>
            <?php if ($canCreate): ?>
            <button class="btn btn-primary" style="margin-top:14px;" onclick="openModal('createTaskModal')"><?= icon('plus',14) ?> Create Task</button>
            <?php endif; ?>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($tasks as $t):
          $late = $t['deadline'] && strtotime($t['deadline']) < time() && !in_array($t['status'],['completed','cancelled']);
          $soon = !$late && $t['deadline'] && strtotime($t['deadline']) <= strtotime('+3 days');
        ?>
        <tr style="<?= $late?'background:rgba(232,85,106,0.03);':'' ?>">
          <td style="max-width:230px;">
            <a href="tasks.php?view=<?= $t['id'] ?>" style="font-size:13px;font-weight:600;color:var(--text-primary);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= sanitize($t['title']) ?>
            </a>
            <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
              <?= timeAgo($t['created_at']) ?>
              <?php if ((int)$t['comment_count']>0): ?>
              · <?= icon('message-circle',10) ?> <?= $t['comment_count'] ?>
              <?php endif; ?>
            </div>
          </td>
          <td><span class="badge badge-<?= $prioBadge[$t['priority']]??'muted' ?>"><?= ucfirst($t['priority']) ?></span></td>
          <td>
            <span style="font-size:12px;font-weight:600;color:<?= $statusColor[$t['status']]??'var(--text-muted)' ?>;">
              <?= ucfirst(str_replace('_',' ',$t['status'])) ?>
            </span>
          </td>
          <td>
            <?php if ($t['asgn_first']): ?>
            <div style="font-size:12.5px;font-weight:500;"><?= sanitize($t['asgn_first'].' '.$t['asgn_last']) ?></div>
            <?php elseif ($t['asgn_dept']): ?>
            <span class="badge badge-muted" style="font-size:10px;"><?= sanitize($t['asgn_dept']) ?></span>
            <?php else: ?><span class="td-muted">—</span><?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted);"><?= sanitize($t['req_first'].' '.$t['req_last']) ?></td>
          <td>
            <?php if ($t['deadline']): ?>
            <span style="font-size:12px;font-weight:600;color:<?= $late?'var(--danger)':($soon?'var(--warning)':'var(--text-muted)') ?>;">
              <?= $late?'⚠️ ':'' ?><?= date('M d, Y',strtotime($t['deadline'])) ?>
            </span>
            <?php else: ?><span class="td-muted">—</span><?php endif; ?>
          </td>
          <td>
            <a href="tasks.php?view=<?= $t['id'] ?>" class="btn btn-outline btn-sm"><?= icon('eye',12) ?> View</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create Task Modal -->
<?php if ($canCreate): ?>
<div class="modal-overlay" id="createTaskModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('plus-circle') ?> Create New Task</div>
      <button class="modal-close" onclick="closeModal('createTaskModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_task">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Task Title <span style="color:var(--danger);">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Clear, concise task title" required maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">Description <span style="color:var(--danger);">*</span></label>
          <textarea name="description" class="form-control" rows="4"
            placeholder="Describe what needs to be done, expected outcome, any special instructions…"
            data-counter="taskDescCounter" required></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Minimum 10 characters</span>
            <span class="char-counter" id="taskDescCounter">0 chars</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-control">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="critical">🚨 Critical</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Deadline</label>
            <input type="date" name="deadline" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Department</label>
            <select name="assigned_dept_id" class="form-control">
              <option value="">— Any Department —</option>
              <?php foreach ($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign to Specific Staff</label>
            <select name="assigned_user_id" class="form-control">
              <option value="">— Department only —</option>
              <?php foreach ($allUsers as $u): ?>
              <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'].' '.$u['last_name']) ?> (<?= sanitize($u['dept_name']??'—') ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-helper">Leave blank to assign to the whole department</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('createTaskModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Create Task</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
