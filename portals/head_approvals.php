<?php
// ============================================================
// head_approvals.php — Department Head approval queue
// Features: single approve/reject, bulk approve, rejection templates
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(6);

$db     = getDB();
$user   = currentUser();
$uid    = (int)$user['id'];
$deptId = (int)$user['department_id'];

// ── POST Handler (must be BEFORE header.php) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Single approve / reject ───────────────────────────────
    if (in_array($action, ['approve', 'reject']) && isset($_POST['report_id'])) {
        $reportId = (int)$_POST['report_id'];
        $comment  = trim($_POST['comment'] ?? '');
        $status   = $action === 'approve' ? 'approved' : 'rejected';

        if ($action === 'reject' && $comment === '') {
            setFlash('danger', '❌ A rejection reason is required.');
        } else {
            $check = $db->prepare("SELECT r.* FROM reports r JOIN users u ON r.user_id=u.id WHERE r.id=? AND u.department_id=?");
            $check->execute([$reportId, $deptId]);
            $report = $check->fetch();

            if ($report) {
                $db->prepare("UPDATE reports SET status=?, approval_comment=?, approved_by=?, updated_at=NOW() WHERE id=?")
                   ->execute([$status, $comment, $uid, $reportId]);

                $notifType = $status === 'approved' ? 'success' : 'danger';
                $notifMsg  = $status === 'approved'
                    ? 'Your daily report for ' . formatDate($report['report_date'], 'M d, Y') . ' has been approved!'
                    : 'Your daily report was rejected. Reason: ' . sanitize($comment);
                sendNotification($report['user_id'], 'Report ' . ucfirst($status), $notifMsg, $notifType, SITE_URL . '/portals/my_submissions.php');
                logActivity($uid, 'REPORT_' . strtoupper($status), 'Report ID ' . $reportId . ' ' . $status . ' by dept head');

                setFlash($status === 'approved' ? 'success' : 'danger',
                    $status === 'approved' ? '✅ Report approved.' : '❌ Report rejected.');
            }
        }
        $qs = ($_GET['status'] ?? '') ? '?status=' . $_GET['status'] : '';
        header('Location: head_approvals.php' . $qs);
        exit;
    }

    // ── Bulk approve ──────────────────────────────────────────
    if ($action === 'bulk_approve' && !empty($_POST['report_ids'])) {
        $ids      = array_map('intval', (array)$_POST['report_ids']);
        $comment  = trim($_POST['bulk_comment'] ?? '');
        $approved = 0;

        foreach ($ids as $reportId) {
            $check = $db->prepare("SELECT r.* FROM reports r JOIN users u ON r.user_id=u.id WHERE r.id=? AND u.department_id=? AND r.status='pending'");
            $check->execute([$reportId, $deptId]);
            $report = $check->fetch();

            if ($report) {
                $db->prepare("UPDATE reports SET status='approved', approval_comment=?, approved_by=?, updated_at=NOW() WHERE id=?")
                   ->execute([$comment ?: null, $uid, $reportId]);

                sendNotification(
                    $report['user_id'],
                    'Report Approved',
                    'Your daily report for ' . formatDate($report['report_date'], 'M d, Y') . ' has been approved.',
                    'success',
                    SITE_URL . '/portals/my_submissions.php'
                );
                logActivity($uid, 'REPORT_APPROVED', 'Bulk approved report ID ' . $reportId);
                $approved++;
            }
        }

        setFlash('success', '✅ ' . $approved . ' report' . ($approved !== 1 ? 's' : '') . ' approved successfully.');
        header('Location: head_approvals.php?status=pending');
        exit;
    }

    header('Location: head_approvals.php');
    exit;
}

// ── Page setup ────────────────────────────────────────────────
$pageTitle    = 'Review Department Reports';
$pageSubtitle = 'Approve or reject your team\'s daily reports';
require_once __DIR__ . '/../includes/header.php';

// ── Flash ─────────────────────────────────────────────────────
$flash = getFlash();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss style="margin-bottom:18px;">
  <?= sanitize($flash['message']) ?>
</div>
<?php endif; ?>

<?php
$filterStatus  = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filterStatus, $validStatuses)) $filterStatus = 'pending';
$whereStatus = $filterStatus === 'all' ? '1=1' : "r.status='$filterStatus'";

$counts = [];
foreach (['pending', 'approved', 'rejected'] as $s) {
    $stmt = $db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE u.department_id=$deptId AND r.status='$s'");
    $counts[$s] = (int)$stmt->fetchColumn();
}
$counts['all'] = array_sum($counts);

$reports    = $db->query(
    "SELECT r.*, u.first_name, u.last_name, u.employee_id, u.avatar,
            d.name AS dept_name, d.code AS dept_code,
            rev.first_name AS rev_first, rev.last_name AS rev_last
     FROM reports r
     JOIN users u ON r.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users rev ON r.approved_by=rev.id
     WHERE u.department_id=$deptId AND $whereStatus
     ORDER BY r.created_at DESC"
)->fetchAll();

$hasPending = $filterStatus === 'pending' && !empty($reports);

$rejectionTemplates = [
    'Insufficient detail — please elaborate on the tasks completed.',
    'Missing key metrics — quantitative data is required for this report.',
    'Report is too brief — please document at least 3 specific tasks.',
    'Please include your plan for tomorrow in future reports.',
    'This report appears to be a duplicate of a previous submission.',
];
?>

<!-- Status Filter Tabs -->
<div class="tab-row">
  <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $s => $label): ?>
  <a href="?status=<?= $s ?>" style="text-decoration:none;">
    <button class="tab-btn <?= $filterStatus === $s ? 'active' : '' ?>">
      <?= $label ?>
      <?php if ($counts[$s] > 0): ?>
      <span class="tab-badge"><?= $counts[$s] ?></span>
      <?php endif; ?>
    </button>
  </a>
  <?php endforeach; ?>
</div>

<!-- Bulk Toolbar (pending tab only, when rows exist) -->
<?php if ($hasPending): ?>
<div id="bulkToolbar" style="background:var(--bg-card);border:1px solid var(--gold-border);border-radius:var(--radius);padding:12px 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);user-select:none;">
    <input type="checkbox" id="selectAll" style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
    Select All
  </label>
  <div style="font-size:13px;color:var(--text-muted);">
    <span id="selectionCount">0</span> selected
  </div>
  <div style="flex:1;"></div>
  <button id="bulkApproveBtn" type="button" class="btn btn-success btn-sm"
    onclick="openBulkModal()" disabled style="opacity:0.4;transition:opacity .2s;">
    <?= icon('check-square', 14) ?> Approve Selected
  </button>
</div>
<?php endif; ?>

<!-- Reports Table -->
<form method="POST" id="bulkForm">
  <input type="hidden" name="action" value="bulk_approve">
  <input type="hidden" name="bulk_comment" id="bulkCommentInput">

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <?= icon('file-text') ?>
        <?= ucfirst($filterStatus) ?> Reports
        <span class="badge badge-muted" style="margin-left:6px;"><?= count($reports) ?></span>
      </div>
      <?php if ($hasPending && count($reports) > 0): ?>
      <div style="font-size:12px;color:var(--text-muted);">Tick rows to bulk approve</div>
      <?php endif; ?>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <?php if ($hasPending): ?><th style="width:42px;"></th><?php endif; ?>
            <th>Staff Member</th>
            <th>Date</th>
            <th>Tasks Summary</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($reports)): ?>
          <tr><td colspan="<?= $hasPending ? 6 : 5 ?>">
            <div class="empty-state" style="padding:48px;">
              <?= icon('inbox', 36) ?>
              <h3><?= $filterStatus === 'pending' ? 'No pending reports — all reviewed!' : 'No reports match this filter.' ?></h3>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($reports as $r): ?>
          <tr class="report-row" data-id="<?= $r['id'] ?>" style="transition:background .15s;">
            <?php if ($hasPending): ?>
            <td style="text-align:center;padding:10px 8px;">
              <input type="checkbox" name="report_ids[]" value="<?= $r['id'] ?>"
                class="row-checkbox"
                style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
            </td>
            <?php endif; ?>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <?= avatarHtml($r, 'sm') ?>
                <div>
                  <div class="td-bold"><?= sanitize($r['first_name'] . ' ' . $r['last_name']) ?></div>
                  <div class="td-muted"><?= sanitize($r['employee_id']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <div class="td-bold"><?= formatDate($r['report_date'], 'M d, Y') ?></div>
              <div class="td-muted"><?= date('D', strtotime($r['report_date'])) ?></div>
            </td>
            <td style="max-width:260px;">
              <div style="color:var(--text-primary);font-size:13px;line-height:1.5;"><?= sanitize(substr($r['tasks_completed'], 0, 100)) ?>...</div>
              <?php if ($r['key_metrics']): ?>
              <div class="td-muted truncate"><?= sanitize(substr($r['key_metrics'], 0, 60)) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?= statusBadge($r['status']) ?>
              <?php if ($r['rev_first']): ?>
              <div class="td-muted" style="font-size:11px;margin-top:3px;">by <?= sanitize($r['rev_first'] . ' ' . $r['rev_last']) ?></div>
              <?php endif; ?>
              <?php if ($r['approval_comment'] && $r['status'] !== 'pending'): ?>
              <div class="td-muted" style="font-size:11px;margin-top:2px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitize($r['approval_comment']) ?>">
                "<?= sanitize(substr($r['approval_comment'], 0, 40)) ?>"
              </div>
              <?php endif; ?>
            </td>
            <td>
              <button type="button"
                class="btn <?= $r['status'] === 'pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm"
                onclick="openReviewModal(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                <?= $r['status'] === 'pending' ? icon('check-square', 13) . ' Review' : icon('file-text', 13) . ' View' ?>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<!-- ── Review Modal ───────────────────────────────────────── -->
<div class="modal-overlay" id="reviewModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalTitle">Review Report</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="modalSubtitle"></div>
      </div>
      <button class="modal-close" onclick="closeModal('reviewModal')"><?= icon('x', 18) ?></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:14px;">
        <div class="form-label">Tasks Completed</div>
        <div id="modalTasks" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;font-size:13.5px;color:var(--text-primary);line-height:1.7;max-height:160px;overflow-y:auto;white-space:pre-wrap;"></div>
      </div>
      <div id="modalMetricsWrap" style="margin-bottom:14px;display:none;">
        <div class="form-label">Key Metrics</div>
        <div id="modalMetrics" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:13px;color:var(--text-secondary);"></div>
      </div>
      <div id="modalChallengesWrap" style="margin-bottom:14px;display:none;">
        <div class="form-label">Challenges</div>
        <div id="modalChallenges" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:13px;color:var(--text-secondary);"></div>
      </div>

      <!-- Rejection Templates (shown only for pending reports) -->
      <div id="rejectionTemplatesWrap" style="display:none;margin-bottom:10px;">
        <div class="form-label" style="margin-bottom:7px;">
          <?= icon('x-circle', 13) ?> Quick Rejection Reasons
          <span style="font-size:11px;color:var(--text-muted);font-weight:400;margin-left:4px;">(click to fill)</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;">
          <?php foreach ($rejectionTemplates as $t): ?>
          <button type="button"
            onclick="applyTemplate(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)"
            style="text-align:left;background:rgba(232,85,106,0.07);border:1px solid rgba(232,85,106,0.3);border-radius:6px;padding:7px 11px;font-size:12px;color:var(--danger);cursor:pointer;transition:background .15s;"
            onmouseover="this.style.background='rgba(232,85,106,0.14)'" onmouseout="this.style.background='rgba(232,85,106,0.07)'">
            <?= htmlspecialchars($t) ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="modalCommentWrap">
        <label class="form-label">
          Reviewer Comment
          <span id="commentRequired" style="color:var(--danger);display:none;margin-left:3px;">* required for rejection</span>
        </label>
        <textarea id="modalComment" class="form-control" rows="3"
          placeholder="Add a comment (optional for approval, required when rejecting)…"
          style="resize:vertical;"></textarea>
      </div>
      <div id="modalStatusInfo" style="display:none;margin-top:12px;"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
      <button class="btn btn-danger"  id="rejectBtn"  onclick="submitReview('reject')"><?= icon('x', 14) ?> Reject</button>
      <button class="btn btn-success" id="approveBtn" onclick="submitReview('approve')"><?= icon('check-square', 14) ?> Approve</button>
    </div>
  </div>
</div>

<!-- ── Bulk Approve Confirmation Modal ───────────────────── -->
<div class="modal-overlay" id="bulkModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div>
        <div class="modal-title"><?= icon('check-square', 18) ?> Bulk Approve Reports</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="bulkModalSubtitle"></div>
      </div>
      <button class="modal-close" onclick="closeModal('bulkModal')"><?= icon('x', 18) ?></button>
    </div>
    <div class="modal-body">
      <div id="bulkPreviewList" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;max-height:160px;overflow-y:auto;margin-bottom:14px;"></div>
      <div style="margin-bottom:14px;">
        <label class="form-label">Optional Comment (applied to all approved reports)</label>
        <textarea id="bulkCommentArea" class="form-control" rows="2"
          placeholder="e.g. Reviewed and approved in bulk — end of day review…"></textarea>
      </div>
      <div class="alert alert-info" style="font-size:13px;display:flex;align-items:flex-start;gap:8px;">
        <?= icon('info', 14) ?>
        <span>Each staff member will receive an individual approval notification.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('bulkModal')">Cancel</button>
      <button class="btn btn-success" onclick="submitBulk()">
        <?= icon('check-square', 14) ?> Approve All Selected
      </button>
    </div>
  </div>
</div>

<!-- Hidden single form -->
<form method="POST" id="reviewForm" style="display:none;">
  <input type="hidden" name="report_id" id="reviewReportId">
  <input type="hidden" name="action"    id="reviewAction">
  <input type="hidden" name="comment"   id="reviewComment">
</form>

<script>
// ── Single review ─────────────────────────────────────────────
let currentReportId = null;

function openReviewModal(id, report) {
  currentReportId = id;
  document.getElementById('modalTitle').textContent    = report.status === 'pending' ? 'Review Daily Report' : 'View Report';
  document.getElementById('modalSubtitle').textContent = report.first_name + ' ' + report.last_name + ' — ' + report.report_date;
  document.getElementById('modalTasks').textContent    = report.tasks_completed;

  const mw = document.getElementById('modalMetricsWrap');
  if (report.key_metrics) { document.getElementById('modalMetrics').textContent = report.key_metrics; mw.style.display = 'block'; }
  else mw.style.display = 'none';

  const cw = document.getElementById('modalChallengesWrap');
  if (report.challenges) { document.getElementById('modalChallenges').textContent = report.challenges; cw.style.display = 'block'; }
  else cw.style.display = 'none';

  document.getElementById('modalComment').value = report.approval_comment || '';

  const isPending = report.status === 'pending';
  document.getElementById('approveBtn').style.display             = isPending ? 'inline-flex' : 'none';
  document.getElementById('rejectBtn').style.display              = isPending ? 'inline-flex' : 'none';
  document.getElementById('rejectionTemplatesWrap').style.display = isPending ? 'block' : 'none';
  document.getElementById('commentRequired').style.display        = isPending ? 'inline' : 'none';

  const si = document.getElementById('modalStatusInfo');
  if (!isPending) {
    const cls = report.status === 'approved' ? 'alert-success' : 'alert-danger';
    const msg = report.approval_comment ? safeText(report.approval_comment) : 'Report was ' + report.status + '.';
    si.style.display = 'block';
    si.innerHTML = '<div class="alert ' + cls + '">' + msg + '</div>';
  } else si.style.display = 'none';

  openModal('reviewModal');
}

function safeText(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function applyTemplate(text) {
  document.getElementById('modalComment').value = text;
  document.getElementById('modalComment').focus();
}

function submitReview(action) {
  const comment = document.getElementById('modalComment').value.trim();
  if (action === 'reject' && !comment) {
    alert('A rejection reason is required. Select a quick template above or type your own.');
    return;
  }
  const msg = action === 'approve' ? 'Approve this report?' : 'Reject this report? This will notify the staff member.';
  if (!confirm(msg)) return;
  document.getElementById('reviewReportId').value = currentReportId;
  document.getElementById('reviewAction').value   = action;
  document.getElementById('reviewComment').value  = comment;
  document.getElementById('reviewForm').submit();
}

// ── Bulk select logic ─────────────────────────────────────────
const selectAllCb    = document.getElementById('selectAll');
const bulkApproveBtn = document.getElementById('bulkApproveBtn');
const countSpan      = document.getElementById('selectionCount');

function getChecked() {
  return [...document.querySelectorAll('.row-checkbox:checked')];
}

function updateToolbar() {
  const n   = getChecked().length;
  const all = document.querySelectorAll('.row-checkbox');
  if (countSpan) countSpan.textContent = n;
  if (bulkApproveBtn) {
    bulkApproveBtn.disabled      = n === 0;
    bulkApproveBtn.style.opacity = n === 0 ? '0.4' : '1';
  }
  if (selectAllCb) {
    selectAllCb.indeterminate = n > 0 && n < all.length;
    selectAllCb.checked       = n > 0 && n === all.length;
  }
}

if (selectAllCb) {
  selectAllCb.addEventListener('change', function () {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
    highlightAll();
    updateToolbar();
  });
}

document.querySelectorAll('.row-checkbox').forEach(cb => {
  cb.addEventListener('change', function () {
    this.closest('tr').style.background = this.checked ? 'rgba(201,168,76,0.07)' : '';
    updateToolbar();
  });
});

function highlightAll() {
  const checked = selectAllCb && selectAllCb.checked;
  document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.closest('tr').style.background = checked ? 'rgba(201,168,76,0.07)' : '';
  });
}

// ── Bulk approve modal ────────────────────────────────────────
function openBulkModal() {
  const checked = getChecked();
  if (!checked.length) return;

  document.getElementById('bulkModalSubtitle').textContent =
    checked.length + ' report' + (checked.length !== 1 ? 's' : '') + ' selected';

  const list = document.getElementById('bulkPreviewList');
  list.innerHTML = '';
  checked.forEach(cb => {
    const row  = cb.closest('tr');
    const cols = row.querySelectorAll('.td-bold');
    const name = cols[0]?.textContent?.trim() ?? '';
    const date = cols[1]?.textContent?.trim() ?? '';
    const item = document.createElement('div');
    item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--border);font-size:13px;';
    item.innerHTML = '<span style="color:var(--success);font-size:15px;">✓</span> <strong>' +
      safeText(name) + '</strong><span style="color:var(--text-muted);margin-left:4px;">— ' + safeText(date) + '</span>';
    list.appendChild(item);
  });

  document.getElementById('bulkCommentArea').value = '';
  openModal('bulkModal');
}

function submitBulk() {
  const checked = getChecked();
  if (!checked.length) return;
  if (!confirm('Approve ' + checked.length + ' report(s)? Each staff member will be notified.')) return;
  document.getElementById('bulkCommentInput').value = document.getElementById('bulkCommentArea').value.trim();
  document.getElementById('bulkForm').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
