<?php
// Admin: hospitals list with search, filter, pagination, add / edit / activate / delete.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

/* ---------------- handle form submissions (POST only, CSRF-checked) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('admin/hospitals.php'); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'save') {
            $name = trim($_POST['name'] ?? '');   $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');   $phone   = trim($_POST['phone'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $email = strtolower(trim($_POST['login_email'] ?? ''));  $pass = $_POST['login_password'] ?? '';

            $err = [];
            if ($name === '' || mb_strlen($name) > 150) $err[] = 'Hospital name is required.';
            if ($address === '') $err[] = 'Address is required.';
            if ($city === '')    $err[] = 'City is required.';
            if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $err[] = 'Phone number looks invalid.';
            if (!$id && $email !== '') {            // optional login for a new hospital
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Login email is invalid.';
                if (strlen($pass) < 8) $err[] = 'Login password must be at least 8 characters.';
                $dup = $pdo->prepare('SELECT 1 FROM users WHERE email = ?'); $dup->execute([$email]);
                if ($dup->fetch()) $err[] = 'That login email is already used.';
            }
            if ($err) { flash('danger', implode(' ', $err)); redirect('admin/hospitals.php'); }

            $pdo->beginTransaction();
            if ($id) {
                $pdo->prepare('UPDATE hospitals SET name=?, address=?, city=?, phone=?, status=? WHERE id=?')
                    ->execute([$name, $address, $city, $phone ?: null, $status, $id]);
                $pdo->prepare('UPDATE users u JOIN hospitals h ON h.user_id = u.id SET u.name=?, u.address=?, u.city=?, u.phone=? WHERE h.id=?')
                    ->execute([$name, $address, $city, $phone ?: null, $id]);
                flash('success', 'Hospital updated.');
            } else {
                $uid = null;
                if ($email !== '') {
                    $pdo->prepare("INSERT INTO users (name,email,password,phone,address,city,role) VALUES (?,?,?,?,?,?,'hospital')")
                        ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $phone ?: null, $address, $city]);
                    $uid = (int) $pdo->lastInsertId();
                }
                $pdo->prepare('INSERT INTO hospitals (user_id,name,address,city,phone,status) VALUES (?,?,?,?,?,?)')
                    ->execute([$uid, $name, $address, $city, $phone ?: null, $status]);
                flash('success', 'Hospital added.');
            }
            $pdo->commit();
        } elseif ($action === 'toggle') {
            $pdo->prepare("UPDATE hospitals SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
            flash('success', 'Hospital status changed.');
        } elseif ($action === 'delete') {
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT user_id FROM hospitals WHERE id = ?'); $q->execute([$id]);
            $uid = $q->fetchColumn();
            $pdo->prepare('DELETE FROM hospitals WHERE id = ?')->execute([$id]);
            if ($uid) $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'hospital'")->execute([$uid]);
            $pdo->commit();
            flash('success', 'Hospital deleted.');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('danger', 'Could not save changes. Please try again.');
    }
    redirect('admin/hospitals.php');
}

/* ---------------- list with search / filter / pagination ---------------- */
$q = trim($_GET['q'] ?? '');  $st = $_GET['status'] ?? '';
$where = []; $args = [];
if ($q !== '') { $where[] = '(h.name LIKE ? OR h.city LIKE ? OR h.address LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
if (in_array($st, ['active', 'inactive'], true)) { $where[] = 'h.status = ?'; $args[] = $st; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM hospitals h $w"); $cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 8);
$stmt = $pdo->prepare("SELECT h.*, u.email FROM hospitals h LEFT JOIN users u ON u.id = h.user_id $w ORDER BY h.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$layout = 'app'; $pageTitle = 'Hospitals';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="mb-0">Hospitals</h2><p class="text-secondary mb-0"><?= $pg['total'] ?> hospital<?= $pg['total'] === 1 ? '' : 's' ?> found</p></div>
  <button class="btn btn-vx" data-bs-toggle="modal" data-bs-target="#hModal" data-fill="#hModal" data-title="Add hospital"><i class="bi bi-plus-lg me-1"></i>Add hospital</button>
</div>

<div class="panel">
  <form class="toolbar" method="get">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, city or address" aria-label="Search">
    <select class="form-select" name="status" aria-label="Status"><option value="">All statuses</option>
      <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Inactive</option></select>
    <button class="btn btn-vx">Filter</button>
    <?php if ($q !== '' || $st !== ''): ?><a class="btn btn-ghost" href="hospitals.php">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-hospital"></i>No hospitals found. Add one to get started.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Hospital</th><th>City</th><th>Phone</th><th>Login</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $h): ?>
      <tr>
        <td><b><?= e($h['name']) ?></b><div class="text-secondary small"><?= e($h['address']) ?></div></td>
        <td><?= e($h['city']) ?></td><td><?= e($h['phone'] ?? '-') ?></td>
        <td class="small"><?= $h['email'] ? e($h['email']) : '<span class="text-secondary">No login</span>' ?></td>
        <td><span class="tag <?= $h['status'] === 'active' ? 'ok' : 'off' ?>"><?= ucfirst($h['status']) ?></span></td>
        <td class="text-end"><div class="act">
          <button class="btn btn-ghost" title="Edit" aria-label="Edit <?= e($h['name']) ?>" data-bs-toggle="modal" data-bs-target="#hModal" data-fill="#hModal" data-title="Edit hospital"
            data-json="<?= e(json_encode(['id' => $h['id'], 'name' => $h['name'], 'address' => $h['address'], 'city' => $h['city'], 'phone' => $h['phone'], 'status' => $h['status']])) ?>"><i class="bi bi-pencil"></i></button>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $h['id'] ?>">
            <button class="btn btn-ghost" title="<?= $h['status'] === 'active' ? 'Deactivate' : 'Activate' ?>"><i class="bi bi-<?= $h['status'] === 'active' ? 'toggle-on' : 'toggle-off' ?>"></i></button></form>
          <form method="post" data-confirm="Delete <?= e($h['name']) ?>? Its login account and bookings will also be removed."><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $h['id'] ?>">
            <button class="btn btn-ghost text-danger" title="Delete" aria-label="Delete <?= e($h['name']) ?>"><i class="bi bi-trash"></i></button></form>
        </div></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>

<!-- Add / edit modal -->
<div class="modal fade" id="hModal" tabindex="-1" aria-labelledby="hTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="">
    <div class="modal-header"><h5 class="modal-title" id="hTitle">Add hospital</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-label" for="h-name">Hospital name</label><input class="form-control" id="h-name" name="name" required maxlength="150"></div>
      <div class="col-sm-6"><label class="form-label" for="h-city">City</label><input class="form-control" id="h-city" name="city" required></div>
      <div class="col-sm-6"><label class="form-label" for="h-phone">Phone</label><input class="form-control" id="h-phone" name="phone"></div>
      <div class="col-12"><label class="form-label" for="h-addr">Address</label><input class="form-control" id="h-addr" name="address" required></div>
      <div class="col-12"><label class="form-label" for="h-status">Status</label><select class="form-select" id="h-status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
      <div class="col-12 create-only"><hr class="my-1"><p class="small text-secondary mb-2">Optional: create a login so this hospital can sign in.</p></div>
      <div class="col-sm-6 create-only"><label class="form-label" for="h-mail">Login email</label><input class="form-control" type="email" id="h-mail" name="login_email"></div>
      <div class="col-sm-6 create-only"><label class="form-label" for="h-pass">Login password</label><input class="form-control" type="password" id="h-pass" name="login_password" minlength="8"></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button class="btn btn-vx">Save hospital</button></div>
  </form></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>