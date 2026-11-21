<?php
// Hospital: approved appointments. Mark each child Vaccinated or Not vaccinated.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('hospital');
$pdo  = db();

// This hospital's own profile row
$hs = $pdo->prepare('SELECT id, name FROM hospitals WHERE user_id = ? LIMIT 1');
$hs->execute([$user['id']]);
$hospital = $hs->fetch();
$hid = (int) ($hospital['id'] ?? 0);

/* ---------------- mark vaccinated / not vaccinated ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('hospital/appointments.php'); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        $pdo->beginTransaction();
        // Only THIS hospital's own approved appointment can be updated (row is locked while we work)
        $q = $pdo->prepare("SELECT b.*, c.name AS child, v.name AS vaccine
            FROM bookings b
            JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
            JOIN children c ON c.id = cv.child_id
            JOIN vaccines v ON v.id = cv.vaccine_id
            WHERE b.id = ? AND b.hospital_id = ? AND b.status = 'approved' FOR UPDATE");
        $q->execute([$id, $hid]);
        $b = $q->fetch();

        if (!$b) {
            $pdo->rollBack();
            flash('warning', 'That appointment was already updated.');
        } elseif ($action === 'vaccinated') {
            $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE child_vaccinations SET status = 'vaccinated', vaccinated_on = CURDATE(), hospital_id = ? WHERE id = ?")
                ->execute([$hid, $b['child_vaccination_id']]);
            add_notification((int) $b['parent_id'], 'Vaccination completed', $b['child'] . ' received ' . $b['vaccine'] . ' at ' . $hospital['name'] . '.');
            $pdo->commit();
            flash('success', $b['child'] . ' marked as vaccinated.');
        } elseif ($action === 'not_vaccinated') {
            // Dose goes back to "pending" so the parent can book again
            $pdo->prepare("UPDATE bookings SET status = 'cancelled', admin_remark = 'Marked not vaccinated by the hospital' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE child_vaccinations SET status = 'pending', hospital_id = NULL WHERE id = ?")->execute([$b['child_vaccination_id']]);
            add_notification((int) $b['parent_id'], 'Vaccination not given', $b['vaccine'] . ' for ' . $b['child'] . ' was not given at ' . $hospital['name'] . '. Please book again.');
            $pdo->commit();
            flash('success', 'Marked as not vaccinated. The parent has been notified.');
        } else {
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('danger', 'Could not update. Please try again.');
    }
    redirect('hospital/appointments.php');
}

/* ---------------- list approved appointments ---------------- */
$date = $_GET['date'] ?? '';
$sql = "SELECT b.*, c.name AS child, c.dob, c.gender, p.name AS parent, p.phone AS parent_phone, v.name AS vaccine
        FROM bookings b
        JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
        JOIN children c ON c.id = cv.child_id
        JOIN users p ON p.id = b.parent_id
        JOIN vaccines v ON v.id = cv.vaccine_id
        WHERE b.hospital_id = ? AND b.status = 'approved'";
$args = [$hid];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $sql .= ' AND b.preferred_date = ?'; $args[] = $date; } else { $date = ''; }
$stmt = $pdo->prepare($sql . ' ORDER BY b.preferred_date, b.preferred_time');
$stmt->execute($args);
$rows = $stmt->fetchAll();

$layout = 'app'; $pageTitle = 'Appointments';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Appointments</h2><p class="text-secondary mb-0">Approved bookings waiting for vaccination.</p></div>

<?php if (!$hospital): ?>
  <div class="panel"><div class="empty"><i class="bi bi-exclamation-circle"></i>No hospital profile is linked to this account.</div></div>
<?php else: ?>
<div class="panel">
  <form class="toolbar" method="get">
    <label class="form-label mb-0" for="date">Date</label>
    <input class="form-control" type="date" id="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()">
    <?php if ($date !== ''): ?><a class="btn btn-ghost" href="appointments.php">Show all</a>
      <a class="btn btn-ghost" href="?date=<?= date('Y-m-d') ?>">Today</a><?php else: ?><a class="btn btn-ghost" href="?date=<?= date('Y-m-d') ?>">Today only</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-calendar-check"></i>No approved appointments<?= $date !== '' ? ' on this date' : ' yet' ?>.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Date &amp; time</th><th>Child</th><th>Vaccine</th><th>Parent</th><th class="text-end">Action</th></tr></thead><tbody>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><b><?= date('d M Y', strtotime($b['preferred_date'])) ?></b><div class="small text-secondary"><?= date('h:i A', strtotime($b['preferred_time'])) ?></div></td>
        <td><b><?= e($b['child']) ?></b><div class="small text-secondary"><?= child_age($b['dob']) ?> &middot; <?= ucfirst($b['gender']) ?></div></td>
        <td><?= e($b['vaccine']) ?></td>
        <td><?= e($b['parent']) ?><div class="small text-secondary"><?= e($b['parent_phone'] ?? '') ?></div></td>
        <td class="text-end"><div class="act">
          <form method="post" data-confirm="Mark <?= e($b['child']) ?> as vaccinated with <?= e($b['vaccine']) ?>?"><?= csrf_field() ?>
            <input type="hidden" name="action" value="vaccinated"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn btn-vx btn-sm">Vaccinated</button></form>
          <form method="post" data-confirm="Mark as NOT vaccinated? The parent will be asked to book again."><?= csrf_field() ?>
            <input type="hidden" name="action" value="not_vaccinated"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn btn-ghost btn-sm text-danger">Not vaccinated</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>