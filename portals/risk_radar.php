<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireRole(2); // CEO and Principal only

$db   = getDB();
$user = currentUser();
$uid  = (int)$user['id'];

$aiEngine   = new AIEngine($db);
$monthStart = date('Y-m-01');
$today      = date('Y-m-d');
$period     = date('Y-m');

$pageTitle    = 'Risk Radar';
$pageSubtitle = 'Executive Intelligence & Risk Overview';
require_once __DIR__ . '/../includes/header.php';

// ── Run AI if stale ───────────────────────────────────────────
if (empty($_SESSION['ai_risk_run']) || time() - $_SESSION['ai_risk_run'] > 1800) {
    $aiEngine->runAll();
    $_SESSION['ai_risk_run'] = time();
}

// ── RISK DATA COLLECTION ──────────────────────────────────────

// 1. Compliance risk — staff not submitting reports
$totalStaff       = (int)$db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.slug='staff' AND u.is_active=1")->fetchColumn();
$submittedToday   = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM reports WHERE report_date='$today'")->fetchColumn();
$complianceRate   = $totalStaff > 0 ? round($submittedToday / $totalStaff * 100) : 0;
$complianceRisk   = $complianceRate < 50 ? 'critical' : ($complianceRate < 75 ? 'high' : ($complianceRate < 90 ? 'medium' : 'low'));

// 2. Financial risk — pending/unreviewed transactions
$pendingTxns      = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
$totalTxnValue    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='pending'")->fetchColumn();
$fraudFlags       = (int)$db->query("SELECT COUNT(*) FROM fraud_flags WHERE is_resolved=0")->fetchColumn();
$financeRisk      = $fraudFlags > 0 || $pendingTxns > 20 ? 'critical' : ($pendingTxns > 10 ? 'high' : ($pendingTxns > 3 ? 'medium' : 'low'));

// 3. IT risk — SLA breaches and critical tickets
$itSlaBreached    = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE sla_deadline < NOW() AND status IN ('open','in_progress','pending_user')")->fetchColumn();
$criticalTickets  = (int)$db->query("SELECT COUNT(*) FROM it_tickets WHERE priority='critical' AND status IN ('open','in_progress')")->fetchColumn();
$itRisk           = $criticalTickets > 0 || $itSlaBreached > 5 ? 'critical' : ($itSlaBreached > 2 ? 'high' : ($itSlaBreached > 0 ? 'medium' : 'low'));

// 4. HR / Performance risk — low approval rates
$avgApproval      = (float)$db->query(
    "SELECT ROUND(AVG(rate),1) FROM (
        SELECT u.id,
               CASE WHEN COUNT(r.id)>0 THEN SUM(r.status='approved')/COUNT(r.id)*100 ELSE NULL END AS rate
        FROM users u LEFT JOIN reports r ON r.user_id=u.id AND r.report_date>='$monthStart'
        JOIN roles ro ON u.role_id=ro.id WHERE ro.slug='staff' AND u.is_active=1
        GROUP BY u.id
     ) sub WHERE rate IS NOT NULL"
)->fetchColumn();
$lowPerformers    = (int)$db->query(
    "SELECT COUNT(*) FROM (
        SELECT user_id FROM reports WHERE report_date>='$monthStart'
        GROUP BY user_id HAVING SUM(status='approved')/COUNT(*)*100 < 30
     ) sub"
)->fetchColumn();
$hrRisk = $avgApproval < 40 || $lowPerformers > 5 ? 'critical' : ($avgApproval < 60 ? 'high' : ($avgApproval < 75 ? 'medium' : 'low'));

// 5. Procurement risk — critical pending requests
$criticalProcurement = (int)$db->query("SELECT COUNT(*) FROM procurement_requests WHERE urgency='critical' AND status IN ('submitted','finance_review')")->fetchColumn();
$overallProcPending  = (int)$db->query("SELECT COUNT(*) FROM procurement_requests WHERE status IN ('submitted','finance_review')")->fetchColumn();
$procRisk = $criticalProcurement > 0 ? 'critical' : ($overallProcPending > 10 ? 'high' : ($overallProcPending > 3 ? 'medium' : 'low'));

// 6. AI detected risks
$allInsights      = $aiEngine->getActiveInsights(null, 20);
$criticalInsights = array_filter($allInsights, fn($i) => $i['severity'] === 'critical');
$warningInsights  = array_filter($allInsights, fn($i) => $i['severity'] === 'warning');
$aiRisk           = count($criticalInsights) > 3 ? 'critical' : (count($criticalInsights) > 0 ? 'high' : (count($warningInsights) > 3 ? 'medium' : 'low'));

// Overall risk score (weighted)
$riskWeights = ['compliance'=>25, 'finance'=>25, 'it'=>15, 'hr'=>15, 'procurement'=>10, 'ai'=>10];
$riskScores  = ['low'=>0, 'medium'=>33, 'high'=>66, 'critical'=>100];
$riskValues  = [
    'compliance'  => $riskScores[$complianceRisk],
    'finance'     => $riskScores[$financeRisk],
    'it'          => $riskScores[$itRisk],
    'hr'          => $riskScores[$hrRisk],
    'procurement' => $riskScores[$procRisk],
    'ai'          => $riskScores[$aiRisk],
];
$overallScore = 0;
foreach ($riskWeights as $k => $w) $overallScore += ($riskValues[$k] * $w / 100);
$overallScore = (int)round($overallScore);
$overallLevel = $overallScore >= 66 ? 'critical' : ($overallScore >= 33 ? 'high' : ($overallScore >= 15 ? 'medium' : 'low'));

// Department risk scores
$deptRisks = $db->query(
    "SELECT d.id, d.name, d.code,
            COUNT(DISTINCT u.id) AS staff,
            COUNT(r.id) AS reports_month,
            SUM(r.status='approved') AS approved,
            SUM(r.status='pending') AS pending,
            SUM(r.status='rejected') AS rejected,
            ROUND(AVG(k.quality_score),0) AS avg_quality
     FROM departments d
     LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1
     LEFT JOIN reports r ON r.user_id=u.id AND r.report_date>='$monthStart'
     LEFT JOIN kpi_submissions k ON k.user_id=u.id AND k.submission_date>='$monthStart'
     GROUP BY d.id ORDER BY d.name"
)->fetchAll();

// Resolve a fraud flag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_flag'])) {
    $flagId     = (int)$_POST['flag_id'];
    $resolution = trim($_POST['resolution'] ?? '');
    $db->prepare("UPDATE fraud_flags SET is_resolved=1,resolved_by=?,resolved_at=NOW(),resolution=? WHERE id=?")
       ->execute([$uid,$resolution,$flagId]);
    logActivity($uid,'FRAUD_FLAG_RESOLVED','Fraud flag ID '.$flagId.' resolved');
    setFlash('success','✅ Flag resolved.');
    header('Location: risk_radar.php'); exit;
}

// Fraud flags
$fraudFlagsList = $db->query(
    "SELECT ff.*, u.first_name, u.last_name, d.name AS dept_name
     FROM fraud_flags ff
     LEFT JOIN users u ON ff.user_id=u.id
     LEFT JOIN departments d ON ff.department_id=d.id
     WHERE ff.is_resolved=0
     ORDER BY FIELD(ff.severity,'critical','high','medium','low'), ff.created_at DESC
     LIMIT 15"
)->fetchAll();

// Risk helpers
function riskColor(string $level): string {
    return match($level) {
        'critical' => 'var(--danger)',
        'high'     => 'var(--warning)',
        'medium'   => '#f59e0b',
        default    => 'var(--success)',
    };
}
function riskIcon(string $level): string {
    return match($level) { 'critical'=>'🔴','high'=>'🟠','medium'=>'🟡',default=>'🟢' };
}
function riskBg(string $level): string {
    return match($level) {
        'critical' => 'rgba(232,85,106,0.08)',
        'high'     => 'rgba(245,158,11,0.08)',
        'medium'   => 'rgba(245,166,35,0.06)',
        default    => 'rgba(45,212,160,0.06)',
    };
}
?>

<!-- Overall Risk Score Banner -->
<div class="card mb-24" style="background:<?= riskBg($overallLevel) ?>;border:1px solid <?= riskColor($overallLevel) ?>33;">
  <div class="card-body" style="padding:24px 28px;">
    <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;">

      <!-- Score gauge -->
      <div style="text-align:center;flex-shrink:0;">
        <div style="width:110px;height:110px;border-radius:50%;border:6px solid <?= riskColor($overallLevel) ?>;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--bg-card);">
          <div style="font-size:32px;font-weight:800;color:<?= riskColor($overallLevel) ?>;"><?= $overallScore ?></div>
          <div style="font-size:10px;color:var(--text-muted);">/ 100</div>
        </div>
        <div style="font-size:12px;font-weight:700;color:<?= riskColor($overallLevel) ?>;margin-top:8px;text-transform:uppercase;"><?= $overallLevel ?> Risk</div>
      </div>

      <!-- Risk breakdown bars -->
      <div style="flex:1;min-width:280px;">
        <div style="font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:16px;">
          <?= riskIcon($overallLevel) ?> Overall Institutional Risk Score
        </div>
        <?php
        $riskAreas = [
            'Compliance'    => [$complianceRisk,  $riskValues['compliance'],  $complianceRate.'% submission rate'],
            'Finance'       => [$financeRisk,      $riskValues['finance'],     $pendingTxns.' pending txns · '.$fraudFlags.' fraud flags'],
            'IT Operations' => [$itRisk,           $riskValues['it'],          $itSlaBreached.' SLA breaches · '.$criticalTickets.' critical'],
            'HR/Performance'=> [$hrRisk,           $riskValues['hr'],          round($avgApproval).'% avg approval · '.$lowPerformers.' low performers'],
            'Procurement'   => [$procRisk,         $riskValues['procurement'], $overallProcPending.' pending ('.$criticalProcurement.' critical)'],
            'AI Detection'  => [$aiRisk,           $riskValues['ai'],          count($criticalInsights).' critical · '.count($warningInsights).' warnings'],
        ];
        foreach ($riskAreas as $label => [$level,$score,$detail]):
        ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <span style="font-size:13px;font-weight:600;color:var(--text-secondary);width:120px;flex-shrink:0;"><?= $label ?></span>
          <div style="flex:1;height:12px;background:var(--bg-elevated);border-radius:6px;overflow:hidden;">
            <div style="height:100%;width:<?= $score ?>%;background:<?= riskColor($level) ?>;border-radius:6px;transition:width 0.8s ease;"></div>
          </div>
          <span style="font-size:10px;padding:2px 8px;border-radius:4px;background:<?= riskBg($level) ?>;color:<?= riskColor($level) ?>;font-weight:700;flex-shrink:0;width:60px;text-align:center;"><?= ucfirst($level) ?></span>
        </div>
        <div style="font-size:10px;color:var(--text-muted);margin-bottom:10px;padding-left:130px;"><?= $detail ?></div>
        <?php endforeach; ?>
      </div>

      <!-- Quick actions -->
      <div style="flex-shrink:0;display:flex;flex-direction:column;gap:8px;">
        <a href="ai_insights.php" class="btn btn-outline btn-sm"><?= icon('zap',13) ?> AI Insights (<?= count($allInsights) ?>)</a>
        <a href="finance_procurement.php?urgency=critical" class="btn btn-outline btn-sm"><?= icon('shopping-cart',13) ?> Critical PRQs</a>
        <a href="it_tickets.php?tab=open" class="btn btn-outline btn-sm"><?= icon('message-square',13) ?> IT Tickets</a>
        <a href="ceo_analytics.php" class="btn btn-outline btn-sm"><?= icon('bar-chart',13) ?> Analytics</a>
      </div>
    </div>
  </div>
</div>

<!-- Risk Cards Row -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
  <?php
  $cards = [
      ['Compliance',   $complianceRisk,  '📋', $complianceRate.'%', 'Report Submission Today', $submittedToday.'/'.$totalStaff.' staff submitted'],
      ['Finance',      $financeRisk,     '💰', $fraudFlags > 0 ? $fraudFlags.' flags' : $pendingTxns.' pending', 'Financial Risk', $fraudFlags>0?'Fraud flags detected':'Pending transactions'],
      ['IT Operations',$itRisk,          '🖥️', $itSlaBreached, 'SLA Breaches', $criticalTickets.' critical tickets open'],
      ['Performance',  $hrRisk,          '📊', round($avgApproval).'%', 'Avg Approval Rate', $lowPerformers.' low-performing staff'],
      ['Procurement',  $procRisk,        '🛒', $overallProcPending, 'Pending PRQs', $criticalProcurement.' flagged critical'],
      ['AI Detected',  $aiRisk,          '🤖', count($allInsights), 'Active Alerts', count($criticalInsights).' critical · '.count($warningInsights).' warnings'],
  ];
  foreach ($cards as [$label,$level,$emoji,$value,$sublabel,$detail]):
    $col = riskColor($level);
    $bg  = riskBg($level);
  ?>
  <div style="background:var(--bg-card);border:1px solid var(--border);border-top:4px solid <?= $col ?>;border-radius:var(--radius);padding:18px;background:<?= $bg ?>;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
      <span style="font-size:18px;"><?= $emoji ?></span>
      <span style="font-size:11px;font-weight:700;color:<?= $col ?>;text-transform:uppercase;"><?= riskIcon($level) ?> <?= ucfirst($level) ?></span>
    </div>
    <div style="font-size:28px;font-weight:800;color:<?= $col ?>;margin-bottom:4px;"><?= $value ?></div>
    <div style="font-size:12px;font-weight:600;color:var(--text-secondary);"><?= $label ?> — <?= $sublabel ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= $detail ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Department Risk Matrix -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> Department Risk Matrix — <?= date('F Y') ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Department</th>
          <th>Staff</th>
          <th>Reports</th>
          <th>Approval Rate</th>
          <th>Avg Quality</th>
          <th>Risk Level</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deptRisks as $d):
          $dRate   = (int)$d['reports_month'] > 0 ? round((int)$d['approved'] / (int)$d['reports_month'] * 100) : 0;
          $dRisk   = $dRate < 40 ? 'critical' : ($dRate < 60 ? 'high' : ($dRate < 80 ? 'medium' : 'low'));
          if ((int)$d['staff'] === 0) $dRisk = 'low';
          $dCol    = riskColor($dRisk);
        ?>
        <tr>
          <td>
            <div class="td-bold"><?= sanitize($d['name']) ?></div>
            <span class="badge badge-muted" style="font-size:10px;"><?= sanitize($d['code']) ?></span>
          </td>
          <td><?= (int)$d['staff'] ?></td>
          <td><?= (int)$d['approved'] ?><span class="td-muted">/<?= (int)$d['reports_month'] ?></span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress" style="flex:1;max-width:100px;">
                <div class="progress-bar <?= $dRate>=80?'green':($dRate>=60?'gold':'red') ?>" style="width:<?= $dRate ?>%;"></div>
              </div>
              <span style="font-size:12px;font-weight:700;color:<?= $dCol ?>;"><?= $dRate ?>%</span>
            </div>
          </td>
          <td style="font-weight:600;color:<?= $d['avg_quality'] ? ((float)$d['avg_quality']>=75?'var(--success)':'var(--warning)') : 'var(--text-muted)' ?>;">
            <?= $d['avg_quality'] ? $d['avg_quality'].'%' : '—' ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <span><?= riskIcon($dRisk) ?></span>
              <span style="font-size:12px;font-weight:700;color:<?= $dCol ?>;"><?= ucfirst($dRisk) ?></span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- AI Alerts + Fraud Flags -->
<div class="grid-2">

  <!-- AI Insights -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('zap') ?> Active AI Alerts</div>
      <a href="ai_insights.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if (empty($allInsights)): ?>
    <div class="card-body">
      <div class="empty-state" style="padding:20px;"><?= icon('check-circle',28) ?><p style="color:var(--success);">No active AI alerts</p></div>
    </div>
    <?php else: ?>
    <div style="max-height:380px;overflow-y:auto;">
      <?php foreach (array_slice($allInsights,0,10) as $ins):
        $col = $ins['severity']==='critical'?'var(--danger)':($ins['severity']==='warning'?'var(--warning)':'var(--info)');
        $ico = $ins['severity']==='critical'?'🚨':($ins['severity']==='warning'?'⚠️':'ℹ️');
      ?>
      <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
        <span style="flex-shrink:0;"><?= $ico ?></span>
        <div style="flex:1;">
          <div style="font-size:12.5px;font-weight:600;color:var(--text-primary);"><?= sanitize($ins['title']) ?></div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= sanitize(substr($ins['description'],0,90)) ?>…</div>
          <?php if ($ins['dept_name']): ?><span class="badge badge-muted" style="font-size:9px;margin-top:3px;"><?= sanitize($ins['dept_name']) ?></span><?php endif; ?>
        </div>
        <span style="font-size:10px;color:<?= $col ?>;font-weight:700;flex-shrink:0;"><?= $ins['confidence_pct'] ?>%</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Fraud Flags -->
  <div class="card">
    <div class="card-header">
      <div class="card-title" style="<?= $fraudFlags>0?'color:var(--danger);':'' ?>"><?= icon('shield') ?> Fraud &amp; Anomaly Flags</div>
      <span class="badge badge-<?= $fraudFlags>0?'danger':'success' ?>"><?= $fraudFlags ?> active</span>
    </div>
    <?php if (empty($fraudFlagsList)): ?>
    <div class="card-body">
      <div class="empty-state" style="padding:20px;"><?= icon('shield',28) ?><p style="color:var(--success);">No active fraud flags</p></div>
    </div>
    <?php else: ?>
    <div style="max-height:380px;overflow-y:auto;">
      <?php foreach ($fraudFlagsList as $ff):
        $fcol = ['critical'=>'var(--danger)','high'=>'var(--warning)','medium'=>'#f59e0b','low'=>'var(--text-muted)'][$ff['severity']] ?? 'var(--text-muted)';
      ?>
      <div style="padding:12px 16px;border-bottom:1px solid var(--border);border-left:3px solid <?= $fcol ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
          <div style="flex:1;">
            <div style="font-size:12px;font-weight:700;color:<?= $fcol ?>;margin-bottom:3px;"><?= ucfirst($ff['severity']) ?> — <?= sanitize(str_replace('_',' ',$ff['flag_type'])) ?></div>
            <div style="font-size:12px;color:var(--text-primary);"><?= sanitize(substr($ff['description'],0,100)) ?>…</div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
              <?= $ff['first_name'] ? sanitize($ff['first_name'].' '.$ff['last_name']) : '' ?>
              <?= $ff['dept_name'] ? '· '.sanitize($ff['dept_name']) : '' ?>
              · <?= $ff['ai_confidence'] ?>% confidence · <?= timeAgo($ff['created_at']) ?>
            </div>
          </div>
          <button class="btn btn-outline btn-sm" style="flex-shrink:0;font-size:10px;" onclick="openResolveModal(<?= $ff['id'] ?>, '<?= sanitize(addslashes($ff['description'])) ?>')">Resolve</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Resolve Flag Modal -->
<div class="modal-overlay" id="resolveFlagModal">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('shield') ?> Resolve Fraud Flag</div>
      <button class="modal-close" onclick="closeModal('resolveFlagModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="resolve_flag" value="1">
      <input type="hidden" name="flag_id" id="resolveFlagId">
      <div class="modal-body">
        <div id="resolveFlagDesc" style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:8px;padding:12px;font-size:13px;color:var(--text-secondary);margin-bottom:16px;"></div>
        <div class="form-group">
          <label class="form-label">Resolution Notes <span style="color:var(--danger);">*</span></label>
          <textarea name="resolution" class="form-control" rows="3" placeholder="Explain how this was investigated and resolved…" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('resolveFlagModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('check-square',14) ?> Mark Resolved</button>
      </div>
    </form>
  </div>
</div>

<script>
function openResolveModal(id, desc) {
    document.getElementById('resolveFlagId').value   = id;
    document.getElementById('resolveFlagDesc').textContent = desc;
    openModal('resolveFlagModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
