<?php
// ── Process POST first — BEFORE any HTML output ───────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(2);

$db        = getDB();
$user      = currentUser();
$isCEO     = $user['role_level'] == 1;
$error = $success = '';

// ── Handle POST actions (CEO only) ────────────────────────────
if ($isCEO && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add or Edit user
    if (in_array($action, ['add','edit'])) {
        $editId    = (int)($_POST['edit_id'] ?? 0);
        $firstName = trim($_POST['first_name']  ?? '');
        $lastName  = trim($_POST['last_name']   ?? '');
        $empId     = trim($_POST['employee_id'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $roleId    = (int)($_POST['role_id']       ?? 4);
        $deptId    = (int)($_POST['department_id'] ?? 0) ?: null;
        $supId     = (int)($_POST['supervisor_id'] ?? 0) ?: null;
        $phone     = trim($_POST['phone']    ?? '');
        $position  = trim($_POST['position'] ?? '');

        if (!$firstName || !$lastName || !$empId || !$email) {
            $error = 'First name, last name, employee ID and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                if ($action === 'add') {
                    $tempPass = 'Lanbridge@' . rand(1000,9999);
                    $hash     = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    $stmt = $db->prepare(
                        "INSERT INTO users (employee_id,first_name,last_name,email,password_hash,role_id,department_id,supervisor_id,phone,position,force_password_change,join_date)
                         VALUES (?,?,?,?,?,?,?,?,?,?,1,CURDATE())"
                    );
                    $stmt->execute([$empId,$firstName,$lastName,$email,$hash,$roleId,$deptId,$supId,$phone,$position]);
                    $newId = $db->lastInsertId();
                    logActivity($user['id'],'USER_CREATED','Created user: '.$email);
                    sendNotification($newId,'Welcome to Lanbridge KPI','Your account has been created. Please log in and change your password.','info', SITE_URL.'/login.php');
                    setFlash('success', "✅ Staff member added! Temporary password: <strong>$tempPass</strong> — share this securely.");
                } else {
                    $db->prepare(
                        "UPDATE users SET employee_id=?,first_name=?,last_name=?,email=?,role_id=?,department_id=?,supervisor_id=?,phone=?,position=?,updated_at=NOW() WHERE id=?"
                    )->execute([$empId,$firstName,$lastName,$email,$roleId,$deptId,$supId,$phone,$position,$editId]);
                    logActivity($user['id'],'USER_UPDATED','Updated user ID: '.$editId);
                    setFlash('success','✅ Staff member updated successfully.');
                }
            } catch (PDOException $e) {
                $error = str_contains($e->getMessage(),'Duplicate') ? 'Email or Employee ID already exists.' : 'Could not save. Please try again.';
            }
        }
    }

    // Toggle active/inactive
    if ($action === 'toggle') {
        $toggleId  = (int)$_POST['toggle_id'];
        $newStatus = (int)$_POST['new_status'];
        if ($toggleId !== $user['id']) {
            $db->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$newStatus,$toggleId]);
            logActivity($user['id'],'USER_'.($newStatus?'ACTIVATED':'DEACTIVATED'),'User ID '.$toggleId);
            setFlash('success', $newStatus ? '✅ Account activated.' : '⚠️ Account deactivated.');
        }
    }

    // Reset password
    if ($action === 'reset_password') {
        $resetId  = (int)$_POST['reset_id'];
        $tempPass = 'Lanbridge@' . rand(1000,9999);
        $hash     = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $db->prepare("UPDATE users SET password_hash=?, force_password_change=1 WHERE id=?")->execute([$hash,$resetId]);
        logActivity($user['id'],'PASSWORD_RESET','Reset password for user ID '.$resetId);
        setFlash('success',"🔑 Password reset! New temporary password: <strong>$tempPass</strong> — share this securely.");
    }

    // ── CSV Bulk Import ────────────────────────────────────────
    if ($action === 'csv_import' && isset($_FILES['csv_file'])) {
        $csvFile = $_FILES['csv_file'];
        $importResults = ['added' => 0, 'errors' => [], 'passwords' => []];

        if ($csvFile['error'] === UPLOAD_ERR_OK && $csvFile['size'] > 0) {
            $handle = fopen($csvFile['tmp_name'], 'r');
            $header = fgetcsv($handle); // skip header row
            $rowNum = 1;

            // Build lookup maps
            $roleMap = [];
            foreach ($db->query("SELECT id, name, slug FROM roles")->fetchAll() as $r) {
                $roleMap[strtolower($r['name'])] = $r['id'];
                $roleMap[strtolower($r['slug'])] = $r['id'];
            }
            $deptMap = [];
            foreach ($db->query("SELECT id, name, code FROM departments")->fetchAll() as $d) {
                $deptMap[strtolower($d['name'])] = $d['id'];
                $deptMap[strtolower($d['code'])] = $d['id'];
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count($row) < 4) { $importResults['errors'][] = "Row $rowNum: Too few columns (need at least 4)"; continue; }

                [$empId, $firstName, $lastName, $email] = array_map('trim', $row);
                $roleRaw = trim($row[4] ?? 'staff');
                $deptRaw = trim($row[5] ?? '');
                $phone   = trim($row[6] ?? '');
                $position = trim($row[7] ?? '');

                if (!$empId || !$firstName || !$lastName || !$email) {
                    $importResults['errors'][] = "Row $rowNum: Missing required fields (employee_id, first_name, last_name, email)";
                    continue;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $importResults['errors'][] = "Row $rowNum: Invalid email — $email";
                    continue;
                }

                $roleId = $roleMap[strtolower($roleRaw)] ?? $roleMap['staff'] ?? null;
                $deptId2 = $deptMap[strtolower($deptRaw)] ?? null;

                if (!$roleId) { $importResults['errors'][] = "Row $rowNum: Unknown role '$roleRaw'"; continue; }

                try {
                    $tempPass = 'Lanbridge@' . rand(1000, 9999);
                    $hash     = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    $stmt = $db->prepare(
                        "INSERT INTO users (employee_id,first_name,last_name,email,password_hash,role_id,department_id,phone,position,force_password_change,join_date)
                         VALUES (?,?,?,?,?,?,?,?,?,1,CURDATE())"
                    );
                    $stmt->execute([$empId, $firstName, $lastName, $email, $hash, $roleId, $deptId2, $phone, $position]);
                    $newId = $db->lastInsertId();
                    logActivity($user['id'], 'USER_CREATED', 'CSV import: '.$email);
                    sendNotification($newId, 'Welcome to Lanbridge KPI', 'Your account has been created. Please log in and change your password.', 'info', SITE_URL.'/login.php');
                    $importResults['added']++;
                    $importResults['passwords'][] = [$firstName.' '.$lastName, $email, $tempPass];
                } catch (PDOException $e) {
                    $importResults['errors'][] = "Row $rowNum ($email): " . (str_contains($e->getMessage(), 'Duplicate') ? 'Email or Employee ID already exists' : 'Database error');
                }
            }
            fclose($handle);
        } else {
            $importResults['errors'][] = 'No file uploaded or file error.';
        }

        // Store results in session to display after redirect
        $_SESSION['csv_import_results'] = $importResults;
        header('Location: manage_users.php?import=done'); exit;
    }

    if (!$error) { header('Location: manage_users.php'); exit; }
}

// ── Now load the shared layout (outputs HTML) ─────────────────
$pageTitle    = 'Manage Staff';
$pageSubtitle = 'Add, edit and manage all user accounts';
require_once __DIR__ . '/../includes/header.php';

// ── Fetch data ────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$whereSearch = $search
    ? "AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.employee_id LIKE '%$search%' OR d.name LIKE '%$search%')"
    : '';

$users = $db->query(
    "SELECT u.*, r.name AS role_name, r.slug AS role_slug, r.level AS role_level,
            d.name AS dept_name,
            CONCAT(s.first_name,' ',s.last_name) AS supervisor_name
     FROM users u
     JOIN roles r ON u.role_id=r.id
     LEFT JOIN departments d ON u.department_id=d.id
     LEFT JOIN users s ON u.supervisor_id=s.id
     WHERE 1=1 $whereSearch
     ORDER BY r.level, u.first_name"
)->fetchAll();

$roles   = $db->query("SELECT * FROM roles ORDER BY level")->fetchAll();
$depts   = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$supList = $db->query("SELECT id, first_name, last_name FROM users WHERE is_active=1 ORDER BY first_name")->fetchAll();
?>

<!-- CSV Import Results -->
<?php
$csvResults = $_SESSION['csv_import_results'] ?? null;
if ($csvResults) { unset($_SESSION['csv_import_results']); }
if ($csvResults): ?>
<div class="card mb-24" style="border:1px solid <?= $csvResults['added'] > 0 ? 'var(--gold-border)' : 'rgba(232,85,106,0.3)' ?>;">
  <div class="card-header">
    <div class="card-title"><?= icon('upload') ?> CSV Import Results</div>
  </div>
  <div class="card-body">
    <?php if ($csvResults['added'] > 0): ?>
    <div class="alert alert-success" style="margin-bottom:12px;">
      ✅ <strong><?= $csvResults['added'] ?> user<?= $csvResults['added']!==1?'s':'' ?> imported successfully.</strong>
    </div>
    <div style="margin-bottom:12px;">
      <div class="form-label" style="margin-bottom:6px;">Temporary Passwords (share securely — one time only):</div>
      <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
        <table style="width:100%;font-size:12px;">
          <thead><tr style="background:var(--bg-card);">
            <th style="padding:8px 12px;text-align:left;color:var(--text-muted);">Name</th>
            <th style="padding:8px 12px;text-align:left;color:var(--text-muted);">Email</th>
            <th style="padding:8px 12px;text-align:left;color:var(--text-muted);">Temp Password</th>
          </tr></thead>
          <tbody>
            <?php foreach ($csvResults['passwords'] as [$name, $email2, $pass]): ?>
            <tr style="border-top:1px solid var(--border);">
              <td style="padding:7px 12px;"><?= sanitize($name) ?></td>
              <td style="padding:7px 12px;"><?= sanitize($email2) ?></td>
              <td style="padding:7px 12px;font-family:monospace;color:var(--gold);"><?= sanitize($pass) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($csvResults['errors'])): ?>
    <div class="alert alert-danger">
      <strong><?= count($csvResults['errors']) ?> error<?= count($csvResults['errors'])!==1?'s':'' ?>:</strong>
      <ul style="margin:8px 0 0 18px;font-size:12px;">
        <?php foreach ($csvResults['errors'] as $err): ?>
        <li><?= sanitize($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Top controls -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
  <form method="GET" style="display:flex;gap:10px;flex:1;max-width:400px;">
    <div class="input-wrap" style="flex:1;">
      <span class="input-icon"><?= icon('users',15) ?></span>
      <input type="text" name="search" class="form-control" placeholder="Search by name, email, ID, department..." value="<?= sanitize($search) ?>">
    </div>
    <button type="submit" class="btn btn-outline"><?= icon('users',14) ?> Search</button>
    <?php if ($search): ?><a href="manage_users.php" class="btn btn-outline">Clear</a><?php endif; ?>
  </form>
  <?php if ($isCEO): ?>
  <div style="display:flex;gap:8px;">
    <button class="btn btn-outline" onclick="openModal('csvImportModal')">
      <?= icon('upload',15) ?> Bulk Import CSV
    </button>
    <button class="btn btn-primary" onclick="openModal('addModal')">
      <?= icon('users',15) ?> Add Staff Member
    </button>
  </div>
  <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="alert alert-danger mb-24"><?= icon('x',15) ?> <?= sanitize($error) ?></div>
<?php endif; ?>

<!-- Staff Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= icon('users') ?> All Staff <span class="badge badge-muted" style="margin-left:6px;"><?= count($users) ?></span></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Employee</th>
          <th>Email</th>
          <th>Role</th>
          <th>Department</th>
          <th>Supervisor</th>
          <th>Last Login</th>
          <th>Status</th>
          <?php if ($isCEO): ?><th>Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar"><?= getInitials($u['first_name'],$u['last_name']) ?></div>
              <div>
                <div class="td-bold"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></div>
                <div class="td-muted"><?= sanitize($u['employee_id']) ?></div>
              </div>
            </div>
          </td>
          <td class="td-muted text-sm"><?= sanitize($u['email']) ?></td>
          <td>
            <span class="badge badge-<?= match($u['role_slug']){
              'ceo'=>'ceo','vice_principal'=>'vp','dept_head'=>'head',default=>'staff'
            } ?>"><?= sanitize($u['role_name']) ?></span>
          </td>
          <td class="td-muted"><?= sanitize($u['dept_name'] ?? '—') ?></td>
          <td class="td-muted text-sm"><?= sanitize($u['supervisor_name'] ?? '—') ?></td>
          <td class="td-muted text-sm"><?= $u['last_login'] ? formatDate($u['last_login'],'M d, Y') : 'Never' ?></td>
          <td>
            <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <?php if ($isCEO): ?>
          <td>
            <div style="display:flex;gap:6px;">
              <!-- Edit -->
              <button class="btn btn-outline btn-icon btn-sm" title="Edit"
                onclick="openEditModal(<?= htmlspecialchars(json_encode($u),ENT_QUOTES) ?>)">
                <?= icon('settings',13) ?>
              </button>
              <!-- Toggle active -->
              <?php if ($u['id'] != $user['id']): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action"    value="toggle">
                <input type="hidden" name="toggle_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="new_status" value="<?= $u['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn <?= $u['is_active']?'btn-danger':'btn-success' ?> btn-icon btn-sm"
                  title="<?= $u['is_active']?'Deactivate':'Activate' ?>"
                  data-confirm="<?= $u['is_active']?'Deactivate this account?':'Activate this account?' ?>">
                  <?= icon($u['is_active']?'x':'check-square',13) ?>
                </button>
              </form>
              <!-- Reset password -->
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action"   value="reset_password">
                <input type="hidden" name="reset_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-info btn-icon btn-sm" title="Reset Password"
                  data-confirm="Reset password for <?= sanitize($u['first_name']) ?>? A temporary password will be generated.">
                  <?= icon('lock',13) ?>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($isCEO): ?>
<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('users',16) ?> Add Staff Member</div>
      <button class="modal-close" onclick="closeModal('addModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required placeholder="Jane"></div>
          <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required placeholder="Mwanza"></div>
        </div>
        <div class="form-group"><label class="form-label">Employee ID *</label><input type="text" name="employee_id" class="form-control" required placeholder="LC-006"></div>
        <div class="form-group"><label class="form-label">Email Address *</label><input type="email" name="email" class="form-control" required placeholder="jane@lanbridgecollegezambia.com"></div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Role *</label>
            <select name="role_id" class="form-control" required>
              <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>" <?= $r['slug']==='staff'?'selected':'' ?>><?= sanitize($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Supervisor</label>
          <select name="supervisor_id" class="form-control">
            <option value="">— None —</option>
            <?php foreach ($supList as $s): ?>
            <option value="<?= $s['id'] ?>"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="+260 97X XXX XXX"></div>
          <div class="form-group"><label class="form-label">Position / Title</label><input type="text" name="position" class="form-control" placeholder="Lecturer, IT Technician..."></div>
        </div>
        <div class="alert alert-info" style="margin-top:4px;"><?= icon('lock',14) ?> A temporary password will be auto-generated. You must share it securely with the new staff member.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('users',14) ?> Add Staff Member</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title"><?= icon('settings',16) ?> Edit Staff Member</div>
      <button class="modal-close" onclick="closeModal('editModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action"  value="edit">
      <input type="hidden" name="edit_id" id="editId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" id="editFirstName" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" id="editLastName" class="form-control" required></div>
        </div>
        <div class="form-group"><label class="form-label">Employee ID *</label><input type="text" name="employee_id" id="editEmpId" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Email Address *</label><input type="email" name="email" id="editEmail" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Role *</label>
            <select name="role_id" id="editRoleId" class="form-control" required>
              <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id'] ?>"><?= sanitize($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" id="editDeptId" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Supervisor</label>
          <select name="supervisor_id" id="editSupId" class="form-control">
            <option value="">— None —</option>
            <?php foreach ($supList as $s): ?>
            <option value="<?= $s['id'] ?>"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
          <div class="form-group"><label class="form-label">Position / Title</label><input type="text" name="position" id="editPosition" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('settings',14) ?> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(u) {
  document.getElementById('editId').value        = u.id;
  document.getElementById('editFirstName').value = u.first_name;
  document.getElementById('editLastName').value  = u.last_name;
  document.getElementById('editEmpId').value     = u.employee_id;
  document.getElementById('editEmail').value     = u.email;
  document.getElementById('editRoleId').value    = u.role_id;
  document.getElementById('editDeptId').value    = u.department_id || '';
  document.getElementById('editSupId').value     = u.supervisor_id || '';
  document.getElementById('editPhone').value     = u.phone || '';
  document.getElementById('editPosition').value  = u.position || '';
  openModal('editModal');
}
</script>
<?php endif; ?>

<?php if ($isCEO): ?>
<!-- CSV Bulk Import Modal -->
<div class="modal-overlay" id="csvImportModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div>
        <div class="modal-title"><?= icon('upload',18) ?> Bulk Import Staff via CSV</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">Upload a CSV file to add multiple staff at once</div>
      </div>
      <button class="modal-close" onclick="closeModal('csvImportModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="csv_import">
      <div class="modal-body">

        <!-- Format guide -->
        <div class="alert alert-info" style="font-size:12px;margin-bottom:14px;">
          <?= icon('info',13) ?>
          <div style="margin-left:6px;">
            <strong>Required CSV format</strong> — include a header row, then one staff member per row:
          </div>
        </div>
        <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius);padding:10px 14px;font-family:monospace;font-size:11px;color:var(--text-secondary);margin-bottom:14px;overflow-x:auto;white-space:nowrap;">
          employee_id, first_name, last_name, email, role, department, phone, position<br>
          LC-010, Jane, Banda, jane.banda@lanbridge.ac.zm, staff, Academic, 0977000001, Lecturer<br>
          LC-011, Peter, Mwale, p.mwale@lanbridge.ac.zm, dept_head, Finance, , Finance Head
        </div>

        <!-- Column guide -->
        <div style="margin-bottom:14px;font-size:12px;">
          <div class="form-label" style="margin-bottom:6px;">Column guide:</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;color:var(--text-muted);">
            <div><span style="color:var(--gold);">employee_id</span> — required (e.g. LC-010)</div>
            <div><span style="color:var(--gold);">first_name</span> — required</div>
            <div><span style="color:var(--gold);">last_name</span> — required</div>
            <div><span style="color:var(--gold);">email</span> — required, must be unique</div>
            <div><span style="color:var(--gold);">role</span> — role name or slug (default: staff)</div>
            <div><span style="color:var(--gold);">department</span> — dept name or code (optional)</div>
            <div><span style="color:var(--gold);">phone</span> — optional</div>
            <div><span style="color:var(--gold);">position</span> — optional</div>
          </div>
        </div>

        <!-- Valid roles -->
        <div style="margin-bottom:14px;font-size:11px;color:var(--text-muted);">
          <strong style="color:var(--text-secondary);">Valid roles:</strong>
          <?php foreach ($roles as $r): ?>
          <span style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:4px;padding:2px 6px;margin:2px;display:inline-block;"><?= sanitize($r['slug']) ?></span>
          <?php endforeach; ?>
        </div>
        <div style="margin-bottom:16px;font-size:11px;color:var(--text-muted);">
          <strong style="color:var(--text-secondary);">Valid departments:</strong>
          <?php foreach ($depts as $d): ?>
          <span style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:4px;padding:2px 6px;margin:2px;display:inline-block;"><?= sanitize($d['code']) ?></span>
          <?php endforeach; ?>
        </div>

        <!-- File upload -->
        <div class="form-group">
          <label class="form-label">Select CSV File *</label>
          <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required
            style="padding:8px;">
          <div class="form-helper">Max file size: 2MB. Must be a .csv file with UTF-8 encoding.</div>
        </div>

        <div class="alert alert-warning" style="font-size:12px;">
          ⚠️ Each new user gets a temporary password (shown after import). Share these securely — they must change their password on first login.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('csvImportModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('upload',14) ?> Import Staff</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
