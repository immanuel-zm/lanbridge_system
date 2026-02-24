<?php
$pageTitle    = 'Notifications';
$pageSubtitle = 'Your alerts and updates';
require_once __DIR__ . '/../includes/header.php';

$db  = getDB();
$uid = (int)currentUser()['id'];

// Mark single as read
if (isset($_GET['mark_read'])) {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([(int)$_GET['mark_read'],$uid]);
    header('Location: notifications.php'); exit;
}
// Mark all read
if (isset($_GET['mark_all'])) {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    header('Location: notifications.php'); exit;
}
// Clear read
if (isset($_GET['clear_read'])) {
    $db->prepare("DELETE FROM notifications WHERE user_id=? AND is_read=1")->execute([$uid]);
    header('Location: notifications.php'); exit;
}

$all      = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
$all->execute([$uid]);
$all      = $all->fetchAll();
$unread   = array_filter($all, fn($n)=>!$n['is_read']);
$read     = array_filter($all, fn($n)=> $n['is_read']);

$typeIcon = ['info'=>'bell','success'=>'check-square','warning'=>'clock','danger'=>'x'];
?>

<!-- Controls -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div style="display:flex;gap:10px;">
    <?php if (count($unread)>0): ?>
    <a href="?mark_all=1" class="btn btn-outline btn-sm"><?= icon('check-square',13) ?> Mark All Read</a>
    <?php endif; ?>
    <?php if (count($read)>0): ?>
    <a href="?clear_read=1" class="btn btn-danger btn-sm" data-confirm="Delete all read notifications?"><?= icon('x',13) ?> Clear Read</a>
    <?php endif; ?>
  </div>
  <span class="badge badge-muted"><?= count($unread) ?> unread</span>
</div>

<!-- Unread -->
<?php if (!empty($unread)): ?>
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('bell') ?> Unread <span class="badge badge-danger" style="margin-left:6px;"><?= count($unread) ?></span></div>
  </div>
  <div class="card-body" style="padding:12px 16px;">
    <?php foreach ($unread as $n): ?>
    <div class="notif-item unread <?= sanitize($n['type']) ?>">
      <div class="notif-icon" style="background:var(--<?= $n['type'] ?>-dim);color:var(--<?= $n['type'] ?>);">
        <?= icon($typeIcon[$n['type']]??'bell', 14) ?>
      </div>
      <div style="flex:1;">
        <div class="notif-title"><?= sanitize($n['title']) ?></div>
        <div class="notif-msg"><?= sanitize($n['message']) ?></div>
        <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
      </div>
      <a href="?mark_read=<?= $n['id'] ?>" class="btn btn-outline btn-sm" style="flex-shrink:0;">Mark Read</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Read -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('clock') ?> Read Notifications</div>
  </div>
  <div class="card-body" style="padding:12px 16px;">
    <?php if (empty($read)): ?>
    <div class="empty-state"><?= icon('bell',36) ?><h3>You're all caught up!</h3><p>No read notifications</p></div>
    <?php else: ?>
    <?php foreach ($read as $n): ?>
    <div class="notif-item <?= sanitize($n['type']) ?>" style="opacity:0.65;">
      <div class="notif-icon" style="background:var(--<?= $n['type'] ?>-dim);color:var(--<?= $n['type'] ?>);">
        <?= icon($typeIcon[$n['type']]??'bell', 14) ?>
      </div>
      <div>
        <div class="notif-title"><?= sanitize($n['title']) ?></div>
        <div class="notif-msg"><?= sanitize($n['message']) ?></div>
        <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
