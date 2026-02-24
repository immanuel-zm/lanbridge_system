<?php
// ── Process POST first — BEFORE any HTML output ───────────────
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle    = 'Departments';
$pageSubtitle = 'Manage college departments';
requireRole(1);

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $code   = strtoupper(trim($_POST['code'] ?? ''));
    $desc   = trim($_POST['description'] ?? '');

    if ($action === 'add') {
        if (!$name || !$code) { $error = 'Name and code are required.'; }
        else {
            try {
                $db->prepare("INSERT INTO departments (name,code,description) VALUES (?,?,?)")->execute([$name,$code,$desc]);
                setFlash('success','✅ Department added successfully.');
                header('Location: departments.php'); exit;
            } catch (PDOException $e) { $error = 'Code already exists or error occurred.'; }
        }
    }
    if ($action === 'delete') {
        $delId = (int)$_POST['dept_id'];
        $db->prepare("DELETE FROM departments WHERE id=?")->execute([$delId]);
        setFlash('success','Department deleted.'); header('Location: departments.php'); exit;
    }
}

// ── Now load the shared layout (outputs HTML) ───────────────
require_once __DIR__ . '/../includes/header.php';

$depts = $db->query(
    "SELECT d.*, COUNT(DISTINCT u.id) AS staff_count FROM departments d LEFT JOIN users u ON u.department_id=d.id AND u.is_active=1 GROUP BY d.id ORDER BY d.name"
)->fetchAll();
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openModal('addDeptModal')"><?= icon('layers',15) ?> Add Department</button>
</div>

<?php if ($error): ?><div class="alert alert-danger mb-24"><?= sanitize($error) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title"><?= icon('layers') ?> All Departments</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Department</th><th>Code</th><th>Description</th><th>Staff Count</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($depts as $d): ?>
        <tr>
          <td class="td-bold"><?= sanitize($d['name']) ?></td>
          <td><span class="badge badge-gold"><?= sanitize($d['code']) ?></span></td>
          <td class="td-muted text-sm"><?= sanitize($d['description']??'—') ?></td>
          <td><span class="font-display" style="font-size:20px;color:var(--gold);"><?= (int)$d['staff_count'] ?></span></td>
          <td>
            <?php if ($d['staff_count'] == 0): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action"  value="delete">
              <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this department?"><?= icon('x',13) ?> Delete</button>
            </form>
            <?php else: ?>
            <span class="text-muted text-sm">Has staff</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addDeptModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><?= icon('layers',16) ?> Add Department</div>
      <button class="modal-close" onclick="closeModal('addDeptModal')"><?= icon('x',18) ?></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Department Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Human Resources"></div>
        <div class="form-group"><label class="form-label">Code *</label><input type="text" name="code" class="form-control" required placeholder="e.g. HR" style="text-transform:uppercase;"></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addDeptModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
