<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireRole(3);

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

// ── Generate scorecards on demand ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $period = trim($_POST['period'] ?? date('Y-m'));
    if (preg_match('/^\d{4}-\d{2}$/', $period)) {
        $engine = new AIEngine($db);
        $count  = $engine->generateScorecards($period);
        setFlash('success', "✅ Scorecards generated for {$count} staff members for {$period}.");
    }
    header('Location: scorecards.php?period=' . urlencode($period)); exit;
}

$period     = $_GET['period'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $period)) $period = date('Y-m');
$deptFilter = (int)($_GET['dept'] ?? 0);

$pageTitle    = 'Performance Scorecards';
$pageSubtitle = 'Monthly staff performance analysis — ' . date('F Y', strtotime($period . '-01'));
require_once __DIR__ . '/../includes/header.php';

// ── Load scorecards ────────────────────────────────────────────
$where = "ps.period = '$period'";
if ($deptFilter) $where .= " AND ps.department_id = $deptFilter";

$scorecards = $db->query(
    "SELECT ps.*,
            u.first_name, u.last_name, u.avatar,
            d.name AS dept_name, d.code AS dept_code
     FROM performance_scorecards ps
     JOIN users u ON ps.user_id = u.id
     LEFT JOIN departments d ON ps.department_id = d.id
     WHERE $where
     ORDER BY ps.overall_score DESC, ps.rank_in_dept"
)->fetchAll();

// Dept summary
$deptSummary = $db->query(
    "SELECT d.name, d.code,
            COUNT(ps.id) AS staff_count,
            ROUND(AVG(ps.overall_score), 1) AS avg_score,
            MAX(ps.overall_score) AS top_score,
            MIN(ps.overall_score) AS low_score
     FROM performance_scorecards ps
     JOIN departments d ON ps.department_id = d.id
     WHERE ps.period = '$period'
     GROUP BY d.id
     ORDER BY avg_score DESC"
)->fetchAll();

$allDepts = $db->query("SELECT id, name, code FROM departments ORDER BY name")->fetchAll();

// Score color helper
function scoreColor(float $score): string {
    if ($score >= 80) return 'var(--success)';
    if ($score >= 60) return 'var(--warning)';
    if ($score >= 40) return '#f59e0b';
    return 'var(--danger)';
}
function scoreBg(float $score): string {
    if ($score >= 80) return 'rgba(45,212,160,0.12)';
    if ($score >= 60) return 'rgba(245,166,35,0.12)';
    if ($score >= 40) return 'rgba(245,158,11,0.12)';
    return 'rgba(232,85,106,0.12)';
}
?>

<!-- ── Period + Filters ───────────────────────────────────────── -->
<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px;">
  <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <input type="month" name="period" value="<?= $period ?>" class="form-control" style="height:34px;width:160px;font-size:13px;">
    <button type="submit" name="generate" value="1" class="btn btn-primary" style="height:34px;">
      <?= icon('refresh-cw', 13) ?> Generate / Refresh
    </button>
  </form>
  <select class="form-control" style="height:34px;width:auto;font-size:12px;"
    onchange="window.location='scorecards.php?period=<?= $period ?>&dept='+this.value">
    <option value="0">All Departments</option>
    <?php foreach ($allDepts as $d): ?>
    <option value="<?= $d['id'] ?>" <?= $deptFilter === (int)$d['id'] ? 'selected' : '' ?>>
      <?= sanitize($d['code'] . ' — ' . $d['name']) ?>
    </option>
    <?php endforeach; ?>
  </select>
</div>

<?php if (empty($scorecards)): ?>
<div class="card">
  <div class="card-body">
    <div class="empty-state" style="padding:60px 0;">
      <?= icon('bar-chart', 48) ?>
      <h3 style="margin:16px 0 8px;font-size:18px;">No Scorecards Generated</h3>
      <p style="color:var(--text-muted);">Click <strong>Generate / Refresh</strong> above to create performance scorecards for the selected month.</p>
    </div>
  </div>
</div>
<?php else: ?>

<!-- ── Department Summary ─────────────────────────────────────── -->
<?php if (!$deptFilter && !empty($deptSummary)): ?>
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> Department Performance Comparison</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Rank</th><th>Department</th><th>Staff Scored</th><th>Avg Score</th><th>Top Score</th><th>Low Score</th><th>Performance</th></tr></thead>
      <tbody>
        <?php foreach ($deptSummary as $i => $d): ?>
        <tr>
          <td class="td-num"><?= $i + 1 ?></td>
          <td>
            <div class="td-bold"><?= sanitize($d['name']) ?></div>
            <div class="td-muted"><?= sanitize($d['code']) ?></div>
          </td>
          <td><?= $d['staff_count'] ?></td>
          <td>
            <span style="font-size:16px;font-weight:800;color:<?= scoreColor((float)$d['avg_score']) ?>;">
              <?= $d['avg_score'] ?>
            </span>
            <span style="font-size:11px;color:var(--text-muted);">/100</span>
          </td>
          <td><span class="badge badge-success"><?= $d['top_score'] ?></span></td>
          <td><span class="badge badge-<?= $d['low_score'] < 40 ? 'danger' : 'muted' ?>"><?= $d['low_score'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;min-width:130px;">
              <div class="progress" style="flex:1;">
                <div class="progress-bar <?= (float)$d['avg_score'] >= 80 ? 'green' : ((float)$d['avg_score'] >= 60 ? 'gold' : 'red') ?>"
                  style="width:<?= $d['avg_score'] ?>%"></div>
              </div>
              <span style="font-size:12px;font-weight:600;color:var(--text-secondary);"><?= $d['avg_score'] ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ── Individual Scorecards ──────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('users') ?> Individual Scorecards — <?= date('F Y', strtotime($period . '-01')) ?></div>
    <span class="badge badge-muted"><?= count($scorecards) ?> staff</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Staff Member</th>
          <th>Department</th>
          <th style="text-align:center;">Report Score<br><span style="font-weight:400;font-size:10px;color:var(--text-muted);">30%</span></th>
          <th style="text-align:center;">KPI Score<br><span style="font-weight:400;font-size:10px;color:var(--text-muted);">30%</span></th>
          <th style="text-align:center;">Approval Rate<br><span style="font-weight:400;font-size:10px;color:var(--text-muted);">25%</span></th>
          <th style="text-align:center;">Consistency<br><span style="font-weight:400;font-size:10px;color:var(--text-muted);">15%</span></th>
          <th>Overall</th>
          <th>Dept Rank</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($scorecards as $i => $sc): ?>
        <tr style="<?= (float)$sc['overall_score'] < 40 ? 'background:rgba(232,85,106,0.03);' : '' ?>">
          <td class="td-num" style="font-weight:700;color:<?= $i < 3 ? 'var(--gold)' : 'var(--text-muted)' ?>;">
            <?= $i + 1 ?>
            <?= $i === 0 ? ' 🥇' : ($i === 1 ? ' 🥈' : ($i === 2 ? ' 🥉' : '')) ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <?php if (!empty($sc['avatar'])): ?>
              <img src="<?= sanitize(SITE_URL . '/' . ltrim($sc['avatar'], '/')) ?>"
                style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="avatar">
              <?php else: ?>
              <div class="avatar avatar-sm"><?= getInitials($sc['first_name'], $sc['last_name']) ?></div>
              <?php endif; ?>
              <span class="td-bold"><?= sanitize($sc['first_name'] . ' ' . $sc['last_name']) ?></span>
            </div>
          </td>
          <td>
            <span class="badge badge-muted"><?= sanitize($sc['dept_code'] ?? '—') ?></span>
          </td>
          <!-- Subscores with mini bars -->
          <?php foreach (['report_score', 'kpi_score', 'approval_rate', 'consistency_score'] as $metric): ?>
          <td style="text-align:center;">
            <div style="font-size:14px;font-weight:700;color:<?= scoreColor((float)$sc[$metric]) ?>;">
              <?= number_format((float)$sc[$metric], 0) ?>
            </div>
            <div style="width:48px;height:4px;background:var(--bg-elevated);border-radius:2px;margin:3px auto 0;">
              <div style="height:100%;width:<?= min(100,(float)$sc[$metric]) ?>%;background:<?= scoreColor((float)$sc[$metric]) ?>;border-radius:2px;"></div>
            </div>
          </td>
          <?php endforeach; ?>
          <!-- Overall -->
          <td>
            <div style="
              display:inline-flex;align-items:center;justify-content:center;
              width:52px;height:52px;border-radius:50%;
              background:<?= scoreBg((float)$sc['overall_score']) ?>;
              border:2px solid <?= scoreColor((float)$sc['overall_score']) ?>;
              font-size:15px;font-weight:800;color:<?= scoreColor((float)$sc['overall_score']) ?>;
            "><?= number_format((float)$sc['overall_score'], 0) ?></div>
          </td>
          <td>
            <?php if ($sc['rank_in_dept']): ?>
            <span style="font-size:13px;font-weight:700;color:var(--text-secondary);">#<?= $sc['rank_in_dept'] ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Legend ────────────────────────────────────────────────── -->
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;font-size:12px;color:var(--text-muted);">
  <span>Score Ranges:</span>
  <span style="color:var(--success);">■ 80–100: Excellent</span>
  <span style="color:var(--warning);">■ 60–79: Good</span>
  <span style="color:#f59e0b;">■ 40–59: Needs Improvement</span>
  <span style="color:var(--danger);">■ 0–39: Poor</span>
  <span style="margin-left:auto;">Weighting: Report Submission 30% · KPI Quality 30% · Approval Rate 25% · Consistency 15%</span>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
