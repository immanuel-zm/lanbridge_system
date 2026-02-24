<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect users who have a specific dashboard to their correct one
$_checkUser = currentUser();
$_destDash  = getRoleDashboard($_checkUser['role_slug'] ?? '', $_checkUser);
if ($_destDash !== 'staff_dashboard.php') {
    header('Location: ' . SITE_URL . '/portals/' . $_destDash); exit;
}

$pageTitle    = 'My Dashboard';
$pageSubtitle = date('l, F j, Y');
require_once __DIR__ . '/../includes/header.php';

$db         = getDB();
$user       = currentUser();
$uid        = (int)$user['id'];
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');
$period     = date('Y-m');

// Today's report
$todayReport = $db->prepare("SELECT * FROM reports WHERE user_id=? AND report_date=?");
$todayReport->execute([$uid, $today]);
$todayReport = $todayReport->fetch();

// Stats
$totalMonth    = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND report_date>='$monthStart'")->fetchColumn();
$approvedMonth = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='approved' AND report_date>='$monthStart'")->fetchColumn();
$pendingCount  = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='pending' AND report_date>='$monthStart'")->fetchColumn();
$rejectedMonth = (int)$db->query("SELECT COUNT(*) FROM reports WHERE user_id=$uid AND status='rejected' AND report_date>='$monthStart'")->fetchColumn();
$avgQuality    = (float)($db->query("SELECT ROUND(AVG(quality_score),1) FROM kpi_submissions WHERE user_id=$uid AND submission_date>='$monthStart' AND quality_score IS NOT NULL")->fetchColumn() ?? 0);
$activeDays    = (int)$db->query("SELECT COUNT(DISTINCT report_date) FROM reports WHERE user_id=$uid AND report_date>='$monthStart'")->fetchColumn();
$approvalRate  = $totalMonth > 0 ? round(($approvedMonth / $totalMonth) * 100) : 0;
$rateColor     = $approvalRate >= 70 ? 'green' : ($approvalRate >= 40 ? 'gold' : 'red');

// Personal scorecard
$scorecard = $db->prepare("SELECT * FROM performance_scorecards WHERE user_id=? AND period=?");
$scorecard->execute([$uid, $period]);
$scorecard = $scorecard->fetch();

// Dept rank
$deptRank = null;
if ($scorecard) {
    $deptTotal = (int)$db->query(
        "SELECT COUNT(*) FROM performance_scorecards WHERE department_id={$user['department_id']} AND period='$period'"
    )->fetchColumn();
    $deptRank = $scorecard['rank_in_dept'];
}

// Today's KPIs
$todayKpis = $db->prepare(
    "SELECT k.*, c.name AS cat_name FROM kpi_submissions k
     JOIN kpi_categories c ON k.category_id=c.id
     WHERE k.user_id=? AND k.submission_date=? ORDER BY k.created_at DESC"
);
$todayKpis->execute([$uid, $today]);
$todayKpis = $todayKpis->fetchAll();

// Recent reports
$recentReports = $db->prepare(
    "SELECT r.*, u.first_name AS rev_first, u.last_name AS rev_last
     FROM reports r LEFT JOIN users u ON r.approved_by=u.id
     WHERE r.user_id=? ORDER BY r.report_date DESC LIMIT 8"
);
$recentReports->execute([$uid]);
$recentReports = $recentReports->fetchAll();

// Heatmap — all submission dates this month
$heatDays = [];
$heatStmt = $db->prepare("SELECT report_date FROM reports WHERE user_id=? AND report_date LIKE ? ORDER BY report_date");
$heatStmt->execute([$uid, date('Y-m').'%']);
$heatSubmitted = array_flip(array_column($heatStmt->fetchAll(PDO::FETCH_COLUMN, 0), null));
// also get this month's KPI dates
$kpiDayStmt = $db->prepare("SELECT DISTINCT submission_date FROM kpi_submissions WHERE user_id=? AND submission_date LIKE ?");
$kpiDayStmt->execute([$uid, date('Y-m').'%']);
$kpiSubmitted = array_flip(array_column($kpiDayStmt->fetchAll(PDO::FETCH_COLUMN, 0), null));
$heatMonthStart = date('Y-m-01');
$heatDaysInMonth = (int)date('t');
$heatFirstDow = (int)date('N', strtotime($heatMonthStart)); // 1=Mon..7=Sun

// Streak calculation — consecutive weekdays submitted up to today
$streak = 0;
$streakDate = strtotime($today);
while (true) {
    $dow = (int)date('N', $streakDate);
    if ($dow >= 6) { $streakDate = strtotime('-1 day', $streakDate); continue; }
    $ds = date('Y-m-d', $streakDate);
    if ($ds < $heatMonthStart) break; // only check this month for streak
    if (isset($heatSubmitted[$ds])) { $streak++; $streakDate = strtotime('-1 day', $streakDate); }
    else break;
}

// Active announcements for this user
$deptId = (int)($user['department_id'] ?? 0);
$announcements = $db->prepare(
    "SELECT a.title, a.created_at, u.first_name, u.last_name FROM announcements a
     JOIN users u ON a.posted_by=u.id
     WHERE a.is_active=1 AND (a.audience='all' OR a.id IN (
         SELECT announcement_id FROM announcement_departments WHERE department_id=?
     ))
     ORDER BY a.created_at DESC LIMIT 3"
);
$announcements->execute([$deptId]);
$announcements = $announcements->fetchAll();

$greeting = getGreeting();
$dayOfWeek = (int)date('N');
$isWeekend = $dayOfWeek >= 6;
$isTodayHoliday = isHoliday($today);
$isOffDay = $isWeekend || $isTodayHoliday;
?>

<!-- Welcome Banner -->
<div class="welcome-banner mb-24">
  <div class="welcome-greeting"><?= $greeting ?>, <?= sanitize($user['first_name']) ?>.</div>
  <div class="welcome-msg">
    <?php if ($isOffDay): ?>
      <?php if ($isTodayHoliday): ?>
        🎉 Today is a public holiday or closure day — no submission required.
      <?php else: ?>
        🎉 Enjoy your weekend! Report submissions are closed today.
      <?php endif; ?>
    <?php elseif ($todayReport): ?>
      <?php if ($todayReport['status'] === 'approved'): ?>
        ✅ Your daily report has been approved today.
        <span class="badge badge-success" style="margin-left:6px;">Approved ✓</span>
      <?php elseif ($todayReport['status'] === 'pending'): ?>
        Report submitted and awaiting supervisor review.
        <span class="badge badge-warning" style="margin-left:6px;">Pending</span>
      <?php else: ?>
        Your report was rejected. Please review the feedback and resubmit.
        <span class="badge badge-danger" style="margin-left:6px;">Rejected</span>
      <?php endif; ?>
    <?php else: ?>
      ⚠️ You haven't submitted your daily report yet. Please do so before end of day.
    <?php endif; ?>
  </div>
  <div class="welcome-meta">
    <?= icon('map-pin',13) ?> <?= sanitize($user['dept_name'] ?? 'No Department') ?> &nbsp;·&nbsp;
    <?= icon('briefcase',13) ?> <?= sanitize($user['position'] ?? $user['role_name']) ?>
  </div>
  <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
    <?php if (!$isOffDay): ?>
    <?php if (!$todayReport): ?>
    <a href="submit_report.php" class="btn btn-primary"><?= icon('send',15) ?> Submit Today's Report</a>
    <?php else: ?>
    <a href="submit_report.php" class="btn btn-outline"><?= icon('file-text',15) ?> View My Report</a>
    <?php endif; ?>
    <a href="submit_kpi.php" class="btn btn-outline"><?= icon('bar-chart',15) ?> Submit KPI</a>
    <?php endif; ?>
    <a href="my_submissions.php" class="btn btn-outline"><?= icon('clock',15) ?> My History</a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $approvedMonth ?></div><div class="stat-label">Approved This Month</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta up"><?= $approvalRate ?>% approval rate</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= $pendingCount ?></div><div class="stat-label">Pending Review</div></div>
      <div class="stat-icon"><?= icon('clock',20) ?></div>
    </div>
    <div class="stat-delta">Awaiting supervisor</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $avgQuality ?: '—' ?></div><div class="stat-label">Avg Quality Score</div></div>
      <div class="stat-icon"><?= icon('bar-chart',20) ?></div>
    </div>
    <div class="stat-delta">Your KPI self-assessment avg</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $activeDays ?></div><div class="stat-label">Active Days</div></div>
      <div class="stat-icon"><?= icon('calendar',20) ?></div>
    </div>
    <div class="stat-delta"><?= $totalMonth ?> total reports this month</div>
  </div>
</div>

<!-- Submission Heatmap -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('calendar') ?> <?= date('F Y') ?> Submission Calendar</div>
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if ($streak > 0): ?>
      <span style="font-size:12px;color:var(--gold);font-weight:700;">🔥 <?= $streak ?>-day streak</span>
      <?php endif; ?>
      <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);">
        <span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:var(--success);"></span>Submitted
        <span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:var(--danger);margin-left:4px;"></span>Missed
        <span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:rgba(201,168,76,0.5);border:1px solid var(--gold);margin-left:4px;"></span>Holiday
        <span style="display:inline-block;width:11px;height:11px;border-radius:2px;background:var(--bg-elevated);border:1px solid var(--border);margin-left:4px;"></span>Weekend
      </div>
    </div>
  </div>
  <div class="card-body" style="padding:16px;">
    <!-- Day headers -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:4px;">
      <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dh): ?>
      <div style="text-align:center;font-size:10px;font-weight:700;color:var(--text-muted);padding:2px 0;"><?= $dh ?></div>
      <?php endforeach; ?>
    </div>
    <!-- Calendar cells -->
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
      <?php
      // Empty cells before month starts
      for ($pad = 1; $pad < $heatFirstDow; $pad++):
      ?><div></div><?php endfor;
      for ($day = 1; $day <= $heatDaysInMonth; $day++):
        $ds  = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
        $dow = (int)date('N', strtotime($ds));
        $isFuture  = $ds > $today;
        $isToday   = $ds === $today;
        $isWeekend = $dow >= 6;
        $isHol     = isHoliday($ds);
        $submitted = isset($heatSubmitted[$ds]);
        $hasKpi    = isset($kpiSubmitted[$ds]);
        $isMissed  = !$isWeekend && !$isHol && !$isFuture && !$submitted;

        if ($isToday) {
            $bg = $submitted ? 'var(--success)' : 'var(--gold)';
            $border = '2px solid var(--gold)';
            $textColor = '#fff';
        } elseif ($isHol) {
            $bg = 'rgba(201,168,76,0.5)';
            $border = '1px solid var(--gold)';
            $textColor = 'var(--gold)';
        } elseif ($isWeekend) {
            $bg = 'var(--bg-elevated)';
            $border = '1px solid var(--border)';
            $textColor = 'var(--text-muted)';
        } elseif ($isFuture) {
            $bg = 'transparent';
            $border = '1px dashed var(--border)';
            $textColor = 'var(--text-muted)';
        } elseif ($submitted) {
            $bg = 'rgba(45,212,160,0.2)';
            $border = '1px solid var(--success)';
            $textColor = 'var(--success)';
        } else {
            $bg = 'rgba(232,85,106,0.12)';
            $border = '1px solid rgba(232,85,106,0.3)';
            $textColor = 'var(--danger)';
        }
        $title = $isHol ? 'Holiday/Closure — no submission required' : ($isWeekend ? 'Weekend' : ($isFuture ? 'Upcoming' : ($submitted ? 'Submitted'.($hasKpi?' + KPI':'') : 'Missed')));
      ?>
      <div title="<?= $ds ?> — <?= $title ?>" style="background:<?= $bg ?>;border:<?= $border ?>;border-radius:5px;text-align:center;padding:5px 2px;font-size:11px;font-weight:<?= $isToday?'800':'600' ?>;color:<?= $textColor ?>;position:relative;cursor:default;min-height:28px;display:flex;align-items:center;justify-content:center;flex-direction:column;">
        <?= $day ?>
        <?php if ($hasKpi && $submitted): ?><div style="width:4px;height:4px;border-radius:50%;background:var(--gold);margin-top:1px;"></div><?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
    <div style="margin-top:10px;display:flex;gap:16px;font-size:11px;color:var(--text-muted);flex-wrap:wrap;">
      <span><?= count($heatSubmitted) ?> days submitted</span>
      <?php
        $missedCount = 0;
        for ($d2 = 1; $d2 <= $heatDaysInMonth; $d2++) {
            $ds2 = date('Y-m-') . str_pad($d2, 2, '0', STR_PAD_LEFT);
            $dow2 = (int)date('N', strtotime($ds2));
            if ($dow2 < 6 && $ds2 < $today && !isHoliday($ds2) && !isset($heatSubmitted[$ds2])) $missedCount++;
        }
      ?>
      <span style="color:<?= $missedCount>0?'var(--danger)':'var(--success)' ?>;"><?= $missedCount ?> weekdays missed</span>
      <span>🔵 Gold dot = KPI also submitted</span>
    </div>
  </div>
</div>

<!-- Main Grid -->
<div class="grid-2 mb-24">

  <!-- Personal Scorecard -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('award') ?> My Performance Scorecard</div>
      <span class="badge badge-muted"><?= date('F Y') ?></span>
    </div>
    <div class="card-body">
      <?php if ($scorecard): ?>
      <!-- Overall score circle -->
      <div style="text-align:center;margin-bottom:20px;">
        <?php
        $ov = (float)$scorecard['overall_score'];
        $ovColor = $ov >= 80 ? 'var(--success)' : ($ov >= 60 ? 'var(--warning)' : ($ov >= 40 ? '#f59e0b' : 'var(--danger))'));
        ?>
        <div style="display:inline-flex;flex-direction:column;align-items:center;justify-content:center;width:100px;height:100px;border-radius:50%;border:4px solid <?= $ovColor ?>;background:rgba(255,255,255,0.03);">
          <div style="font-size:28px;font-weight:800;color:<?= $ovColor ?>;"><?= number_format($ov,0) ?></div>
          <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">/ 100</div>
        </div>
        <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-top:8px;">Overall Score</div>
        <?php if ($deptRank): ?>
        <div style="font-size:12px;color:var(--text-muted);">Dept Rank: <strong style="color:var(--gold);">#<?= $deptRank ?></strong></div>
        <?php endif; ?>
      </div>
      <!-- Sub-scores -->
      <?php
      $metrics = [
          'report_score'      => ['Report Submission', 'file-text', '30%'],
          'kpi_score'         => ['KPI Quality',       'bar-chart', '30%'],
          'approval_rate'     => ['Approval Rate',     'check-square','25%'],
          'consistency_score' => ['Consistency',       'shield',    '15%'],
      ];
      foreach ($metrics as $key => [$label, $ic, $weight]):
        $val = (float)$scorecard[$key];
        $col = $val >= 80 ? 'var(--success)' : ($val >= 60 ? 'var(--warning)' : 'var(--danger)');
      ?>
      <div style="margin-bottom:10px;">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
          <span style="color:var(--text-secondary);"><?= icon($ic,12) ?> <?= $label ?> <span style="color:var(--text-muted);">(<?= $weight ?>)</span></span>
          <span style="font-weight:700;color:<?= $col ?>;"><?= number_format($val,0) ?></span>
        </div>
        <div class="progress" style="height:6px;">
          <div class="progress-bar <?= $val>=80?'green':($val>=60?'gold':'red') ?>" style="width:<?= $val ?>%;"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state" style="padding:28px 0;">
        <?= icon('award',36) ?>
        <h3 style="margin:12px 0 6px;font-size:15px;">No Scorecard Yet</h3>
        <p style="font-size:12px;color:var(--text-muted);">Your supervisor will generate monthly scorecards.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Monthly Performance + Today KPIs -->
  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Monthly -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('bar-chart') ?> Monthly Breakdown</div>
      </div>
      <div class="card-body">
        <div style="text-align:center;margin-bottom:14px;">
          <div style="font-size:36px;font-weight:800;color:var(--<?= $rateColor==='gold'?'gold':($rateColor==='green'?'success':'danger') ?>);"><?= $approvalRate ?>%</div>
          <div style="font-size:12px;color:var(--text-muted);">Approval Rate</div>
          <div class="progress" style="margin-top:8px;">
            <div class="progress-bar <?= $rateColor ?>" style="width:<?= $approvalRate ?>%"></div>
          </div>
        </div>
        <div class="metric-boxes">
          <div class="metric-box"><div class="metric-box-num"><?= $totalMonth ?></div><div class="metric-box-label">Total</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--success);"><?= $approvedMonth ?></div><div class="metric-box-label">Approved</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--warning);"><?= $pendingCount ?></div><div class="metric-box-label">Pending</div></div>
          <div class="metric-box"><div class="metric-box-num" style="color:var(--danger);"><?= $rejectedMonth ?></div><div class="metric-box-label">Rejected</div></div>
        </div>
      </div>
    </div>

    <!-- Today's KPIs -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><?= icon('send') ?> Today's KPI Entries</div>
        <a href="submit_kpi.php" class="btn btn-outline btn-sm"><?= icon('send',13) ?> Add</a>
      </div>
      <?php if (empty($todayKpis)): ?>
      <div class="card-body">
        <div class="empty-state" style="padding:16px 0;">
          <?= icon('inbox',28) ?>
          <p style="font-size:12px;">No KPIs submitted today</p>
          <a href="submit_kpi.php" class="btn btn-primary btn-sm" style="margin-top:8px;">Submit KPI</a>
        </div>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Category</th><th>Quality</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($todayKpis as $k): ?>
            <tr>
              <td class="td-bold" style="font-size:12px;"><?= sanitize($k['cat_name']) ?></td>
              <td><?= $k['quality_score'] ? $k['quality_score'].'%' : '—' ?></td>
              <td><?= statusBadge($k['status']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Announcements + Recent History -->
<div class="grid-2">

  <!-- Announcements -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('volume-2') ?> Recent Announcements</div>
      <a href="announcements.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if (empty($announcements)): ?>
    <div class="card-body">
      <div class="empty-state" style="padding:20px 0;"><?= icon('volume-2',28) ?><p style="font-size:12px;">No announcements</p></div>
    </div>
    <?php else: ?>
    <div class="card-body" style="padding:12px 16px;">
      <?php foreach ($announcements as $ann): ?>
      <div style="padding:10px 0;border-bottom:1px solid var(--border);">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:3px;">📢 <?= sanitize($ann['title']) ?></div>
        <div style="font-size:11px;color:var(--text-muted);">
          <?= sanitize($ann['first_name'].' '.$ann['last_name']) ?> · <?= timeAgo($ann['created_at']) ?>
        </div>
      </div>
      <?php endforeach; ?>
      <a href="announcements.php" style="display:block;text-align:center;font-size:12px;color:var(--gold);padding-top:10px;text-decoration:none;">View all announcements →</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Reports -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('clock') ?> Recent Reports</div>
      <a href="my_submissions.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Date</th><th>Summary</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recentReports)): ?>
          <tr><td colspan="3"><div class="empty-state" style="padding:20px;"><?= icon('file-text',24) ?><p>No reports yet</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($recentReports as $r): ?>
          <tr>
            <td>
              <div class="td-bold"><?= formatDate($r['report_date'],'M d') ?></div>
              <div class="td-muted"><?= date('D',strtotime($r['report_date'])) ?></div>
            </td>
            <td style="max-width:180px;">
              <div style="font-size:12px;color:var(--text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= sanitize(substr($r['tasks_completed'],0,60)) ?>
              </div>
            </td>
            <td><?= statusBadge($r['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
