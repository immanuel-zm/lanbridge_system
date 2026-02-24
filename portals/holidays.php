<?php
// ============================================================
// holidays.php — CEO Holiday & Closure Calendar
// CEO marks public holidays / closure days to exclude from
// compliance calculations. Uses public_holidays table.
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(1); // CEO only

$db  = getDB();
$uid = (int)currentUser()['id'];

// ── Auto-migrate: create table if missing ────────────────────
$db->exec("
    CREATE TABLE IF NOT EXISTS public_holidays (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        holiday_date DATE         NOT NULL,
        name         VARCHAR(150) NOT NULL,
        type         ENUM('public','closure','religious','other') NOT NULL DEFAULT 'public',
        is_active    TINYINT(1)   NOT NULL DEFAULT 1,
        created_by   INT,
        created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_holiday_date (holiday_date),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── POST handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $date = $_POST['holiday_date'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'public';
        $validTypes = ['public', 'closure', 'religious', 'other'];

        if (!$date || !$name) {
            setFlash('danger', '❌ Date and holiday name are required.');
        } elseif (!in_array($type, $validTypes)) {
            setFlash('danger', '❌ Invalid holiday type.');
        } else {
            try {
                $db->prepare("INSERT INTO public_holidays (holiday_date, name, type, created_by) VALUES (?,?,?,?)")
                   ->execute([$date, $name, $type, $uid]);
                logActivity($uid, 'HOLIDAY_ADDED', 'Added holiday: '.$name.' on '.$date);
                setFlash('success', '✅ Holiday added — '.$name.' on '.date('D, M j Y', strtotime($date)).'.');
            } catch (PDOException $e) {
                setFlash('danger', str_contains($e->getMessage(), 'Duplicate') ? '❌ A holiday already exists on that date.' : '❌ Could not save holiday.');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['holiday_id'] ?? 0);
        if ($id) {
            $h = $db->query("SELECT * FROM public_holidays WHERE id=$id")->fetch();
            if ($h) {
                $db->prepare("DELETE FROM public_holidays WHERE id=?")->execute([$id]);
                logActivity($uid, 'HOLIDAY_DELETED', 'Deleted holiday: '.$h['name'].' on '.$h['holiday_date']);
                setFlash('success', '✅ Holiday removed.');
            }
        }
    }

    if ($action === 'toggle') {
        $id     = (int)($_POST['holiday_id'] ?? 0);
        $newVal = (int)($_POST['new_val'] ?? 0);
        $db->prepare("UPDATE public_holidays SET is_active=? WHERE id=?")->execute([$newVal, $id]);
        setFlash('success', $newVal ? '✅ Holiday re-enabled.' : '⚠️ Holiday disabled (not counted as exclusion).');
    }

    header('Location: holidays.php'); exit;
}

// ── Load data ─────────────────────────────────────────────────
$holidays = $db->query(
    "SELECT * FROM public_holidays ORDER BY holiday_date ASC"
)->fetchAll();

// Index by date for calendar rendering
$holidayMap = [];
foreach ($holidays as $h) {
    $holidayMap[$h['holiday_date']] = $h;
}

// Count active holidays
$activeCount  = count(array_filter($holidays, fn($h) => (int)$h['is_active']));
$thisYearCount = count(array_filter($holidays, fn($h) => substr($h['holiday_date'], 0, 4) === date('Y') && (int)$h['is_active']));

$pageTitle    = 'Holiday & Closure Calendar';
$pageSubtitle = 'Mark public holidays and closure days to exclude from compliance tracking';
require_once __DIR__ . '/../includes/header.php';

$flash = getFlash();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" data-auto-dismiss style="margin-bottom:18px;">
  <?= sanitize($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Stat Strip -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $thisYearCount ?></div><div class="stat-label"><?= date('Y') ?> Holidays</div></div>
      <div class="stat-icon"><?= icon('calendar',20) ?></div>
    </div>
    <div class="stat-delta">Active exclusions this year</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-top">
      <div><div class="stat-number"><?= $activeCount ?></div><div class="stat-label">Total Active</div></div>
      <div class="stat-icon"><?= icon('check-square',20) ?></div>
    </div>
    <div class="stat-delta">All-time enabled holidays</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= count(array_filter($holidays, fn($h) => $h['type']==='public' && (int)$h['is_active'])) ?></div><div class="stat-label">Public Holidays</div></div>
      <div class="stat-icon"><?= icon('globe',20) ?></div>
    </div>
    <div class="stat-delta">Government-declared days</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-top">
      <div><div class="stat-number"><?= count(array_filter($holidays, fn($h) => $h['type']==='closure' && (int)$h['is_active'])) ?></div><div class="stat-label">Closures</div></div>
      <div class="stat-icon"><?= icon('lock',20) ?></div>
    </div>
    <div class="stat-delta">Institution closure days</div>
  </div>
</div>

<div class="grid-2 mb-24">

  <!-- Add Holiday Form -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('plus-circle') ?> Add Holiday or Closure</div>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Date *</label>
          <input type="date" name="holiday_date" class="form-control" required
            min="<?= date('Y-01-01') ?>" max="<?= date('Y+2').'-12-31' ?>"
            value="<?= htmlspecialchars($_GET['prefill_date'] ?? '') ?>">
          <div class="form-helper">Select the day to exclude from compliance tracking</div>
        </div>
        <div class="form-group">
          <label class="form-label">Holiday Name *</label>
          <input type="text" name="name" class="form-control" required
            placeholder="e.g. Independence Day, Term Break, Good Friday…"
            maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" class="form-control">
            <option value="public">🏛️ Public Holiday (Government-declared)</option>
            <option value="closure">🏫 Institution Closure</option>
            <option value="religious">⛪ Religious Observance</option>
            <option value="other">📌 Other</option>
          </select>
        </div>

        <!-- Quick presets for common Zambian holidays -->
        <div style="margin-bottom:14px;">
          <div class="form-label" style="margin-bottom:6px;">Quick Presets <span style="font-size:11px;color:var(--text-muted);">(click to fill)</span></div>
          <div style="display:flex;flex-wrap:wrap;gap:5px;">
            <?php
            $year = date('Y');
            $presets = [
              ["$year-01-01", 'New Year\'s Day',         'public'],
              ["$year-03-12", 'Youth Day',               'public'],
              ["$year-04-28", 'African Freedom Day',     'public'],
              ["$year-05-01", 'Workers\' Day',           'public'],
              ["$year-05-25", 'Africa Day',              'public'],
              ["$year-07-07", 'Heroes\' Day',            'public'],
              ["$year-07-08", 'Unity Day',               'public'],
              ["$year-08-04", 'Farmers\' Day',           'public'],
              ["$year-10-24", 'Independence Day',        'public'],
              ["$year-12-25", 'Christmas Day',           'public'],
              ["$year-12-26", 'Boxing Day',              'public'],
            ];
            foreach ($presets as [$pDate, $pName, $pType]):
              $exists = isset($holidayMap[$pDate]);
            ?>
            <button type="button"
              onclick="fillPreset('<?= $pDate ?>','<?= addslashes($pName) ?>','<?= $pType ?>')"
              style="font-size:11px;padding:4px 9px;border-radius:5px;border:1px solid <?= $exists ? 'var(--success)' : 'var(--border)' ?>;background:<?= $exists ? 'rgba(45,212,160,0.1)' : 'var(--bg-elevated)' ?>;color:<?= $exists ? 'var(--success)' : 'var(--text-secondary)' ?>;cursor:pointer;"
              title="<?= $exists ? 'Already added' : 'Click to prefill' ?>">
              <?= $exists ? '✓ ' : '' ?><?= htmlspecialchars($pName) ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="btn btn-primary"><?= icon('plus-circle',14) ?> Add Holiday</button>
      </form>
    </div>
  </div>

  <!-- Holiday List -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= icon('list') ?> Holiday Register</div>
      <span class="badge badge-gold"><?= count($holidays) ?> entries</span>
    </div>
    <?php if (empty($holidays)): ?>
    <div class="card-body">
      <div class="empty-state" style="padding:40px;">
        <?= icon('calendar',36) ?>
        <h3 style="margin:12px 0 6px;font-size:15px;">No Holidays Defined</h3>
        <p style="font-size:12px;color:var(--text-muted);">Add the first holiday using the form.</p>
      </div>
    </div>
    <?php else: ?>
    <div style="max-height:420px;overflow-y:auto;">
      <table style="width:100%;border-collapse:collapse;">
        <thead style="position:sticky;top:0;background:var(--bg-card);z-index:1;">
          <tr>
            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);border-bottom:1px solid var(--border);">Date</th>
            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);border-bottom:1px solid var(--border);">Name</th>
            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);border-bottom:1px solid var(--border);">Type</th>
            <th style="padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:var(--text-muted);border-bottom:1px solid var(--border);">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $typeIcons = ['public' => '🏛️', 'closure' => '🏫', 'religious' => '⛪', 'other' => '📌'];
          $isPast = false;
          $today2 = date('Y-m-d');
          foreach ($holidays as $h):
            $isPast  = $h['holiday_date'] < $today2;
            $isToday = $h['holiday_date'] === $today2;
            $typeIco = $typeIcons[$h['type']] ?? '📌';
          ?>
          <tr style="border-bottom:1px solid var(--border);opacity:<?= (int)$h['is_active'] ? '1' : '0.45' ?>;">
            <td style="padding:9px 14px;">
              <div style="font-size:13px;font-weight:700;color:<?= $isToday ? 'var(--gold)' : ($isPast ? 'var(--text-muted)' : 'var(--text-primary)') ?>;">
                <?= date('M j, Y', strtotime($h['holiday_date'])) ?>
              </div>
              <div style="font-size:10px;color:var(--text-muted);"><?= date('l', strtotime($h['holiday_date'])) ?></div>
              <?php if ($isToday): ?><span class="badge badge-gold" style="font-size:9px;">Today</span><?php endif; ?>
              <?php if ($isPast && !$isToday): ?><span style="font-size:9px;color:var(--text-muted);">Past</span><?php endif; ?>
            </td>
            <td style="padding:9px 14px;">
              <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= sanitize($h['name']) ?></div>
              <?php if (!(int)$h['is_active']): ?>
              <span style="font-size:10px;color:var(--danger);">Disabled</span>
              <?php endif; ?>
            </td>
            <td style="padding:9px 14px;">
              <span style="font-size:12px;"><?= $typeIco ?> <?= ucfirst($h['type']) ?></span>
            </td>
            <td style="padding:9px 14px;">
              <div style="display:flex;gap:5px;align-items:center;">
                <!-- Toggle active -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action"      value="toggle">
                  <input type="hidden" name="holiday_id"  value="<?= $h['id'] ?>">
                  <input type="hidden" name="new_val"     value="<?= (int)$h['is_active'] ? 0 : 1 ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="font-size:10px;padding:3px 8px;"
                    title="<?= (int)$h['is_active'] ? 'Disable' : 'Enable' ?>">
                    <?= (int)$h['is_active'] ? icon('eye-off',11) : icon('eye',11) ?>
                  </button>
                </form>
                <!-- Delete -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete \'<?= addslashes($h['name']) ?>\'?');">
                  <input type="hidden" name="action"     value="delete">
                  <input type="hidden" name="holiday_id" value="<?= $h['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm" style="font-size:10px;padding:3px 8px;border-color:var(--danger);color:var(--danger);">
                    <?= icon('trash-2',11) ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Visual Year Calendar -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('calendar') ?> <?= date('Y') ?> Visual Calendar</div>
    <div style="display:flex;gap:10px;font-size:12px;align-items:center;flex-wrap:wrap;">
      <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:2px;background:rgba(201,168,76,0.7);display:inline-block;"></span> Holiday/Closure</span>
      <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:2px;background:var(--bg-elevated);border:1px solid var(--border);display:inline-block;"></span> Weekend</span>
      <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:2px;background:transparent;border:1px dashed var(--border);display:inline-block;"></span> Workday</span>
    </div>
  </div>
  <div class="card-body" style="padding:20px;">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px;">
    <?php
    $today3 = date('Y-m-d');
    for ($mo = 1; $mo <= 12; $mo++):
      $moStr    = date('Y') . '-' . str_pad($mo, 2, '0', STR_PAD_LEFT);
      $firstDay = "$moStr-01";
      $daysInMo = (int)date('t', strtotime($firstDay));
      $firstDow = (int)date('N', strtotime($firstDay)); // 1=Mon
    ?>
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--text-primary);margin-bottom:8px;text-align:center;">
        <?= date('F', strtotime($firstDay)) ?>
      </div>
      <!-- Day headers -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:2px;">
        <?php foreach (['M','T','W','T','F','S','S'] as $dh): ?>
        <div style="text-align:center;font-size:9px;font-weight:700;color:var(--text-muted);"><?= $dh ?></div>
        <?php endforeach; ?>
      </div>
      <!-- Days grid -->
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">
        <?php
        for ($pad = 1; $pad < $firstDow; $pad++): ?><div></div><?php endfor;
        for ($day = 1; $day <= $daysInMo; $day++):
          $ds4 = $moStr . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
          $dow4 = (int)date('N', strtotime($ds4));
          $isWeekend4 = $dow4 >= 6;
          $isToday4   = ($ds4 === $today3);
          $holData    = $holidayMap[$ds4] ?? null;
          $isHol      = $holData && (int)$holData['is_active'];

          if ($isHol) {
              $bg  = 'rgba(201,168,76,0.75)';
              $col = '#1a1a2e';
              $title4 = $holData['name'];
          } elseif ($isWeekend4) {
              $bg  = 'var(--bg-elevated)';
              $col = 'var(--text-muted)';
              $title4 = 'Weekend';
          } else {
              $bg  = 'transparent';
              $col = $isToday4 ? 'var(--gold)' : 'var(--text-secondary)';
              $title4 = 'Workday';
          }
          $border4 = $isToday4 ? '1.5px solid var(--gold)' : ($isHol ? 'none' : '1px dashed var(--border)');
        ?>
        <div title="<?= $ds4 ?> — <?= htmlspecialchars($title4) ?>"
          style="border:<?= $border4 ?>;background:<?= $bg ?>;border-radius:3px;text-align:center;font-size:9px;font-weight:<?= $isToday4?'800':'500' ?>;color:<?= $col ?>;padding:3px 0;cursor:<?= $isHol||$isToday4?'help':'default' ?>;line-height:1.2;min-height:18px;"
          <?php if ($isHol): ?>onclick="document.querySelector('[data-date-add]').value='<?= $ds4 ?>'"<?php endif; ?>
        ><?= $day ?></div>
        <?php endfor; ?>
      </div>
    </div>
    <?php endfor; ?>
    </div>
  </div>
</div>

<!-- How it works callout -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('info') ?> How Holiday Exclusions Work</div>
  </div>
  <div class="card-body" style="font-size:13px;line-height:1.8;color:var(--text-secondary);">
    <p>When a day is marked as a holiday or closure, it is automatically excluded from compliance calculations across the system. Specifically:</p>
    <ul style="margin:10px 0 0 20px;display:flex;flex-direction:column;gap:6px;">
      <li><strong>Staff dashboards</strong> — the heatmap shows holidays in gold rather than red (missed), so staff are not penalised.</li>
      <li><strong>Scorecards & compliance %</strong> — holidays are subtracted from the denominator when calculating submission rates.</li>
      <li><strong>Low performer alerts</strong> — a streak of missed days is not counted if those days include a holiday.</li>
      <li><strong>Submission forms</strong> — the weekend check respects holidays; the submit button is hidden on marked closure days.</li>
    </ul>
    <p style="margin-top:10px;">Disabling a holiday (eye icon) keeps the record but re-includes that day in compliance tracking without deleting the entry.</p>
  </div>
</div>

<script>
function fillPreset(date, name, type) {
  document.querySelector('input[name="holiday_date"]').value = date;
  document.querySelector('input[name="name"]').value = name;
  document.querySelector('select[name="type"]').value = type;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
