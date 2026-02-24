<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(3);
$pageTitle    = 'Review Daily Reports';
$pageSubtitle = 'Approve or reject staff daily reports';
require_once __DIR__ . '/../includes/header.php';

$db         = getDB();
$user       = currentUser();
$roleLevel  = (int)$user['role_level'];
$uid        = (int)$user['id'];

// Handle approval/rejection via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    $reportId = (int)$_POST['report_id'];
    $action   = $_POST['action'];
    $comment  = trim($_POST['comment'] ?? '');

    if (in_array($action, ['approve','reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        // Verify this reviewer can see this report
        $checkStmt = $db->prepare("SELECT r.*, u.department_id FROM reports r JOIN users u ON r.user_id=u.id WHERE r.id=?");
        $checkStmt->execute([$reportId]);
        $report = $checkStmt->fetch();

        if ($report) {
            $db->prepare("UPDATE reports SET status=?, approval_comment=?, approved_by=?, updated_at=NOW() WHERE id=?")
               ->execute([$status, $comment, $uid, $reportId]);

            // Notify staff
            $notifType = $status === 'approved' ? 'success' : 'danger';
            $notifMsg  = $status === 'approved'
                ? 'Your daily report for ' . formatDate($report['report_date'], 'M d, Y') . ' has been approved. Great work!'
                : 'Your daily report for ' . formatDate($report['report_date'], 'M d, Y') . ' was rejected. Comment: ' . sanitize($comment);
            sendNotification($report['user_id'], 'Report ' . ucfirst($status), $notifMsg, $notifType, SITE_URL . '/portals/my_submissions.php');
            logActivity($uid, 'REPORT_' . strtoupper($status), 'Report ID ' . $reportId . ' ' . $status);

            setFlash($status === 'approved' ? 'success' : 'danger',
                $status === 'approved' ? '✅ Report approved successfully.' : '❌ Report rejected.');
        }
    }
    header('Location: vp_approvals.php' . (isset($_GET['status']) ? '?status='.$_GET['status'] : ''));
    exit;
}

// Filter by status
$filterStatus = $_GET['status'] ?? 'pending';
$validStatuses = ['pending','approved','rejected','all'];
if (!in_array($filterStatus, $validStatuses)) $filterStatus = 'pending';

// Build WHERE clause based on role
$whereRole  = $roleLevel <= 2 ? '1=1' : "u.department_id={$user['department_id']}";
$whereStatus = $filterStatus === 'all' ? '1=1' : "r.status='$filterStatus'";

// Count per status
$counts = [];
foreach (['pending','approved','rejected'] as $s) {
    $stmt = $db->query("SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id=u.id WHERE $whereRole AND r.status='$s'");
    $counts[$s] = (int)$stmt->fetchColumn();
}
$counts['all'] = array_sum($counts);

// Fetch reports
$reports = $db->query(
    "SELECT r.*, u.first_name, u.last_name, u.employee_id,
            d.name AS dept_name, d.code AS dept_code,
            rev.first_name AS rev_first, rev.last_name AS rev_last
     FROM reports r
     JOIN users u ON r.user_id=u.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users rev ON r.approved_by=rev.id
     WHERE $whereRole AND $whereStatus
     ORDER BY r.created_at DESC"
)->fetchAll();
?>

<!-- Status Filter Tabs -->
<div class="tab-row">
  <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All Reports'] as $s=>$label): ?>
  <a href="?status=<?= $s ?>" style="text-decoration:none;">
    <button class="tab-btn <?= $filterStatus===$s?'active':'' ?>">
      <?= $label ?>
      <?php if ($counts[$s] > 0): ?>
      <span class="tab-badge"><?= $counts[$s] ?></span>
      <?php endif; ?>
    </button>
  </a>
  <?php endforeach; ?>
</div>

<!-- Reports Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('file-text') ?> <?= ucfirst($filterStatus) ?> Reports <span class="badge badge-muted" style="margin-left:6px;"><?= count($reports) ?></span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Staff Member</th>
          <th>Department</th>
          <th>Date</th>
          <th>Tasks Summary</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
        <tr><td colspan="6">
          <div class="empty-state" style="padding:40px;">
            <?= icon('inbox',36) ?>
            <h3>No <?= $filterStatus ?> reports</h3>
            <p><?= $filterStatus==='pending' ? 'All reports have been reviewed!' : 'No reports match this filter.' ?></p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar avatar-sm"><?= getInitials($r['first_name'],$r['last_name']) ?></div>
              <div>
                <div class="td-bold"><?= sanitize($r['first_name'].' '.$r['last_name']) ?></div>
                <div class="td-muted"><?= sanitize($r['employee_id']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-muted"><?= sanitize($r['dept_code']??'—') ?></span>
          </td>
          <td>
            <div class="td-bold"><?= formatDate($r['report_date'],'M d, Y') ?></div>
            <div class="td-muted"><?= date('D', strtotime($r['report_date'])) ?></div>
          </td>
          <td style="max-width:240px;">
            <div class="truncate" style="color:var(--text-primary);font-size:13px;"><?= sanitize(substr($r['tasks_completed'],0,90)) ?>...</div>
            <?php if ($r['key_metrics']): ?>
            <div class="td-muted truncate"><?= sanitize($r['key_metrics']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?= statusBadge($r['status']) ?>
            <?php if ($r['rev_first']): ?>
            <div class="td-muted" style="margin-top:3px;font-size:11px;">by <?= sanitize($r['rev_first'].' '.$r['rev_last']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['status']==='pending'): ?>
            <button class="btn btn-primary btn-sm" onclick="openReviewModal(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)">
              <?= icon('check-square',13) ?> Review
            </button>
            <?php else: ?>
            <button class="btn btn-outline btn-sm" onclick="openReviewModal(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)">
              <?= icon('file-text',13) ?> View
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModal">
  <div class="modal" style="max-width:580px;">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalTitle">Review Daily Report</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="modalSubtitle"></div>
      </div>
      <button class="modal-close" onclick="closeModal('reviewModal')"><?= icon('x',18) ?></button>
    </div>
    <div class="modal-body">

      <div style="margin-bottom:14px;">
        <div class="form-label">Tasks Completed</div>
        <div id="modalTasks" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;font-size:13.5px;color:var(--text-primary);line-height:1.7;max-height:150px;overflow-y:auto;white-space:pre-wrap;"></div>
      </div>

      <div id="modalMetricsWrap" style="margin-bottom:14px;display:none;">
        <div class="form-label">Key Metrics</div>
        <div id="modalMetrics" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:13px;color:var(--text-secondary);"></div>
      </div>

      <div id="modalChallengesWrap" style="margin-bottom:14px;display:none;">
        <div class="form-label">Challenges</div>
        <div id="modalChallenges" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:13px;color:var(--text-secondary);"></div>
      </div>

      <div id="modalTomorrowWrap" style="margin-bottom:14px;display:none;">
        <div class="form-label">Plan for Tomorrow</div>
        <div id="modalTomorrow" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-size:13px;color:var(--text-secondary);"></div>
      </div>

      <div id="modalCommentWrap">
        <label class="form-label">Reviewer Comment</label>
        <textarea id="modalComment" class="form-control" rows="3" placeholder="Add a comment (required when rejecting)..."></textarea>
      </div>

      <div id="modalStatusInfo" style="display:none;margin-top:12px;"></div>

    </div>
    <div class="modal-footer" id="modalFooter">
      <button class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
      <button class="btn btn-danger" id="rejectBtn" onclick="submitReview('reject')">
        <?= icon('x',14) ?> Reject
      </button>
      <button class="btn btn-success" id="approveBtn" onclick="submitReview('approve')">
        <?= icon('check-square',14) ?> Approve
      </button>
    </div>
  </div>
</div>

<!-- Hidden form for submission -->
<form method="POST" id="reviewForm" style="display:none;">
  <input type="hidden" name="report_id" id="reviewReportId">
  <input type="hidden" name="action"    id="reviewAction">
  <input type="hidden" name="comment"   id="reviewComment">
</form>

<script>
let currentReportId = null;
let currentStatus   = null;

function openReviewModal(id, report) {
  currentReportId = id;
  currentStatus   = report.status;

  document.getElementById('modalTitle').textContent    = report.status === 'pending' ? 'Review Daily Report' : 'View Daily Report';
  document.getElementById('modalSubtitle').textContent = report.first_name + ' ' + report.last_name + ' — ' + report.report_date;
  document.getElementById('modalTasks').textContent    = report.tasks_completed;

  const metricsWrap = document.getElementById('modalMetricsWrap');
  if (report.key_metrics) {
    document.getElementById('modalMetrics').textContent = report.key_metrics;
    metricsWrap.style.display = 'block';
  } else { metricsWrap.style.display = 'none'; }

  const challWrap = document.getElementById('modalChallengesWrap');
  if (report.challenges) {
    document.getElementById('modalChallenges').textContent = report.challenges;
    challWrap.style.display = 'block';
  } else { challWrap.style.display = 'none'; }

  const tomWrap = document.getElementById('modalTomorrowWrap');
  if (report.tomorrow_plan) {
    document.getElementById('modalTomorrow').textContent = report.tomorrow_plan;
    tomWrap.style.display = 'block';
  } else { tomWrap.style.display = 'none'; }

  document.getElementById('modalComment').value = report.approval_comment || '';

  // Show/hide action buttons based on status
  const footer       = document.getElementById('modalFooter');
  const commentWrap  = document.getElementById('modalCommentWrap');
  const statusInfo   = document.getElementById('modalStatusInfo');

  if (report.status === 'pending') {
    footer.style.display      = 'flex';
    commentWrap.style.display = 'block';
    statusInfo.style.display  = 'none';
    document.getElementById('approveBtn').style.display = 'inline-flex';
    document.getElementById('rejectBtn').style.display  = 'inline-flex';
  } else {
    document.getElementById('approveBtn').style.display = 'none';
    document.getElementById('rejectBtn').style.display  = 'none';
    commentWrap.style.display = 'none';
    if (report.approval_comment) {
      statusInfo.style.display  = 'block';
      statusInfo.innerHTML = '<div class="alert ' + (report.status==='approved'?'alert-success':'alert-danger') + '">' + (report.approval_comment || 'No comment') + '</div>';
    }
  }

  openModal('reviewModal');
}

function submitReview(action) {
  const comment = document.getElementById('modalComment').value.trim();
  if (action === 'reject' && !comment) {
    alert('Please provide a comment explaining why this report is being rejected.');
    document.getElementById('modalComment').focus();
    return;
  }
  const msg = action === 'approve'
    ? 'Approve this report? The staff member will be notified.'
    : 'Reject this report? You must provide a reason.';
  if (!confirm(msg)) return;

  document.getElementById('reviewReportId').value = currentReportId;
  document.getElementById('reviewAction').value   = action;
  document.getElementById('reviewComment').value  = comment;
  document.getElementById('reviewForm').submit();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
