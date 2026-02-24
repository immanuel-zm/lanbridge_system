<?php
// ── Process POST first — BEFORE any HTML output ───────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(1);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        $db->prepare("UPDATE system_settings SET setting_value=? WHERE setting_key=?")
           ->execute([trim($value), $key]);
    }
    setFlash('success', '✅ Settings saved successfully.');
    header('Location: settings.php');
    exit;
}

// ── Now load the shared layout (outputs HTML) ─────────────────
$pageTitle    = 'System Settings';
$pageSubtitle = 'Configure system-wide settings';
require_once __DIR__ . '/../includes/header.php';

// Fetch settings AFTER header (no redirect needed here)
$settings2 = [];
foreach ($db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll() as $row) {
    $settings2[$row['setting_key']] = $row['setting_value'];
}
?>

<div style="max-width:700px;">
  <form method="POST">
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('settings') ?> General Settings</div>
      </div>
      <div class="card-body">

        <div class="form-group">
          <label class="form-label">System Name</label>
          <input type="text" name="site_name" class="form-control"
            value="<?= sanitize($settings2['site_name'] ?? 'Lanbridge College KPI System') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Site URL</label>
          <input type="text" name="site_url" class="form-control"
            value="<?= sanitize($settings2['site_url'] ?? SITE_URL) ?>">
          <div class="form-helper">Base URL — no trailing slash</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Timezone</label>
            <input type="text" name="timezone" class="form-control"
              value="<?= sanitize($settings2['timezone'] ?? 'Africa/Lusaka') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Academic Year</label>
            <input type="text" name="academic_year" class="form-control"
              value="<?= sanitize($settings2['academic_year'] ?? date('Y')) ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Report Deadline Hour (24h format)</label>
          <input type="number" name="report_deadline_hour" class="form-control"
            min="1" max="23"
            value="<?= sanitize($settings2['report_deadline_hour'] ?? '17') ?>">
          <div class="form-helper">e.g. 17 = 5:00 PM deadline for daily reports</div>
        </div>

        <div class="form-group">
          <label class="form-label">Maintenance Mode</label>
          <select name="maintenance_mode" class="form-control">
            <option value="0" <?= ($settings2['maintenance_mode'] ?? '0') === '0' ? 'selected' : '' ?>>Off — System is live</option>
            <option value="1" <?= ($settings2['maintenance_mode'] ?? '0') === '1' ? 'selected' : '' ?>>On — Maintenance mode</option>
          </select>
        </div>

      </div>
    </div>

    <!-- Security Config (read-only display) -->
    <div class="card mb-24">
      <div class="card-header">
        <div class="card-title"><?= icon('shield') ?> Security Configuration</div>
      </div>
      <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:16px;">
          <?= icon('shield', 15) ?>
          These values are stored in the <strong>security_config</strong> table. Edit them directly in phpMyAdmin if needed.
        </div>
        <?php
        $secSettings = $db->query(
            "SELECT setting_key, setting_value, description FROM security_config ORDER BY setting_key"
        )->fetchAll();
        foreach ($secSettings as $s): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid var(--border);">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
              <?= sanitize($s['description'] ?? $s['setting_key']) ?>
            </div>
            <div class="text-muted text-sm"><?= sanitize($s['setting_key']) ?></div>
          </div>
          <span class="badge badge-gold"><?= sanitize($s['setting_value']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
      <?= icon('settings', 16) ?> Save Settings
    </button>

  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>