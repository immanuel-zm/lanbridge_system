<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db       = getDB();
$user     = currentUser();
$uid      = (int)$user['id'];
$level    = (int)$user['role_level'];
$deptId   = (int)($user['department_id'] ?? 0);
$canPost  = $level <= 3;
$canManage= $level <= 2;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'post_announcement' && $canPost) {
        $title    = trim($_POST['title'] ?? '');
        $body     = trim($_POST['body'] ?? '');
        $audience = in_array($_POST['audience']??'',['all','departments']) ? $_POST['audience'] : 'all';
        $pinned   = (int)isset($_POST['is_pinned']);
        $depts    = array_map('intval', (array)($_POST['dept_ids'] ?? []));

        if ($title && strlen($body) >= 10) {
            $db->prepare("INSERT INTO announcements (posted_by,title,body,audience,is_pinned,is_active) VALUES (?,?,?,?,?,1)")
               ->execute([$uid,$title,$body,$audience,$pinned]);
            $annId = (int)$db->lastInsertId();

            if ($audience === 'departments' && !empty($depts)) {
                $ins = $db->prepare("INSERT IGNORE INTO announcement_departments (announcement_id,department_id) VALUES (?,?)");
                foreach ($depts as $did) { if ($did) $ins->execute([$annId,$did]); }
            }

            // Notify users
            $notifWhere = ($audience === 'all' || empty($depts)) ? '1=1' : "u.department_id IN (".implode(',',array_filter($depts)).")";
            $notifUsers = $db->query("SELECT id FROM users WHERE is_active=1 AND id!=$uid AND ($notifWhere)")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($notifUsers as $nid) {
                sendNotification((int)$nid, '📢 '.sanitize($title), sanitize(substr($body,0,120)), $pinned?'warning':'info', SITE_URL.'/portals/announcements.php#ann-'.$annId);
            }
            logActivity($uid,'ANNOUNCEMENT_POSTED','Posted: '.$title);
            setFlash('success','✅ Announcement posted'.($pinned?' and pinned':'').'.');
        } else {
            setFlash('danger','❌ Title and body (min 10 chars) are required.');
        }
        header('Location: announcements.php'); exit;
    }

    if ($action === 'toggle_pin' && $canPost) {
        $annId = (int)($_POST['ann_id'] ?? 0);
        $ann   = $db->query("SELECT posted_by FROM announcements WHERE id=$annId")->fetch();
        if ($ann && ((int)$ann['posted_by']===$uid || $canManage)) {
            $db->prepare("UPDATE announcements SET is_pinned=IF(is_pinned,0,1),updated_at=NOW() WHERE id=?")->execute([$annId]);
        }
        header('Location: announcements.php'); exit;
    }

    if ($action === 'delete_announcement') {
        $annId = (int)($_POST['ann_id'] ?? 0);
        $ann   = $db->query("SELECT posted_by FROM announcements WHERE id=$annId")->fetch();
        if ($ann && ((int)$ann['posted_by']===$uid || $canManage)) {
            $db->prepare("DELETE FROM announcement_departments WHERE announcement_id=?")->execute([$annId]);
            $db->prepare("DELETE FROM announcements WHERE id=?")->execute([$annId]);
            setFlash('success','✅ Announcement deleted.');
        }
        header('Location: announcements.php'); exit;
    }
}

$pageTitle    = 'Announcements';
$pageSubtitle = 'Institution-wide communications board';
require_once __DIR__ . '/../includes/header.php';

$announcements = $db->query(
    "SELECT a.*, u.first_name, u.last_name, u.avatar, r.name AS role_name,
            GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS dept_names
     FROM announcements a
     JOIN users u ON a.posted_by=u.id
     JOIN roles r ON u.role_id=r.id
     LEFT JOIN announcement_departments ad ON a.id=ad.announcement_id
     LEFT JOIN departments d ON ad.department_id=d.id
     WHERE a.is_active=1
       AND (a.audience='all' OR a.posted_by=$uid OR $level<=2
            OR (a.audience='departments' AND ad.department_id=$deptId))
     GROUP BY a.id
     ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 60"
)->fetchAll();

$allDepts = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();
?>

<?php if ($canPost): ?>
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openModal('postAnnModal')"><?= icon('plus',14) ?> Post Announcement</button>
</div>
<?php endif; ?>

<?php if (empty($announcements)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state" style="padding:60px;">
      <?= icon('volume-2',48) ?>
      <h3>No announcements yet</h3>
      <p style="color:var(--text-muted);">Posts from management will appear here.</p>
      <?php if ($canPost): ?>
      <button class="btn btn-primary" style="margin-top:16px;" onclick="openModal('postAnnModal')"><?= icon('plus',14) ?> Post First Announcement</button>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php else: ?>

<?php foreach ($announcements as $ann):
  $isOwn   = (int)$ann['posted_by'] === $uid;
  $canEdit = $isOwn || $canManage;
  $isNew   = strtotime($ann['created_at']) > strtotime('-48 hours');
  $isPinned= (bool)$ann['is_pinned'];
?>
<div class="card mb-24" id="ann-<?= $ann['id'] ?>" style="<?= $isPinned?'border-left:4px solid var(--gold);background:rgba(201,168,76,0.02);':'' ?>">
  <div class="card-header" style="flex-wrap:wrap;gap:10px;">
    <div style="flex:1;min-width:0;">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
        <?php if ($isPinned): ?><span title="Pinned" style="font-size:15px;">📌</span><?php endif; ?>
        <h3 style="font-size:15px;font-weight:700;color:var(--text-primary);margin:0;line-height:1.3;"><?= sanitize($ann['title']) ?></h3>
        <?php if ($isNew): ?><span class="badge badge-success" style="font-size:9px;">NEW</span><?php endif; ?>
        <?php if ($ann['audience']==='departments'): ?>
        <span class="badge badge-info" style="font-size:9px;"><?= icon('layers',10) ?> <?= sanitize($ann['dept_names']??'Specific depts') ?></span>
        <?php else: ?>
        <span class="badge badge-muted" style="font-size:9px;"><?= icon('users',10) ?> All Staff</span>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <?php if (!empty($ann['avatar'])): ?>
        <img src="<?= sanitize(SITE_URL.'/'.ltrim($ann['avatar'],'/')) ?>" style="width:22px;height:22px;border-radius:50%;object-fit:cover;" alt="">
        <?php else: ?>
        <div class="avatar avatar-sm" style="width:22px;height:22px;font-size:8px;flex-shrink:0;"><?= getInitials($ann['first_name'],$ann['last_name']) ?></div>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted);">
          <strong style="color:var(--text-secondary);"><?= sanitize($ann['first_name'].' '.$ann['last_name']) ?></strong>
          · <?= sanitize($ann['role_name']) ?> · <?= timeAgo($ann['created_at']) ?>
          <?php if ($ann['updated_at']): ?><em style="font-size:10px;"> (edited)</em><?php endif; ?>
        </span>
      </div>
    </div>
    <?php if ($canEdit): ?>
    <div style="display:flex;gap:6px;flex-shrink:0;">
      <form method="POST" style="margin:0;">
        <input type="hidden" name="action" value="toggle_pin">
        <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
        <button type="submit" class="btn btn-outline btn-sm" title="<?= $isPinned?'Unpin':'Pin to top' ?>" style="font-size:11px;">
          <?= $isPinned ? '📌 Unpin' : '📌 Pin' ?>
        </button>
      </form>
      <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this announcement permanently?')">
        <input type="hidden" name="action" value="delete_announcement">
        <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
        <button type="submit" class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;"><?= icon('trash',12) ?></button>
      </form>
    </div>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div style="font-size:14px;line-height:1.9;color:var(--text-primary);white-space:pre-wrap;"><?= sanitize($ann['body']) ?></div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canPost): ?>
<div class="modal-overlay" id="postAnnModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('volume-2') ?> Post Announcement</div>
      <button class="modal-close" onclick="closeModal('postAnnModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="post_announcement">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--danger);">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required maxlength="255">
        </div>
        <div class="form-group">
          <label class="form-label">Message <span style="color:var(--danger);">*</span></label>
          <textarea name="body" class="form-control" rows="6" placeholder="Write your announcement here…" data-counter="annCounter" required></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Min 10 characters</span>
            <span class="char-counter" id="annCounter">0 chars</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Audience</label>
            <select name="audience" class="form-control" id="audienceSel" onchange="toggleDeptPicker()">
              <option value="all">All Staff</option>
              <option value="departments">Specific Departments</option>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);padding-bottom:8px;">
              <input type="checkbox" name="is_pinned" value="1" style="width:16px;height:16px;"> 📌 Pin to top
            </label>
          </div>
        </div>
        <div id="deptPickerWrap" style="display:none;">
          <div class="form-group">
            <label class="form-label">Select Departments</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:14px;">
              <?php foreach ($allDepts as $d): ?>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;color:var(--text-secondary);">
                <input type="checkbox" name="dept_ids[]" value="<?= $d['id'] ?>" style="width:14px;height:14px;">
                <?= sanitize($d['name']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('postAnnModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('send',14) ?> Post Announcement</button>
      </div>
    </form>
  </div>
</div>
<script>
function toggleDeptPicker() {
  document.getElementById('deptPickerWrap').style.display =
    document.getElementById('audienceSel').value === 'departments' ? 'block' : 'none';
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
