<?php
/**
 * notifications_api.php
 * Real-time notification polling endpoint.
 * Returns JSON — called every 30s by the frontend JS.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}

$user = currentUser();
$uid  = (int)$user['id'];
$db   = getDB();

$action = $_GET['action'] ?? 'poll';

// ── Mark notification(s) as read ─────────────────────────────
if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $uid]);
    } else {
        // Mark all read
        $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── Poll: return unread count + latest notifications ─────────
$since = (int)($_GET['since'] ?? 0); // unix timestamp — only return newer

$unread = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE user_id=$uid AND is_read=0")->fetchColumn();

// Latest 8 notifications (read or unread), newest first
$stmt = $db->prepare(
    "SELECT id, title, message, type, link, is_read, created_at
     FROM notifications
     WHERE user_id=?
     ORDER BY created_at DESC
     LIMIT 8"
);
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for frontend
$formatted = array_map(function($n) {
    return [
        'id'         => (int)$n['id'],
        'title'      => $n['title'],
        'message'    => $n['message'],
        'type'       => $n['type'],           // success | warning | danger | info
        'link'       => $n['link'],
        'is_read'    => (bool)$n['is_read'],
        'time_ago'   => timeAgo($n['created_at']),
        'created_at' => $n['created_at'],
    ];
}, $notifications);

// Also return any new items since last poll (for toast popups)
$newItems = [];
if ($since > 0) {
    $sinceDate = date('Y-m-d H:i:s', $since);
    $newStmt = $db->prepare(
        "SELECT id, title, message, type, link, created_at
         FROM notifications
         WHERE user_id=? AND created_at > ? AND is_read=0
         ORDER BY created_at DESC LIMIT 5"
    );
    $newStmt->execute([$uid, $sinceDate]);
    $newItems = $newStmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'ok'            => true,
    'unread'        => $unread,
    'notifications' => $formatted,
    'new_items'     => $newItems,
    'server_time'   => time(),
]);
