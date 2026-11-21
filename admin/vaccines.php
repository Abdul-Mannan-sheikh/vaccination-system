<?php
// Admin: vaccine master list. Add / edit / delete and mark Available or Unavailable.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('admin/vaccines.php'); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');  $prot = trim($_POST['protects_from'] ?? '');
        $val  = (int) ($_POST['age_value'] ?? -1);
        $mult = ['days' => 1, 'weeks' => 7, 'months' => 30][$_POST['age_unit'] ?? 'days'] ?? 1;
        $avail = isset($_POST['is_available']) ? 1 : 0;
        $err = [];
        if ($name === '' || mb_strlen($name) > 100) $err[] = 'Vaccine name is required.';
        if ($prot === '' || mb_strlen($prot) > 150) $err[] = 'Please say what it protects from.';
        if ($val < 0 || $val > 5000)                $err[] = 'Enter a valid age.';
        if ($err) { flash('danger', implode(' ', $err)); redirect('admin/vaccines.php'); }

        if ($id) {
            $pdo->prepare('UPDATE vaccines SET name=?, protects_from=?, age_days=?, is_available=? WHERE id=?')->execute([$name, $prot, $val * $mult, $avail, $id]);
            flash('success', 'Vaccine updated.');
        } else {
            $pdo->prepare('INSERT INTO vaccines (name, protects_from, age_days, is_available) VALUES (?,?,?,?)')->execute([$name, $prot, $val * $mult, $avail]);
            flash('success', 'Vaccine added.');
        }
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE vaccines SET is_available = 1 - is_available WHERE id = ?')->execute([$id]);
        flash('success', 'Availability updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM vaccines WHERE id = ?')->execute([$id]);
        flash('success', 'Vaccine deleted.');
    }
    redirect('admin/vaccines.php');
}

$q = trim($_GET['q'] ?? '');  $av = $_GET['avail'] ?? '';
$where = []; $args = [];
if ($q !== '') { $where[] = '(name LIKE ? OR protects_from LIKE ?)'; array_push($args, "%$q%", "%$q%"); }
if ($av === '1' || $av === '0') { $where[] = 'is_available = ?'; $args[] = (int) $av; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM vaccines $w"); $cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 10);
$stmt = $pdo->prepare("SELECT * FROM vaccines $w ORDER BY age_days, id LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

/** Split days into the neatest value + unit so the edit form shows "6 weeks" instead of "42 days". */
function split_age(int $d): array
{
    if ($d >= 30 && $d % 30 === 0) return [$d / 30, 'months'];
    if ($d >= 7 && $d % 7 === 0)   return [$d / 7, 'weeks'];
    return [$d, 'days'];
}

$layout = 'app'; $pageTitle = 'Vaccines';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="mb-0">Vaccines</h2><p class="text-secondary mb-0"><?= $pg['total'] ?> in the schedule</p></div>
  <button class="btn btn-vx" data-bs-toggle="modal" data-bs-target="#vModal" data-fill="#vModal" data-title="Add vaccine"><i class="bi bi-plus-lg me-1"></i>Add vaccine</button>
</div>

<div class="panel">
  <form class="toolbar" method="get">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search vaccine or disease" aria-label="Search">
    <select class="form-select" name="avail" aria-label="Availability"><option value="">All</option>
      <option value="1" <?= $av === '1' ? 'selected' : '' ?>>Available</option><option value="0" <?= $av === '0' ? 'selected' : '' ?>>Unavailable</option></select>
    <button class="btn btn-vx">Filter</button>
    <?php if ($q !== '' || $av !== ''): ?><a class="btn btn-ghost" href="vaccines.php">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-capsule"></i>No vaccines match your search.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Vaccine</th><th>Protects from</th><th>Due at</th><th>Availability</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $v): [$av_val, $av_unit] = split_age((int) $v['age_days']); ?>
      <tr>
        <td><b><?= e($v['name']) ?></b></td><td class="text-secondary"><?= e($v['protects_from']) ?></td><td><?= age_label((int) $v['age_days']) ?></td>
        <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $v['id'] ?>">
          <button class="tag border-0 <?= $v['is_available'] ? 'ok' : 'bad' ?>" title="Click to change"><?= $v['is_available'] ? 'Available' : 'Unavailable' ?></button></form></td>
        <td class="text-end"><div class="act">
          <button class="btn btn-ghost" title="Edit" aria-label="Edit <?= e($v['name']) ?>" data-bs-toggle="modal" data-bs-target="#vModal" data-fill="#vModal" data-title="Edit vaccine"
            data-json="<?= e(json_encode(['id' => $v['id'], 'name' => $v['name'], 'protects_from' => $v['protects_from'], 'age_value' => $av_val, 'age_unit' => $av_unit, 'is_available' => (int) $v['is_available']])) ?>"><i class="bi bi-pencil"></i></button>
          <form method="post" data-confirm="Delete <?= e($v['name']) ?>? It will also be removed from every child's schedule."><?= csrf_field() ?>
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $v['id'] ?>">
            <button class="btn btn-ghost text-danger" title="Delete" aria-label="Delete <?= e($v['name']) ?>"><i class="bi bi-trash"></i></button></form>
        </div></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="vModal" tabindex="-1" aria-labelledby="vTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="">
    <div class="modal-header"><h5 class="modal-title" id="vTitle">Add vaccine</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-label" for="v-name">Vaccine name</label><input class="form-control" id="v-name" name="name" required maxlength="100"></div>
      <div class="col-12"><label class="form-label" for="v-prot">Protects from</label><input class="form-control" id="v-prot" name="protects_from" required maxlength="150"></div>
      <div class="col-6"><label class="form-label" for="v-age">Due at age</label><input class="form-control" type="number" min="0" max="5000" id="v-age" name="age_value" value="0" required></div>
      <div class="col-6"><label class="form-label" for="v-unit">Unit</label><select class="form-select" id="v-unit" name="age_unit"><option value="days">Days</option><option value="weeks">Weeks</option><option value="months">Months</option></select></div>
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="v-av" name="is_available" value="1" checked><label class="form-check-label" for="v-av">Currently available</label></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button class="btn btn-vx">Save vaccine</button></div>
  </form></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>