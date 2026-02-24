<?php
// ── Process POST first — BEFORE any HTML output ───────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle    = 'Submit Daily KPI';
$pageSubtitle = 'KPI categories for ' . date('l, F j, Y');

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];
$deptId = (int)$user['department_id'];
$today  = date('Y-m-d');

// ── Weekday check (1=Mon … 5=Fri, 6=Sat, 7=Sun) ──────────────
$dayOfWeek = (int)date('N');
$isWeekend = $dayOfWeek >= 6;
$nextMonday = date('l, F j, Y', strtotime('next Monday'));

// Get categories for this user (global + dept-specific)
$catStmt = $db->prepare(
    "SELECT c.*,
            (SELECT COUNT(*) FROM kpi_submissions WHERE user_id=? AND category_id=c.id AND submission_date=?) AS submitted_today
     FROM kpi_categories c
     WHERE c.is_global=1 OR c.department_id=?
     ORDER BY c.is_global DESC, c.name"
);
$catStmt->execute([$uid, $today, $deptId]);
$categories = $catStmt->fetchAll();

$error = $success = '';

// Block POST on weekends server-side
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isWeekend) {
    setFlash('danger', '❌ KPI submissions are only allowed Monday to Friday.');
    header('Location: submit_kpi.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId    = (int)($_POST['category_id']        ?? 0);
    $desc     = trim($_POST['task_description']     ?? '');
    $qty      = (float)($_POST['quantity_completed'] ?? 0);
    $quality  = (float)($_POST['quality_score']     ?? 0);
    $hours    = (float)($_POST['time_spent_hours']  ?? 0);
    $notes    = trim($_POST['supporting_notes']     ?? '');

    if (!$catId)              $error = 'Please select a KPI category.';
    elseif (strlen($desc)<10) $error = 'Task description must be at least 10 characters.';
    elseif ($quality<0||$quality>100) $error = 'Quality score must be between 0 and 100.';
    else {
        try {
            $stmt = $db->prepare(
                "INSERT INTO kpi_submissions (user_id, category_id, submission_date, task_description, quantity_completed, quality_score, time_spent_hours, supporting_notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$uid, $catId, $today, $desc, $qty, $quality ?: null, $hours ?: null, $notes]);

            // Notify dept head
            $headStmt = $db->prepare("SELECT id FROM users WHERE department_id=? AND role_id=(SELECT id FROM roles WHERE slug='dept_head') LIMIT 1");
            $headStmt->execute([$deptId]);
            $head = $headStmt->fetch();
            if ($head) {
                sendNotification($head['id'], 'New KPI Submission', sanitize($user['first_name'].' '.$user['last_name']).' submitted a KPI report.', 'info', SITE_URL.'/portals/head_approvals.php');
            }

            logActivity($uid, 'REPORT_SUBMIT', 'KPI submission for category ID '.$catId);
            setFlash('success', '✅ KPI submitted successfully! It is now pending review.');
            header('Location: submit_kpi.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'You have already submitted this KPI category today. Each category can only be submitted once per day.';
            } else {
                $error = 'Could not save submission. Please try again.';
            }
        }
    }
}

// ── Now load the shared layout (outputs HTML) ───────────────
require_once __DIR__ . '/../includes/header.php';

// Monthly summary
$monthStart    = date('Y-m-01');
$totalMonth    = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid AND submission_date>='$monthStart'")->fetchColumn();
$approvedMonth = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid AND status='approved' AND submission_date>='$monthStart'")->fetchColumn();
$pendingMonth  = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid AND status='pending' AND submission_date>='$monthStart'")->fetchColumn();
$rejectedMonth = (int)$db->query("SELECT COUNT(*) FROM kpi_submissions WHERE user_id=$uid AND status='rejected' AND submission_date>='$monthStart'")->fetchColumn();

// Today's submissions
$todaySubs = $db->prepare(
    "SELECT k.*, c.name AS cat_name FROM kpi_submissions k JOIN kpi_categories c ON k.category_id=c.id WHERE k.user_id=? AND k.submission_date=? ORDER BY k.created_at DESC"
);
$todaySubs->execute([$uid, $today]);
$todaySubs = $todaySubs->fetchAll();
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

  <!-- LEFT: Form -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('bar-chart') ?> Submit KPI Entry</div>
      <span class="badge badge-gold"><?= date('M d, Y') ?></span>
    </div>
    <div class="card-body">

      <?php if ($error): ?>
      <div class="alert alert-danger"><?= icon('x',16) ?> <?= sanitize($error) ?></div>
      <?php endif; ?>

      <?php if ($isWeekend): ?>
      <div style="text-align:center;padding:48px 24px;">
        <div style="width:80px;height:80px;background:var(--bg-elevated);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;border:2px solid var(--border);">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h3 style="font-family:var(--font-display);font-size:24px;color:var(--text-primary);margin:0 0 12px;">
          <?= $dayOfWeek === 6 ? 'Happy Saturday!' : 'Happy Sunday!' ?>
        </h3>
        <p style="color:var(--text-muted);font-size:14px;line-height:1.8;max-width:380px;margin:0 auto 28px;">
          KPI submissions are only open <strong style="color:var(--text-secondary);">Monday to Friday</strong>.<br>
          The submission window reopens on:
        </p>
        <div style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--gold),#a07830);color:#0d0f14;font-weight:700;font-size:14px;padding:12px 28px;border-radius:var(--radius);">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <?= $nextMonday ?>
        </div>
      </div>
      <?php else: ?>

      <form method="POST" novalidate>

        <!-- Category -->
        <div class="form-group">
          <label class="form-label">KPI Category <span style="color:var(--danger);">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="">— Select a category —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
              <?= (int)($cat['submitted_today']) ? 'disabled' : '' ?>
              <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
              <?= sanitize($cat['name']) ?>
              <?= $cat['is_global'] ? '(Global)' : '' ?>
              <?= $cat['submitted_today'] ? '✓ Already submitted' : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="form-helper">Categories marked ✓ have already been submitted today</div>
        </div>

        <!-- Task Description -->
        <div class="form-group">
          <label class="form-label">Task Description <span style="color:var(--danger);">*</span></label>
          <textarea name="task_description" class="form-control" rows="4"
            placeholder="Describe what you did for this KPI category today. Be specific and detailed..."
            data-counter="descCounter"
            required><?= sanitize($_POST['task_description'] ?? '') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Minimum 10 characters</span>
            <span class="char-counter" id="descCounter">0 chars</span>
          </div>
        </div>

        <!-- Qty + Quality -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Quantity Completed</label>
            <input type="number" name="quantity_completed" class="form-control"
              placeholder="e.g. 3, 15, 40"
              min="0" step="0.5"
              value="<?= sanitize($_POST['quantity_completed'] ?? '') ?>">
            <div class="form-helper">Number of items, tasks, or units</div>
          </div>
          <div class="form-group">
            <label class="form-label">Self Quality Score (0–100)</label>
            <input type="number" name="quality_score" class="form-control"
              placeholder="e.g. 85"
              min="0" max="100" step="1"
              id="qualityInput"
              value="<?= sanitize($_POST['quality_score'] ?? '') ?>">
            <div class="form-helper" id="qualityLabel">Rate your own work quality</div>
          </div>
        </div>

        <!-- Hours -->
        <div class="form-group">
          <label class="form-label">Time Spent (Hours)</label>
          <input type="number" name="time_spent_hours" class="form-control"
            placeholder="e.g. 2.5"
            min="0" max="24" step="0.5"
            value="<?= sanitize($_POST['time_spent_hours'] ?? '') ?>">
          <div class="form-helper">Approximate hours spent on this activity</div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="form-label">Supporting Notes <span style="color:var(--text-muted);font-weight:400;">(Optional)</span></label>
          <textarea name="supporting_notes" class="form-control" rows="2"
            placeholder="Any additional context, attachments references, or notes..."
            data-counter="notesCounter"><?= sanitize($_POST['supporting_notes'] ?? '') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Optional additional details</span>
            <span class="char-counter" id="notesCounter">0 chars</span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <?= icon('send',16) ?> Submit KPI Entry
        </button>

        <div style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:12px;color:var(--text-muted);justify-content:center;">
          <?= icon('lock',13) ?>
          Once submitted, KPI entries are locked for editing after approval.
        </div>

      </form>
    </div>
  </div>

  <!-- RIGHT column -->
  <div>

    <!-- Today's Submissions -->
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('clock') ?> Today's Submissions</div>
        <span class="badge badge-muted"><?= count($todaySubs) ?> submitted</span>
      </div>
      <?php if (empty($todaySubs)): ?>
      <div class="card-body">
        <div class="empty-state" style="padding:20px 0;">
          <?= icon('inbox',28) ?>
          <p>No KPI entries yet today</p>
        </div>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Category</th><th>Quality</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($todaySubs as $s): ?>
            <tr>
              <td class="td-bold" style="font-size:12.5px;"><?= sanitize($s['cat_name']) ?></td>
              <td><?= $s['quality_score'] ? $s['quality_score'].'%' : '—' ?></td>
              <td><?= statusBadge($s['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Monthly Summary -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('bar-chart') ?> This Month</div>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
          <div class="metric-box"><div class="metric-box-num"><?= $totalMonth ?></div><div class="metric-box-label">Total</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--success);"><?= $approvedMonth ?></div><div class="metric-box-label">Approved</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--warning);"><?= $pendingMonth ?></div><div class="metric-box-label">Pending</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--danger);"><?= $rejectedMonth ?></div><div class="metric-box-label">Rejected</div></div>
        </div>
        <?php $rate = $totalMonth > 0 ? round(($approvedMonth/$totalMonth)*100) : 0; ?>
        <div class="progress"><div class="progress-bar <?= $rate>=70?'green':($rate>=40?'gold':'red') ?>" style="width:<?= $rate ?>%"></div></div>
        <div style="text-align:right;font-size:12px;font-weight:600;color:var(--text-secondary);margin-top:4px;"><?= $rate ?>% approval rate</div>
      </div>
    </div>

  </div>
</div>

<script>
// Live quality score label
const qi = document.getElementById('qualityInput');
const ql = document.getElementById('qualityLabel');
if (qi && ql) {
  qi.addEventListener('input', () => {
    const v = parseInt(qi.value);
    if (isNaN(v)) { ql.textContent = 'Rate your own work quality'; return; }
    if (v >= 90) ql.textContent = '⭐ Excellent work!';
    else if (v >= 75) ql.textContent = '👍 Good quality';
    else if (v >= 60) ql.textContent = '👌 Satisfactory';
    else if (v >= 40) ql.textContent = '⚠️ Needs improvement';
    else ql.textContent = '❌ Poor quality';
  });
}
</script>

      <?php endif; // isWeekend ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
