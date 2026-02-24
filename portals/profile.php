<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── Handle avatar upload ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', '❌ Upload failed. Please try again.');
    } elseif (!in_array($file['type'], $allowed)) {
        setFlash('danger', '❌ Only JPG, PNG, GIF and WEBP images are allowed.');
    } elseif ($file['size'] > $maxSize) {
        setFlash('danger', '❌ Image must be under 2MB.');
    } else {
        // Build upload path
        $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Delete old avatar if exists
        $oldAvatar = $user['avatar'] ?? null;
        if ($oldAvatar && file_exists($uploadDir . basename($oldAvatar))) {
            unlink($uploadDir . basename($oldAvatar));
        }

        // Unique filename
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $avatarPath = 'assets/uploads/avatars/' . $filename;
            $db->prepare("UPDATE users SET avatar=?, updated_at=NOW() WHERE id=?")
               ->execute([$avatarPath, $uid]);
            clearUserCache();
            setFlash('success', '✅ Profile picture updated!');
        } else {
            setFlash('danger', '❌ Could not save the image. Check folder permissions.');
        }
    }
    header('Location: profile.php'); exit;
}

// ── Handle avatar removal ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_avatar'])) {
    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
    $oldAvatar = $user['avatar'] ?? null;
    if ($oldAvatar && file_exists($uploadDir . basename($oldAvatar))) {
        unlink($uploadDir . basename($oldAvatar));
    }
    $db->prepare("UPDATE users SET avatar=NULL, updated_at=NOW() WHERE id=?")->execute([$uid]);
    clearUserCache();
    setFlash('success', '✅ Profile picture removed.');
    header('Location: profile.php'); exit;
}

// ── Handle profile info update ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $phone    = trim($_POST['phone']    ?? '');
    $position = trim($_POST['position'] ?? '');
    $db->prepare("UPDATE users SET phone=?, position=?, updated_at=NOW() WHERE id=?")
       ->execute([$phone, $position, $uid]);
    clearUserCache();
    setFlash('success', '✅ Profile updated successfully.');
    header('Location: profile.php'); exit;
}

$pageTitle    = 'My Profile';
$pageSubtitle = 'Manage your account and profile picture';
require_once __DIR__ . '/../includes/header.php';

// Refresh user after any changes
$user = currentUser();

$stats = [
    'total'    => (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid")->fetchColumn(),
    'approved' => (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='approved'")->fetchColumn(),
    'kpis'     => (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid")->fetchColumn(),
];

// Build avatar URL
$avatarUrl = null;
if (!empty($user['avatar'])) {
    $avatarUrl = SITE_URL . '/' . ltrim($user['avatar'], '/');
}
?>

<div style="max-width:760px;margin:0 auto;">
  <div class="card mb-24">
    <div class="card-body" style="padding:32px;">

      <!-- ── Avatar Section ─────────────────────────────────── -->
      <div style="display:flex;align-items:flex-start;gap:28px;margin-bottom:32px;flex-wrap:wrap;">

        <!-- Avatar display -->
        <div style="position:relative;flex-shrink:0;">
          <div class="profile-avatar-wrap" id="avatarWrap">
            <?php if ($avatarUrl): ?>
            <img src="<?= sanitize($avatarUrl) ?>?v=<?= time() ?>"
                 alt="Profile picture"
                 class="profile-avatar-img"
                 id="avatarPreview">
            <?php else: ?>
            <div class="profile-avatar-initials" id="avatarInitials">
              <?= getInitials($user['first_name'], $user['last_name']) ?>
            </div>
            <?php endif; ?>
            <!-- Camera overlay trigger -->
            <label for="avatarInput" class="avatar-edit-overlay" title="Change profile picture">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
            </label>
          </div>

          <!-- Remove button -->
          <?php if ($avatarUrl): ?>
          <form method="POST" style="margin-top:8px;text-align:center;">
            <button type="submit" name="remove_avatar" value="1"
                    class="btn btn-danger btn-sm"
                    style="font-size:11px;padding:4px 10px;"
                    data-confirm="Remove your profile picture?">
              Remove Photo
            </button>
          </form>
          <?php endif; ?>
        </div>

        <!-- Avatar upload form + user info -->
        <div style="flex:1;min-width:200px;">
          <div style="font-family:var(--font-display);font-size:22px;color:var(--text-primary);margin-bottom:4px;">
            <?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?>
          </div>
          <div style="color:var(--text-muted);font-size:13px;margin-bottom:10px;">
            <?= sanitize($user['email']) ?>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <span class="badge badge-gold"><?= sanitize($user['role_name']) ?></span>
            <?php if ($user['dept_name']): ?>
            <span class="badge badge-muted"><?= sanitize($user['dept_name']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Hidden file input — triggered by camera overlay -->
          <form method="POST" enctype="multipart/form-data" id="avatarForm">
            <input type="file"
                   name="avatar"
                   id="avatarInput"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   style="display:none;"
                   onchange="previewAndUpload(this)">
          </form>

          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
            <?= icon('lock', 11) ?> JPG, PNG, GIF or WEBP · Max 2MB<br>
            Click the camera icon on your photo to change it.
          </div>

          <!-- Upload progress indicator -->
          <div id="uploadStatus" style="display:none;margin-top:12px;">
            <div style="display:flex;align-items:center;gap:8px;color:var(--gold);font-size:13px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round"
                   style="animation:spin 1s linear infinite;">
                <polyline points="23 4 23 10 17 10"/>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
              </svg>
              Uploading...
            </div>
          </div>
        </div>
      </div>

      <!-- ── Stats ─────────────────────────────────────────── -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px;">
        <div class="metric-box">
          <div class="metric-box-num"><?= $stats['total'] ?></div>
          <div class="metric-box-label">Total Reports</div>
        </div>
        <div class="metric-box">
          <div class="metric-box-num" style="color:var(--success);"><?= $stats['approved'] ?></div>
          <div class="metric-box-label">Approved</div>
        </div>
        <div class="metric-box">
          <div class="metric-box-num" style="color:var(--gold);"><?= $stats['kpis'] ?></div>
          <div class="metric-box-label">KPI Entries</div>
        </div>
      </div>

      <!-- ── Read-only info grid ────────────────────────────── -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
        <?php foreach ([
          'Employee ID'  => $user['employee_id'],
          'Department'   => $user['dept_name'] ?? '—',
          'Member Since' => $user['join_date'] ? formatDate($user['join_date'], 'M d, Y') : '—',
          'Last Login'   => $user['last_login'] ? formatDate($user['last_login'], 'M d, Y h:i A') : 'Never',
        ] as $label => $val): ?>
        <div>
          <div class="form-label"><?= $label ?></div>
          <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:9px 13px;font-size:13.5px;color:var(--text-primary);">
            <?= sanitize($val) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ── Editable fields ───────────────────────────────── -->
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control"
                   placeholder="+260 97X XXX XXX"
                   value="<?= sanitize($user['phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Position / Title</label>
            <input type="text" name="position" class="form-control"
                   placeholder="e.g. Senior Lecturer"
                   value="<?= sanitize($user['position'] ?? '') ?>">
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:8px;">
          <button type="submit" name="save_profile" value="1" class="btn btn-primary">
            <?= icon('user', 14) ?> Save Changes
          </button>
          <a href="<?= SITE_URL ?>/change_password.php" class="btn btn-outline">
            <?= icon('lock', 14) ?> Change Password
          </a>
        </div>
      </form>

    </div>
  </div>
</div>

<style>
/* ── Profile avatar styles ──────────────────────────────────── */
.profile-avatar-wrap {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  position: relative;
  cursor: pointer;
  flex-shrink: 0;
}
.profile-avatar-img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--gold);
  display: block;
}
.profile-avatar-initials {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), #a07830);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 34px;
  color: #0d0f14;
  border: 3px solid var(--gold);
  user-select: none;
}
.avatar-edit-overlay {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 32px;
  height: 32px;
  background: var(--gold);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d0f14;
  cursor: pointer;
  border: 2px solid var(--bg-card);
  transition: transform 0.15s ease, background 0.15s ease;
}
.avatar-edit-overlay:hover {
  transform: scale(1.1);
  background: #e0b96a;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>

<script>
function previewAndUpload(input) {
  if (!input.files || !input.files[0]) return;

  const file = input.files[0];

  // Client-side size check
  if (file.size > 2 * 1024 * 1024) {
    alert('Image must be under 2MB. Please choose a smaller file.');
    input.value = '';
    return;
  }

  // Show preview instantly
  const reader = new FileReader();
  reader.onload = function(e) {
    const wrap = document.getElementById('avatarWrap');

    // Replace initials div or existing img with preview
    const existing = wrap.querySelector('.profile-avatar-initials, .profile-avatar-img');
    if (existing) existing.remove();

    const img = document.createElement('img');
    img.src       = e.target.result;
    img.className = 'profile-avatar-img';
    img.id        = 'avatarPreview';
    wrap.insertBefore(img, wrap.querySelector('.avatar-edit-overlay'));
  };
  reader.readAsDataURL(file);

  // Show uploading status
  document.getElementById('uploadStatus').style.display = 'block';

  // Submit the form
  document.getElementById('avatarForm').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
