<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!isItRole($user)) {
    header('Location: '.SITE_URL.'/portals/'.getRoleDashboard($user['role_slug'], $user)); exit;
}

$isItAdmin = in_array($user['role_slug'], ['it_admin','ceo','principal']);
$uid       = (int)$user['id'];
$db        = getDB();

$pageTitle    = 'Asset Register';
$pageSubtitle = 'IT Asset Inventory & Assignment';

// ── POST actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isItAdmin) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_asset') {
        $tag     = strtoupper(trim($_POST['asset_tag'] ?? ''));
        $type    = $_POST['asset_type'] ?? 'other';
        $make    = trim($_POST['make'] ?? '');
        $model   = trim($_POST['model'] ?? '');
        $serial  = trim($_POST['serial_number'] ?? '');
        $pDate   = $_POST['purchase_date'] ?: null;
        $pCost   = abs((float)($_POST['purchase_cost'] ?? 0)) ?: null;
        $warrant = $_POST['warranty_expiry'] ?: null;
        $loc     = trim($_POST['location'] ?? '');
        $cond    = $_POST['condition_status'] ?? 'good';
        $notes   = trim($_POST['notes'] ?? '');
        $deptId  = (int)($_POST['department_id'] ?? 0) ?: null;
        $assignTo= (int)($_POST['assigned_to'] ?? 0) ?: null;

        $validTypes = ['laptop','desktop','printer','server','switch','projector','phone','tablet','other'];
        if (!in_array($type, $validTypes)) $type = 'other';
        $validConds = ['new','good','fair','poor','decommissioned'];
        if (!in_array($cond, $validConds)) $cond = 'good';

        if ($tag && $make) {
            try {
                $db->prepare(
                    "INSERT INTO it_assets
                     (asset_tag,asset_type,make,model,serial_number,purchase_date,purchase_cost,
                      warranty_expiry,assigned_to,department_id,location,condition_status,notes)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([$tag,$type,$make,$model,$serial,$pDate,$pCost,$warrant,$assignTo,$deptId,$loc,$cond,$notes]);
                logActivity($uid,'IT_ASSET_ADDED','Asset '.$tag.' — '.$make.' '.$model.' added');
                setFlash('success','✅ Asset '.$tag.' added successfully.');
            } catch (PDOException $e) {
                setFlash('danger','❌ Asset tag already exists or error: '.$e->getMessage());
            }
        } else {
            setFlash('danger','❌ Asset tag and make are required.');
        }
        header('Location: it_assets.php'); exit;
    }

    if ($action === 'update_asset') {
        $id      = (int)($_POST['asset_id'] ?? 0);
        $cond    = $_POST['condition_status'] ?? 'good';
        $loc     = trim($_POST['location'] ?? '');
        $assignTo= (int)($_POST['assigned_to'] ?? 0) ?: null;
        $deptId  = (int)($_POST['department_id'] ?? 0) ?: null;
        $notes   = trim($_POST['notes'] ?? '');
        $warrant = $_POST['warranty_expiry'] ?: null;

        $db->prepare(
            "UPDATE it_assets SET condition_status=?,location=?,assigned_to=?,department_id=?,notes=?,warranty_expiry=?,updated_at=NOW() WHERE id=?"
        )->execute([$cond,$loc,$assignTo,$deptId,$notes,$warrant,$id]);
        logActivity($uid,'IT_ASSET_UPDATED','Asset ID '.$id.' updated');
        setFlash('success','✅ Asset updated.');
        header('Location: it_assets.php'); exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

// ── Filter ────────────────────────────────────────────────────
$filterType = $_GET['type'] ?? '';
$filterCond = $_GET['cond'] ?? '';
$filterDept = (int)($_GET['dept'] ?? 0);
$search     = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($filterType) { $where[] = 'a.asset_type=?'; $params[] = $filterType; }
if ($filterCond) { $where[] = 'a.condition_status=?'; $params[] = $filterCond; }
if ($filterDept) { $where[] = 'a.department_id=?'; $params[] = $filterDept; }
if ($search)     { $where[] = '(a.asset_tag LIKE ? OR a.make LIKE ? OR a.model LIKE ? OR a.serial_number LIKE ?)';
                   $s = "%$search%"; $params = array_merge($params, [$s,$s,$s,$s]); }
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$assetsStmt = $db->prepare(
    "SELECT a.*,
            u.first_name AS assigned_first, u.last_name AS assigned_last,
            d.name AS dept_name
     FROM it_assets a
     LEFT JOIN users u ON a.assigned_to=u.id
     LEFT JOIN departments d ON a.department_id=d.id
     $whereSql
     ORDER BY a.created_at DESC
     LIMIT 200"
);
$assetsStmt->execute($params);
$assets = $assetsStmt->fetchAll();

// Summary counts
$totalAssets   = (int)$db->query("SELECT COUNT(*) FROM it_assets")->fetchColumn();
$assigned      = (int)$db->query("SELECT COUNT(*) FROM it_assets WHERE assigned_to IS NOT NULL")->fetchColumn();
$decommissioned= (int)$db->query("SELECT COUNT(*) FROM it_assets WHERE condition_status='decommissioned'")->fetchColumn();
$warrantyExpiring = (int)$db->query("SELECT COUNT(*) FROM it_assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 90 DAY)")->fetchColumn();

// Lists for selects
$allDepts = $db->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();
$allUsers = $db->query("SELECT u.id, u.first_name, u.last_name, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.is_active=1 ORDER BY u.first_name")->fetchAll();

$assetTypes = ['laptop','desktop','printer','server','switch','projector','phone','tablet','other'];
$conditions = ['new','good','fair','poor','decommissioned'];

function condBadge(string $c): string {
    $map = [
        'new'            => ['badge-success', 'New'],
        'good'           => ['badge-info',    'Good'],
        'fair'           => ['badge-warning',  'Fair'],
        'poor'           => ['badge-danger',   'Poor'],
        'decommissioned' => ['badge-muted',    'Decommissioned'],
    ];
    [$cls,$label] = $map[$c] ?? ['badge-muted',$c];
    return "<span class=\"badge $cls\" style=\"font-size:10px;\">$label</span>";
}

function typeIcon(string $t): string {
    $map = ['laptop'=>'💻','desktop'=>'🖥️','printer'=>'🖨️','server'=>'🗄️',
            'switch'=>'🔀','projector'=>'📽️','phone'=>'📱','tablet'=>'📟','other'=>'🔧'];
    return $map[$t] ?? '🔧';
}
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card gold">
    <div class="stat-top">
      <div><div class="stat-number"><?= $totalAssets ?></div><div class="stat-label">Total Assets</div></div>
      <div class="stat-icon"><?= icon('layers',20) ?></div>
    </div>
    <div class="stat-delta"><?= $assigned ?> currently assigned</div>
  </div>
  <div class="stat-card green">
    <div class="stat-top">
      <div><div class="stat-number"><?= $assigned ?></div><div class="stat-label">Assigned</div></div>
      <div class="stat-icon"><?= icon('users',20) ?></div>
    </div>
    <div class="stat-delta"><?= $totalAssets - $assigned ?> unassigned</div>
  </div>
  <div class="stat-card <?= $warrantyExpiring > 0 ? 'orange' : 'blue' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $warrantyExpiring ?></div><div class="stat-label">Warranty Expiring</div></div>
      <div class="stat-icon"><?= icon('alert-triangle',20) ?></div>
    </div>
    <div class="stat-delta">Within next 90 days</div>
  </div>
  <div class="stat-card <?= $decommissioned > 0 ? 'orange' : 'blue' ?>">
    <div class="stat-top">
      <div><div class="stat-number"><?= $decommissioned ?></div><div class="stat-label">Decommissioned</div></div>
      <div class="stat-icon"><?= icon('trash',20) ?></div>
    </div>
    <div class="stat-delta">Retired from service</div>
  </div>
</div>

<!-- Filters + Add Asset -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title"><?= icon('search') ?> Filter Assets</div>
    <?php if ($isItAdmin): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('addAssetModal')"><?= icon('plus',13) ?> Add Asset</button>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <label class="form-label" style="font-size:11px;">Search</label>
        <input type="text" name="q" class="form-control" style="width:180px;" placeholder="Tag, make, serial…" value="<?= sanitize($search) ?>">
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Type</label>
        <select name="type" class="form-control" style="width:130px;">
          <option value="">All Types</option>
          <?php foreach ($assetTypes as $t): ?>
          <option value="<?= $t ?>" <?= $filterType===$t?'selected':'' ?>><?= typeIcon($t).' '.ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Condition</label>
        <select name="cond" class="form-control" style="width:130px;">
          <option value="">All Conditions</option>
          <?php foreach ($conditions as $c): ?>
          <option value="<?= $c ?>" <?= $filterCond===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" style="font-size:11px;">Department</label>
        <select name="dept" class="form-control" style="width:160px;">
          <option value="">All Depts</option>
          <?php foreach ($allDepts as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDept==$d['id']?'selected':'' ?>><?= sanitize($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><?= icon('search',13) ?> Filter</button>
      <a href="it_assets.php" class="btn btn-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<!-- Assets Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('layers') ?> Asset Register <span class="badge badge-muted" style="margin-left:6px;"><?= count($assets) ?> shown</span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Asset Tag</th>
          <th>Type</th>
          <th>Make / Model</th>
          <th>Department</th>
          <th>Assigned To</th>
          <th>Condition</th>
          <th>Warranty</th>
          <?php if ($isItAdmin): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
        <tr><td colspan="8">
          <div class="empty-state" style="padding:40px;">
            <?= icon('layers',36) ?>
            <h3>No assets found</h3>
            <p><?= $search || $filterType || $filterCond || $filterDept ? 'Try adjusting your filters.' : 'Add your first asset to get started.' ?></p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($assets as $a):
          $warrantyExpiring = $a['warranty_expiry'] && strtotime($a['warranty_expiry']) <= strtotime('+90 days') && strtotime($a['warranty_expiry']) >= time();
          $warrantyExpired  = $a['warranty_expiry'] && strtotime($a['warranty_expiry']) < time();
        ?>
        <tr>
          <td>
            <div style="font-family:monospace;font-size:13px;font-weight:700;color:var(--gold);"><?= sanitize($a['asset_tag']) ?></div>
            <?php if ($a['serial_number']): ?>
            <div class="td-muted" style="font-size:10px;">S/N: <?= sanitize($a['serial_number']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span style="font-size:18px;"><?= typeIcon($a['asset_type']) ?></span>
            <div class="td-muted" style="font-size:10px;margin-top:2px;"><?= ucfirst($a['asset_type']) ?></div>
          </td>
          <td>
            <div class="td-bold"><?= sanitize($a['make']) ?></div>
            <div class="td-muted"><?= sanitize($a['model'] ?? '—') ?></div>
          </td>
          <td class="td-muted text-sm"><?= sanitize($a['dept_name'] ?? '—') ?></td>
          <td>
            <?php if ($a['assigned_first']): ?>
            <div style="font-size:13px;font-weight:500;"><?= sanitize($a['assigned_first'].' '.$a['assigned_last']) ?></div>
            <?php else: ?>
            <span class="badge badge-muted">Unassigned</span>
            <?php endif; ?>
            <?php if ($a['location']): ?>
            <div class="td-muted" style="font-size:10px;"><?= sanitize($a['location']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= condBadge($a['condition_status']) ?></td>
          <td>
            <?php if ($a['warranty_expiry']): ?>
            <span style="font-size:12px;color:<?= $warrantyExpired?'var(--danger)':($warrantyExpiring?'var(--warning)':'var(--success)') ?>;">
              <?= $warrantyExpired ? '⚠️ Expired' : ($warrantyExpiring ? '⏳ Expiring' : '✓') ?><br>
              <span style="font-size:10px;color:var(--text-muted);"><?= date('M d, Y',strtotime($a['warranty_expiry'])) ?></span>
            </span>
            <?php else: ?>
            <span class="td-muted">—</span>
            <?php endif; ?>
          </td>
          <?php if ($isItAdmin): ?>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($a)) ?>)">
              <?= icon('edit',12) ?> Edit
            </button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($isItAdmin): ?>
<!-- Add Asset Modal -->
<div class="modal-overlay" id="addAssetModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('plus') ?> Add New Asset</div>
      <button class="modal-close" onclick="closeModal('addAssetModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="add_asset">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Asset Tag <span style="color:var(--danger);">*</span></label>
            <input type="text" name="asset_tag" class="form-control" placeholder="e.g. LAP-001" required>
          </div>
          <div class="form-group">
            <label class="form-label">Asset Type</label>
            <select name="asset_type" class="form-control">
              <?php foreach ($assetTypes as $t): ?>
              <option value="<?= $t ?>"><?= typeIcon($t).' '.ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Make <span style="color:var(--danger);">*</span></label>
            <input type="text" name="make" class="form-control" placeholder="e.g. Dell, HP, Lenovo" required>
          </div>
          <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" placeholder="e.g. Latitude 5420">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Serial Number</label>
            <input type="text" name="serial_number" class="form-control" placeholder="Manufacturer serial">
          </div>
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select name="condition_status" class="form-control">
              <?php foreach ($conditions as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Purchase Date</label>
            <input type="date" name="purchase_date" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Purchase Cost (ZMW)</label>
            <input type="number" name="purchase_cost" class="form-control" placeholder="0.00" min="0" step="0.01">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Warranty Expiry</label>
            <input type="date" name="warranty_expiry" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Room 201, Block B">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Department</label>
            <select name="department_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign to Staff</label>
            <select name="assigned_to" class="form-control">
              <option value="">— Unassigned —</option>
              <?php foreach ($allUsers as $u): ?>
              <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'].' '.$u['last_name']) ?> (<?= sanitize($u['dept_name']??'') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes…"></textarea>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('addAssetModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><?= icon('plus',14) ?> Add Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Asset Modal -->
<div class="modal-overlay" id="editAssetModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('edit') ?> Update Asset — <span id="editAssetTag"></span></div>
      <button class="modal-close" onclick="closeModal('editAssetModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_asset">
        <input type="hidden" name="asset_id" id="editAssetId">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Condition</label>
            <select name="condition_status" class="form-control" id="editCondition">
              <?php foreach ($conditions as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" id="editLocation" placeholder="e.g. Room 201">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Department</label>
            <select name="department_id" class="form-control" id="editDept">
              <option value="">— None —</option>
              <?php foreach ($allDepts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign to Staff</label>
            <select name="assigned_to" class="form-control" id="editAssigned">
              <option value="">— Unassigned —</option>
              <?php foreach ($allUsers as $u): ?>
              <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Warranty Expiry</label>
          <input type="date" name="warranty_expiry" class="form-control" id="editWarranty">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" id="editNotes"></textarea>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('editAssetModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><?= icon('save',14) ?> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(asset) {
  document.getElementById('editAssetId').value  = asset.id;
  document.getElementById('editAssetTag').textContent = asset.asset_tag;
  document.getElementById('editCondition').value = asset.condition_status || 'good';
  document.getElementById('editLocation').value  = asset.location || '';
  document.getElementById('editDept').value      = asset.department_id || '';
  document.getElementById('editAssigned').value  = asset.assigned_to || '';
  document.getElementById('editWarranty').value  = asset.warranty_expiry ? asset.warranty_expiry.substring(0,10) : '';
  document.getElementById('editNotes').value     = asset.notes || '';
  openModal('editAssetModal');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
