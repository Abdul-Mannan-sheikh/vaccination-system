<?php
// Parent: manage own children. Adding a child auto-generates the vaccine schedule.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('parent/children.php'); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'save') {
            $name   = trim($_POST['name'] ?? '');
            $dob    = $_POST['dob'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $blood  = $_POST['blood_group'] ?? '';

            $err = [];
            if ($name === '' || mb_strlen($name) > 100) $err[] = 'Child name is required.';
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob)    $err[] = 'Please enter a valid date of birth.';
            elseif ($dob > date('Y-m-d'))               $err[] = 'Date of birth cannot be in the future.';
            elseif ($dob < date('Y-m-d', strtotime('-18 years'))) $err[] = 'Child must be under 18 years old.';
            if (!in_array($gender, ['male', 'female', 'other'], true)) $err[] = 'Please choose a gender.';
            if (!in_array($blood, ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) $blood = '';
            if ($err) { flash('danger', implode(' ', $err)); redirect('parent/children.php'); }

            $pdo->beginTransaction();
            if ($id) {
                // ownership check: only update the parent's own child
                $own = $pdo->prepare('SELECT dob FROM children WHERE id = ? AND parent_id = ?');
                $own->execute([$id, $user['id']]);
                $old = $own->fetch();
                if (!$old) { $pdo->rollBack(); redirect('parent/children.php'); }

                $pdo->prepare('UPDATE children SET name=?, dob=?, gender=?, blood_group=? WHERE id=? AND parent_id=?')
                    ->execute([$name, $dob, $gender, $blood ?: null, $id, $user['id']]);
                if ($old['dob'] !== $dob) {   // DOB changed: move the due dates of doses not yet given
                    $pdo->prepare("UPDATE child_vaccinations cv JOIN vaccines v ON v.id = cv.vaccine_id
                                   SET cv.due_date = DATE_ADD(?, INTERVAL v.age_days DAY)
                                   WHERE cv.child_id = ? AND cv.status = 'pending'")->execute([$dob, $id]);
                }
                flash('success', 'Child updated.');
            } else {
                $pdo->prepare('INSERT INTO children (parent_id, name, dob, gender, blood_group) VALUES (?,?,?,?,?)')
                    ->execute([$user['id'], $name, $dob, $gender, $blood ?: null]);
                generate_schedule((int) $pdo->lastInsertId(), $dob);
                flash('success', $name . ' added. The vaccination schedule has been created.');
            }
            $pdo->commit();
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM children WHERE id = ? AND parent_id = ?')->execute([$id, $user['id']]);
            flash('success', 'Child removed.');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('danger', 'Could not save. Please try again.');
    }
    redirect('parent/children.php');
}

// Each child with progress numbers and the next pending due date
$stmt = $pdo->prepare("SELECT c.*,
    (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id) AS total,
    (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id AND status = 'vaccinated') AS done,
    (SELECT MIN(due_date) FROM child_vaccinations WHERE child_id = c.id AND status <> 'vaccinated') AS next_due
    FROM children c WHERE c.parent_id = ? ORDER BY c.name");
$stmt->execute([$user['id']]);
$children = $stmt->fetchAll();

$layout = 'app'; $pageTitle = 'My Children';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="mb-0">My children</h2><p class="text-secondary mb-0">Add a child to get their vaccination schedule.</p></div>
  <button class="btn btn-vx" data-bs-toggle="modal" data-bs-target="#cModal" data-fill="#cModal" data-title="Add child"><i class="bi bi-plus-lg me-1"></i>Add child</button>
</div>

<?php if (!$children): ?>
  <div class="panel"><div class="empty"><i class="bi bi-emoji-smile"></i>No children added yet.<br>Click <b>Add child</b> and the full schedule appears instantly.</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($children as $c): $pct = $c['total'] ? round($c['done'] / $c['total'] * 100) : 0; ?>
  <div class="col-md-6 col-xl-4"><div class="panel h-100">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="avatar-c"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
      <div><h5 class="mb-0"><?= e($c['name']) ?></h5>
        <small class="text-secondary"><?= child_age($c['dob']) ?> &middot; <?= ucfirst($c['gender']) ?><?= $c['blood_group'] ? ' &middot; ' . e($c['blood_group']) : '' ?></small></div>
    </div>
    <div class="d-flex justify-content-between small mb-1"><span>Vaccinated</span><b><?= $c['done'] ?> / <?= $c['total'] ?></b></div>
    <div class="progress mb-3" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
    <p class="small text-secondary mb-3"><i class="bi bi-calendar-event me-1"></i>Next due: <b><?= $c['next_due'] ? date('d M Y', strtotime($c['next_due'])) : 'All done' ?></b></p>
    <div class="d-flex gap-2">
      <a class="btn btn-vx btn-sm flex-grow-1" href="child.php?id=<?= $c['id'] ?>">View schedule</a>
      <button class="btn btn-ghost btn-sm" aria-label="Edit <?= e($c['name']) ?>" data-bs-toggle="modal" data-bs-target="#cModal" data-fill="#cModal" data-title="Edit child"
        data-json="<?= e(json_encode(['id' => $c['id'], 'name' => $c['name'], 'dob' => $c['dob'], 'gender' => $c['gender'], 'blood_group' => $c['blood_group']])) ?>"><i class="bi bi-pencil"></i></button>
      <form method="post" data-confirm="Remove <?= e($c['name']) ?>? Their schedule and bookings will be deleted."><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="btn btn-ghost btn-sm text-danger" aria-label="Remove <?= e($c['name']) ?>"><i class="bi bi-trash"></i></button></form>
    </div>
  </div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="cModal" tabindex="-1" aria-labelledby="cTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="">
    <div class="modal-header"><h5 class="modal-title" id="cTitle">Add child</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-label" for="c-name">Child's name</label><input class="form-control" id="c-name" name="name" required maxlength="100"></div>
      <div class="col-sm-6"><label class="form-label" for="c-dob">Date of birth</label><input class="form-control" type="date" id="c-dob" name="dob" max="<?= date('Y-m-d') ?>" required></div>
      <div class="col-sm-6"><label class="form-label" for="c-gender">Gender</label><select class="form-select" id="c-gender" name="gender" required>
        <option value="">Choose...</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
      <div class="col-sm-6"><label class="form-label" for="c-blood">Blood group (optional)</label><select class="form-select" id="c-blood" name="blood_group">
        <option value="">Not sure</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): ?><option><?= $b ?></option><?php endforeach; ?></select></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button class="btn btn-vx">Save child</button></div>
  </form></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>