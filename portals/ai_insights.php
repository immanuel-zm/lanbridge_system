<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireRole(3); // CEO, Principal, VP, Finance Admin, Bursar, IT Admin can view

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── Handle actions ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Mark insight as reviewed
    if ($action === 'review_insight') {
        $insightId  = (int)($_POST['insight_id'] ?? 0);
        $reviewNote = trim($_POST['review_note'] ?? '');
        if ($insightId) {
            $db->prepare(
                "UPDATE ai_insights SET is_reviewed=1, reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?"
            )->execute([$uid, $reviewNote, $insightId]);
            logActivity($uid, 'AI_INSIGHT_REVIEWED', 'Reviewed insight ID ' . $insightId);
            setFlash('success', '✅ Insight marked as reviewed.');
        }
        header('Location: ai_insights.php'); exit;
    }

    // Run detection now
    if ($action === 'run_detection') {
        $engine = new AIEngine($db);
        $engine->runAll();
        setFlash('success', '✅ AI detection run complete. New insights generated.');
        header('Location: ai_insights.php'); exit;
    }

    // Generate scorecards
    if ($action === 'generate_scorecards') {
        $engine = new AIEngine($db);
        $count  = $engine->generateScorecards();
        setFlash('success', "✅ Performance scorecards generated for {$count} staff members.");
        header('Location: ai_insights.php'); exit;
    }
}

$pageTitle    = 'AI Insights & Fraud Detection';
$pageSubtitle = 'Pattern detection and anomaly alerts';
require_once __DIR__ . '/../includes/header.php';

// ── Fetch insights ────────────────────────────────────────────
$engine  = new AIEngine($db);
$deptFilter = (int)($_GET['dept'] ?? 0);
$showReviewed = ($_GET['show'] ?? 'active') === 'reviewed';

$whereBase = $showReviewed ? "i.is_reviewed = 1" : "i.is_reviewed = 0";
if ($deptFilter) {
    $whereBase .= " AND (i.department_id = $deptFilter OR i.department_id IS NULL)";
}

$insights = $db->query(
    "SELECT i.*, u.first_name, u.last_name, d.name AS dept_name,
            ru.first_name AS rev_first, ru.last_name AS rev_last
     FROM ai_insights i
     LEFT JOIN users u ON i.user_id = u.id
     LEFT JOIN departments d ON i.department_id = d.id
     LEFT JOIN users ru ON i.reviewed_by = ru.id
     WHERE $whereBase
     ORDER BY FIELD(i.severity,'critical','warning','info'), i.created_at DESC
     LIMIT 50"
)->fetchAll();

$stats = [
    'critical' => (int)$db->query("SELECT COUNT(*) FROM ai_insights WHERE is_reviewed=0 AND severity='critical'")->fetchColumn(),
    'warning'  => (int)$db->query("SELECT COUNT(*) FROM ai_insights WHERE is_reviewed=0 AND severity='warning'")->fetchColumn(),
    'info'     => (int)$db->query("SELECT COUNT(*) FROM ai_insights WHERE is_reviewed=0 AND severity='info'")->fetchColumn(),
    'reviewed' => (int)$db->query("SELECT COUNT(*) FROM ai_insights WHERE is_reviewed=1")->fetchColumn(),
];

$byType = $db->query(
    "SELECT insight_type, COUNT(*) AS cnt FROM ai_insights WHERE is_reviewed=0 GROUP BY insight_type ORDER BY cnt DESC"
)->fetchAll();

$allDepts = $db->query("SELECT id, name, code FROM departments ORDER BY name")->fetchAll();

// Severity styles
function severityStyle(string $sev): array {
    return match($sev) {
        'critical' => ['color' => 'var(--danger)',  'bg' => 'rgba(232,85,106,0.08)',  'border' => 'rgba(232,85,106,0.35)', 'icon' => '🚨', 'badge' => 'badge-danger'],
        'warning'  => ['color' => 'var(--warning)', 'bg' => 'rgba(245,166,35,0.08)',  'border' => 'rgba(245,166,35,0.35)', 'icon' => '⚠️', 'badge' => 'badge-warning'],
        default    => ['color' => 'var(--info)',    'bg' => 'rgba(74,158,219,0.08)',   'border' => 'rgba(74,158,219,0.25)', 'icon' => 'ℹ️', 'badge' => 'badge-info'],
    };
}

function insightTypeLabel(string $type): string {
    return match($type) {
        'COPY_PASTE_REPORT'      => 'Copy-Paste Reports',
        'MISSING_SUBMISSIONS'    => 'Missing Submissions',
        'KPI_SCORE_INFLATION'    => 'KPI Score Inflation',
        'BUDGET_OVERRUN_RISK'    => 'Budget Overrun Risk',
        'LOW_APPROVAL_RATE'      => 'Low Approval Rate',
        'IT_SLA_BREACH'          => 'IT SLA Breach',
        'DUPLICATE_TRANSACTION'  => 'Duplicate Transaction',
        'HIGH_OUTSTANDING_FEES'  => 'Outstanding Fees',
        default                  => str_replace('_', ' ', $type),
    };
}
?>

<!-- ── STAT CARDS ─────────────────────────────────────────────── -->
<div class="stat-grid">
  <div class="stat-card" style="--card-accent:#e8556a;">
    <div class="stat-top">
      <div><div class="stat-number"><?= $stats['critical'] ?></div><div class="stat-label">Critical Alerts</div></div>
      <div class="stat-icon" style="font-size:24px;">🚨</div>
    </div>
    <div class="stat-delta down">Requires immediate action</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= $stats['warning'] ?></div><div class="stat-label">Warnings</div></div>
      <div class="stat-icon" style="font-size:24px;">⚠️</div>
    </div>
    <div class="stat-delta">Needs investigation</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $stats['info'] ?></div><div class="stat-label">Info Notices</div></div>
      <div class="stat-icon"><?= icon('info', 20) ?></div>
    </div>
    <div class="stat-delta up">Low priority flags</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $stats['reviewed'] ?></div><div class="stat-label">Total Reviewed</div></div>
      <div class="stat-icon"><?= icon('check-square', 20) ?></div>
    </div>
    <div class="stat-delta up">Human-validated insights</div>
  </div>
</div>

<!-- ── CONTROLS ───────────────────────────────────────────────── -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:center;">
  <!-- Run detection -->
  <form method="POST" style="display:inline;">
    <button type="submit" name="action" value="run_detection" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Run AI Detection Now
    </button>
  </form>
  <form method="POST" style="display:inline;">
    <button type="submit" name="action" value="generate_scorecards" class="btn btn-outline">
      <?= icon('bar-chart', 14) ?> Generate Scorecards
    </button>
  </form>

  <!-- Filter bar -->
  <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <select class="form-control" style="height:34px;font-size:12px;padding:0 10px;width:auto;"
      onchange="window.location='ai_insights.php?dept='+this.value+'&show=<?= $showReviewed ? 'reviewed' : 'active' ?>'">
      <option value="0">All Departments</option>
      <?php foreach ($allDepts as $d): ?>
      <option value="<?= $d['id'] ?>" <?= $deptFilter === (int)$d['id'] ? 'selected' : '' ?>>
        <?= sanitize($d['code']) ?> — <?= sanitize($d['name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <div class="tab-row">
      <a href="?dept=<?= $deptFilter ?>&show=active" class="tab-btn <?= !$showReviewed ? 'active' : '' ?>">Active (<?= $stats['critical'] + $stats['warning'] + $stats['info'] ?>)</a>
      <a href="?dept=<?= $deptFilter ?>&show=reviewed" class="tab-btn <?= $showReviewed ? 'active' : '' ?>">Reviewed (<?= $stats['reviewed'] ?>)</a>
    </div>
  </div>
</div>

<div class="grid-2 mb-24" style="grid-template-columns:1fr 280px;">

  <!-- ── INSIGHTS LIST ──────────────────────────────────────── -->
  <div>
    <?php if (empty($insights)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state" style="padding:60px 0;">
          <?= icon('shield', 48) ?>
          <h3 style="margin:16px 0 8px;font-size:18px;">
            <?= $showReviewed ? 'No Reviewed Insights' : '✅ No Active Alerts' ?>
          </h3>
          <p style="color:var(--text-muted);font-size:13px;">
            <?= $showReviewed
              ? 'No insights have been reviewed yet.'
              : 'All clear! Click "Run AI Detection Now" to scan for new patterns.' ?>
          </p>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php foreach ($insights as $ins):
        $style = severityStyle($ins['severity']);
      ?>
      <div style="
        background:<?= $style['bg'] ?>;
        border:1px solid <?= $style['border'] ?>;
        border-left:4px solid <?= $style['color'] ?>;
        border-radius:var(--radius);
        padding:18px 20px;
      ">
        <!-- Header row -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;">
          <div style="display:flex;align-items:flex-start;gap:10px;">
            <span style="font-size:20px;line-height:1;"><?= $style['icon'] ?></span>
            <div>
              <div style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                <?= sanitize($ins['title']) ?>
              </div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="badge <?= $style['badge'] ?>" style="font-size:10px;"><?= ucfirst($ins['severity']) ?></span>
                <span class="badge badge-muted" style="font-size:10px;"><?= insightTypeLabel($ins['insight_type']) ?></span>
                <?php if ($ins['dept_name']): ?>
                <span class="badge badge-muted" style="font-size:10px;">🏢 <?= sanitize($ins['dept_name']) ?></span>
                <?php endif; ?>
                <?php if ($ins['first_name']): ?>
                <span class="badge badge-muted" style="font-size:10px;">👤 <?= sanitize($ins['first_name'] . ' ' . $ins['last_name']) ?></span>
                <?php endif; ?>
                <span style="font-size:11px;color:var(--text-muted);"><?= timeAgo($ins['created_at']) ?></span>
              </div>
            </div>
          </div>
          <!-- Confidence meter -->
          <div style="text-align:center;flex-shrink:0;min-width:60px;">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">Confidence</div>
            <div style="font-size:18px;font-weight:800;color:<?= $ins['confidence_pct'] >= 80 ? $style['color'] : 'var(--text-muted)' ?>;">
              <?= $ins['confidence_pct'] ?>%
            </div>
          </div>
        </div>

        <!-- Description -->
        <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;margin-bottom:14px;">
          <?= sanitize($ins['description']) ?>
        </div>

        <?php if ($ins['is_reviewed']): ?>
        <!-- Already reviewed -->
        <div style="background:rgba(45,212,160,0.08);border:1px solid rgba(45,212,160,0.2);border-radius:8px;padding:10px 12px;font-size:12px;color:var(--success);">
          ✓ Reviewed by <?= sanitize($ins['rev_first'] . ' ' . $ins['rev_last']) ?>
          on <?= formatDate($ins['reviewed_at'], 'M d, Y') ?>
          <?php if ($ins['review_note']): ?>
          — "<?= sanitize($ins['review_note']) ?>"
          <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Review form -->
        <form method="POST" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
          <input type="hidden" name="action" value="review_insight">
          <input type="hidden" name="insight_id" value="<?= $ins['id'] ?>">
          <div style="flex:1;min-width:200px;">
            <input type="text" name="review_note" class="form-control"
              style="height:34px;font-size:12px;"
              placeholder="Brief review note (e.g. Investigated — confirmed false positive)">
          </div>
          <button type="submit" class="btn btn-outline btn-sm" style="white-space:nowrap;height:34px;">
            <?= icon('check-square', 13) ?> Mark Reviewed
          </button>
        </form>
        <?php endif; ?>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── SIDEBAR: Type Breakdown ────────────────────────────── -->
  <div>
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('pie-chart') ?> By Alert Type</div>
      </div>
      <div class="card-body" style="padding:12px;">
        <?php if (empty($byType)): ?>
        <div style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px;">No active alerts</div>
        <?php else: ?>
        <?php
        $maxCount = max(array_column($byType, 'cnt'));
        foreach ($byType as $t):
          $pct = $maxCount > 0 ? round(($t['cnt'] / $maxCount) * 100) : 0;
        ?>
        <div style="margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
            <span style="color:var(--text-secondary);"><?= insightTypeLabel($t['insight_type']) ?></span>
            <span style="font-weight:700;color:var(--text-primary);"><?= $t['cnt'] ?></span>
          </div>
          <div class="progress" style="height:6px;">
            <div class="progress-bar gold" style="width:<?= $pct ?>%;"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- AI Engine Info -->
    <div class="card" style="border:1px solid rgba(201,168,76,0.25);">
      <div class="card-header" style="background:rgba(201,168,76,0.06);">
        <div class="card-title" style="color:var(--gold);">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:5px;"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
          AI Detectors Active
        </div>
      </div>
      <div class="card-body" style="padding:12px;">
        <?php
        $detectors = [
            ['icon' => '📋', 'name' => 'Copy-Paste Reports',    'desc' => 'Identical text on multiple days'],
            ['icon' => '❌', 'name' => 'Missing Submissions',   'desc' => 'Staff not reporting for 7+ days'],
            ['icon' => '📈', 'name' => 'KPI Score Inflation',   'desc' => '80%+ perfect self-scores'],
            ['icon' => '💰', 'name' => 'Budget Overrun Risk',   'desc' => 'Spending ahead of year pace'],
            ['icon' => '📉', 'name' => 'Low Approval Rates',    'desc' => 'Department below 40%'],
            ['icon' => '🎫', 'name' => 'IT SLA Breaches',       'desc' => 'Tickets past deadline'],
            ['icon' => '💳', 'name' => 'Duplicate Transactions', 'desc' => 'Same amount+category+date'],
            ['icon' => '🧾', 'name' => 'High Outstanding Fees', 'desc' => 'Overdue student balances'],
        ];
        foreach ($detectors as $det):
        ?>
        <div style="display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);">
          <span style="font-size:14px;"><?= $det['icon'] ?></span>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= $det['name'] ?></div>
            <div style="font-size:11px;color:var(--text-muted);"><?= $det['desc'] ?></div>
          </div>
          <span style="margin-left:auto;flex-shrink:0;">
            <span class="badge badge-success" style="font-size:9px;">ON</span>
          </span>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:12px;font-size:11px;color:var(--text-muted);line-height:1.6;">
          ⚠️ AI flags require human review before any action is taken. Confidence scores indicate pattern strength — not certainty.
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
