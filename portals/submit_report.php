<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle    = 'Submit Daily Report';
$pageSubtitle = 'Your report for ' . date('l, F j, Y');

$db    = getDB();
$user  = currentUser();
$uid   = (int)$user['id'];
$today = date('Y-m-d');

// ── Weekday check (ISO: 1=Mon … 5=Fri, 6=Sat, 7=Sun) ─────────
$dayOfWeek  = (int)date('N');
$isWeekend  = $dayOfWeek >= 6;
$nextMonday = date('l, F j, Y', strtotime('next Monday'));

// Block POST on weekends server-side
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isWeekend) {
    setFlash('danger', '❌ Report submissions are only allowed Monday to Friday.');
    header('Location: submit_report.php'); exit;
}

// Check if already submitted today
$stmt = $db->prepare("SELECT r.*, u.first_name AS rev_first, u.last_name AS rev_last FROM reports r LEFT JOIN users u ON r.approved_by=u.id WHERE r.user_id=? AND r.report_date=?");
$stmt->execute([$uid, $today]);
$existing = $stmt->fetch();

$error = '';

// Handle form submission (weekdays only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing && !$isWeekend) {
    $tasks      = trim($_POST['tasks_completed'] ?? '');
    $metrics    = trim($_POST['key_metrics']     ?? '');
    $challenges = trim($_POST['challenges']      ?? '');
    $tomorrow   = trim($_POST['tomorrow_plan']   ?? '');

    if (strlen($tasks) < 20) {
        $error = 'Tasks completed must be at least 20 characters. Please be more descriptive.';
    } else {
        try {
            $stmt = $db->prepare(
                "INSERT INTO reports (user_id, report_date, tasks_completed, key_metrics, challenges, tomorrow_plan, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$uid, $today, $tasks, $metrics, $challenges, $tomorrow]);

            if ($user['supervisor_id']) {
                sendNotification($user['supervisor_id'], 'New Daily Report',
                    sanitize($user['first_name'] . ' ' . $user['last_name']) . ' has submitted their daily report for ' . date('M d, Y') . '.',
                    'info', SITE_URL . '/portals/head_approvals.php');
            }
            $headStmt = $db->prepare("SELECT id FROM users WHERE department_id=? AND role_id=(SELECT id FROM roles WHERE slug='dept_head') LIMIT 1");
            $headStmt->execute([$user['department_id']]);
            $head = $headStmt->fetch();
            if ($head && $head['id'] != $user['supervisor_id']) {
                sendNotification($head['id'], 'New Daily Report',
                    sanitize($user['first_name'] . ' ' . $user['last_name']) . ' submitted their daily report.',
                    'info', SITE_URL . '/portals/head_approvals.php');
            }

            logActivity($uid, 'REPORT_SUBMIT', 'Daily report submitted for ' . $today);
            setFlash('success', '✅ Your daily report has been submitted successfully!');
            header('Location: submit_report.php'); exit;
        } catch (PDOException $e) {
            $error = 'Could not save report. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$monthStart    = date('Y-m-01');
$totalMonth    = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date>='$monthStart'")->fetchColumn();
$approvedMonth = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='approved' AND report_date>='$monthStart'")->fetchColumn();
$pendingMonth  = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='pending' AND report_date>='$monthStart'")->fetchColumn();
$rejectedMonth = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='rejected' AND report_date>='$monthStart'")->fetchColumn();

$recent = $db->prepare("SELECT r.*, u.first_name AS rev_first, u.last_name AS rev_last FROM reports r LEFT JOIN users u ON r.approved_by=u.id WHERE r.user_id=? ORDER BY r.report_date DESC LIMIT 6");
$recent->execute([$uid]);
$recent = $recent->fetchAll();
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

  <!-- LEFT: Form / Weekend Block / Read-only -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('file-text') ?> Today's Daily Report</div>
      <span class="badge <?= $isWeekend ? 'badge-muted' : 'badge-gold' ?>"><?= date('l, M d, Y') ?></span>
    </div>
    <div class="card-body">

      <?php if ($error): ?>
      <div class="alert alert-danger"><?= icon('x', 16) ?> <?= sanitize($error) ?></div>
      <?php endif; ?>

      <?php if ($isWeekend): ?>
      <!-- WEEKEND BLOCK -->
      <div style="text-align:center;padding:48px 24px;">
        <div style="width:80px;height:80px;background:var(--bg-elevated);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;border:2px solid var(--border);">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h3 style="font-family:var(--font-display);font-size:24px;color:var(--text-primary);margin:0 0 12px;">
          <?= $dayOfWeek === 6 ? 'Happy Saturday!' : 'Happy Sunday!' ?>
        </h3>
        <p style="color:var(--text-muted);font-size:14px;line-height:1.8;max-width:380px;margin:0 auto 28px;">
          Daily report submissions are only open <strong style="color:var(--text-secondary);">Monday to Friday</strong>.<br>
          Take a well-deserved break! The submission window reopens on:
        </p>
        <div style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--gold),#a07830);color:#0d0f14;font-weight:700;font-size:14px;padding:12px 28px;border-radius:var(--radius);margin-bottom:36px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <?= $nextMonday ?>
        </div>
        <!-- Day pills row -->
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
          <?php
          $days     = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
          $todayIdx = $dayOfWeek - 1; // 0-based
          foreach ($days as $i => $d):
            $isToday  = ($i === $todayIdx);
            $isOpen   = ($i < 5);
          ?>
          <div style="text-align:center;">
            <div style="
              width:46px;height:46px;border-radius:50%;
              display:flex;align-items:center;justify-content:center;
              font-size:11px;font-weight:700;
              background:<?= $isToday ? 'var(--bg-elevated)' : ($isOpen ? 'rgba(201,168,76,0.10)' : 'rgba(232,85,106,0.10)') ?>;
              border:2px solid <?= $isToday ? 'var(--text-muted)' : ($isOpen ? 'rgba(201,168,76,0.35)' : 'rgba(232,85,106,0.35)') ?>;
              color:<?= $isOpen ? 'var(--gold)' : 'var(--danger)' ?>;
              opacity:<?= $isToday ? '1' : '0.7' ?>;
            "><?= $d ?></div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:5px;"><?= $isOpen ? '✓' : '✗' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php elseif ($existing): ?>
      <!-- READ-ONLY VIEW -->
      <div style="margin-bottom:16px;">
        <?= statusBadge($existing['status']) ?>
        <?php if ($existing['status'] === 'rejected' && $existing['approval_comment']): ?>
        <div class="alert alert-danger" style="margin-top:12px;">
          <?= icon('x', 15) ?>
          <div><strong>Reviewer Comment:</strong><br><?= sanitize($existing['approval_comment']) ?></div>
        </div>
        <?php elseif ($existing['status'] === 'approved'): ?>
        <div class="alert alert-success" style="margin-top:12px;">
          <?= icon('check-square', 15) ?>
          Approved by <?= sanitize($existing['rev_first'] . ' ' . $existing['rev_last']) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php foreach ([
        'Tasks Completed Today'      => $existing['tasks_completed'],
        'Key Metrics / Achievements' => $existing['key_metrics'],
        'Challenges / Issues Faced'  => $existing['challenges'],
        'Plan for Tomorrow'          => $existing['tomorrow_plan'],
      ] as $label => $value): if (!$value) continue; ?>
      <div style="margin-bottom:16px;">
        <div class="form-label" style="margin-bottom:6px;"><?= $label ?></div>
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;font-size:13.5px;color:var(--text-primary);line-height:1.7;white-space:pre-wrap;"><?= sanitize($value) ?></div>
      </div>
      <?php endforeach; ?>
      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text-muted);">
        <?= icon('lock', 13) ?> Submitted at <?= date('h:i A', strtotime($existing['created_at'])) ?> · Reports cannot be edited once submitted.
      </div>

      <?php else: ?>
      <!-- SUBMIT FORM -->
      <form method="POST" id="reportForm" novalidate>
        <div class="form-group">
          <label class="form-label">Tasks Completed Today <span style="color:var(--danger);">*</span></label>
          <textarea name="tasks_completed" class="form-control" rows="5"
            placeholder="Describe all tasks you completed today in detail. Be specific — include lesson names, number of students, documents processed, meetings attended, etc."
            data-counter="tasksCounter" required><?= sanitize($_POST['tasks_completed'] ?? '') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Minimum 20 characters required</span>
            <span class="char-counter" id="tasksCounter">0 chars</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Key Metrics / Achievements</label>
          <input type="text" name="key_metrics" class="form-control"
            placeholder="e.g. Taught 4 lessons, marked 35 papers, attended 2 meetings"
            value="<?= sanitize($_POST['key_metrics'] ?? '') ?>">
          <div class="form-helper">Quantifiable results — numbers help your supervisor assess performance!</div>
        </div>
        <div class="form-group">
          <label class="form-label">Challenges / Issues Faced <span style="color:var(--text-muted);font-weight:400;">(Optional)</span></label>
          <textarea name="challenges" class="form-control" rows="3"
            placeholder="Any obstacles, problems, or issues you encountered today..."
            data-counter="challengesCounter"><?= sanitize($_POST['challenges'] ?? '') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Optional but encouraged</span>
            <span class="char-counter" id="challengesCounter">0 chars</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Plan for Tomorrow <span style="color:var(--text-muted);font-weight:400;">(Optional)</span></label>
          <textarea name="tomorrow_plan" class="form-control" rows="3"
            placeholder="What do you plan to work on tomorrow?..."
            data-counter="tomorrowCounter"><?= sanitize($_POST['tomorrow_plan'] ?? '') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:4px;">
            <span class="form-helper">Helps your supervisor plan workload</span>
            <span class="char-counter" id="tomorrowCounter">0 chars</span>
          </div>
        </div>
        <div style="margin-top:8px;">
          <button type="button" class="btn btn-primary btn-full btn-lg" onclick="confirmSubmit()">
            <?= icon('send', 16) ?> Submit Daily Report
          </button>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:14px;font-size:12px;color:var(--text-muted);justify-content:center;">
          <?= icon('lock', 13) ?> Once submitted, reports are time-stamped and cannot be altered without supervisor approval.
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- RIGHT column -->
  <div>
    <!-- Submission Schedule card -->
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('calendar') ?> Submission Schedule</div>
      </div>
      <div class="card-body" style="padding:16px;">
        <?php
        $schedule  = ['Mon'=>true,'Tue'=>true,'Wed'=>true,'Thu'=>true,'Fri'=>true,'Sat'=>false,'Sun'=>false];
        $todayAbbr = date('D'); // e.g. "Mon"
        ?>
        <div style="display:flex;gap:5px;justify-content:space-between;margin-bottom:10px;">
          <?php foreach ($schedule as $d => $open):
            $active = (substr($todayAbbr, 0, 3) === $d);
          ?>
          <div style="text-align:center;flex:1;">
            <div style="
              height:34px;border-radius:6px;display:flex;align-items:center;justify-content:center;
              font-size:11px;font-weight:700;
              background:<?= $active ? 'var(--gold)' : ($open ? 'rgba(201,168,76,0.12)' : 'rgba(232,85,106,0.08)') ?>;
              color:<?= $active ? '#0d0f14' : ($open ? 'var(--gold)' : 'var(--danger)') ?>;
              border:1px solid <?= $active ? 'var(--gold)' : ($open ? 'rgba(201,168,76,0.25)' : 'rgba(232,85,106,0.2)') ?>;
            "><?= $d ?></div>
            <div style="font-size:9px;margin-top:3px;color:var(--text-muted);"><?= $open ? 'Open' : 'Off' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="font-size:11px;color:var(--text-muted);text-align:center;padding-top:6px;border-top:1px solid var(--border);">
          <?= $isWeekend ? '⚠️ Submissions closed today' : '✅ Submissions open today' ?>
        </div>
      </div>
    </div>

    <!-- Monthly Summary -->
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('bar-chart') ?> This Month's Summary</div>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
          <div class="metric-box"><div class="metric-box-num"><?= $totalMonth ?></div><div class="metric-box-label">Total</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--success);"><?= $approvedMonth ?></div><div class="metric-box-label">Approved</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--warning);"><?= $pendingMonth ?></div><div class="metric-box-label">Pending</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--danger);"><?= $rejectedMonth ?></div><div class="metric-box-label">Rejected</div></div>
        </div>
        <?php $rate = $totalMonth > 0 ? round(($approvedMonth / $totalMonth) * 100) : 0; ?>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;">Approval Rate</div>
        <div class="progress"><div class="progress-bar <?= $rate >= 70 ? 'green' : ($rate >= 40 ? 'gold' : 'red') ?>" style="width:<?= $rate ?>%"></div></div>
        <div style="text-align:right;font-size:12px;font-weight:600;color:var(--text-secondary);margin-top:4px;"><?= $rate ?>%</div>
      </div>
    </div>

    <!-- Recent Reports -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('clock') ?> Recent Reports</div>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Status</th><th>Reviewer</th></tr></thead>
          <tbody>
            <?php if (empty($recent)): ?>
            <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--text-muted);">No reports yet</td></tr>
            <?php else: ?>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td>
                <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= formatDate($r['report_date'], 'M d') ?></div>
                <div class="td-muted"><?= date('D', strtotime($r['report_date'])) ?></div>
              </td>
              <td><?= statusBadge($r['status']) ?></td>
              <td class="td-muted text-sm"><?= $r['rev_first'] ? sanitize($r['rev_first'][0] . '.' . $r['rev_last']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function confirmSubmit() {
  const tasks = document.querySelector('[name="tasks_completed"]').value.trim();
  if (tasks.length < 20) {
    alert('Please describe your tasks in more detail (minimum 20 characters).');
    return;
  }
  if (confirm('Are you sure you want to submit your daily report?\n\nOnce submitted, it cannot be edited without supervisor approval.')) {
    document.getElementById('reportForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
