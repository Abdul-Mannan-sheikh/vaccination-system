<?php
// Parent: list own bookings and cancel pending ones.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('parent/bookings.php'); }
    if (($_POST['action'] ?? '') === 'cancel') {
        // Only this parent's own pending booking can be cancelled
        $n = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND parent_id = ? AND status = 'pending'");
        $n->execute([(int) ($_POST['id'] ?? 0), $user['id']]);
        flash($n->rowCount() ? 'success' : 'warning', $n->rowCount() ? 'Booking cancelled.' : 'That booking can no longer be cancelled.');
    }
    redirect('parent/bookings.php');
}

$st = $_GET['status'] ?? '';
$sql = "SELECT b.*, c.name AS child, v.name AS vaccine, h.name AS hospital, h.city
        FROM bookings b
        JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
        JOIN children c ON c.id = cv.child_id
        JOIN vaccines v ON v.id = cv.vaccine_id
        JOIN hospitals h ON h.id = b.hospital_id
        WHERE b.parent_id = ?";
$args = [$user['id']];
if (in_array($st, ['pending', 'approved', 'rejected', 'completed', 'cancelled'], true)) { $sql .= ' AND b.status = ?'; $args[] = $st; }
$stmt = $pdo->prepare($sql . ' ORDER BY b.created_at DESC');
$stmt->execute($args);
$rows = $stmt->fetchAll();

$cls = ['pending' => 'next', 'approved' => 'ok', 'completed' => 'ok', 'rejected' => 'bad', 'cancelled' => 'off'];

$layout = 'app'; $pageTitle = 'My Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h2 class="mb-0">My bookings</h2><p class="text-secondary mb-0">Track the status of every request.</p></div>
  <a class="btn btn-vx" href="book.php"><i class="bi bi-plus-lg me-1"></i>New booking</a>
</div>

<div class="panel">
  <form class="toolbar" method="get">
    <select class="form-select" name="status" onchange="this.form.submit()" aria-label="Status">
      <option value="">All statuses</option>
      <?php foreach (array_keys($cls) as $s): ?><option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-calendar2-x"></i>No bookings yet.<br>Choose <b>Book vaccine</b> to make your first request.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Child</th><th>Vaccine</th><th>Hospital</th><th>Date &amp; time</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><b><?= e($b['child']) ?></b></td><td><?= e($b['vaccine']) ?></td>
        <td><?= e($b['hospital']) ?><div class="small text-secondary"><?= e($b['city']) ?></div></td>
        <td><?= date('d M Y', strtotime($b['preferred_date'])) ?><div class="small text-secondary"><?= date('h:i A', strtotime($b['preferred_time'])) ?></div></td>
        <td><span class="tag <?= $cls[$b['status']] ?>"><?= ucfirst($b['status']) ?></span>
          <?php if ($b['admin_remark']): ?><div class="small text-secondary mt-1"><?= e($b['admin_remark']) ?></div><?php endif; ?></td>
        <td class="text-end"><?php if ($b['status'] === 'pending'): ?>
          <form method="post" data-confirm="Cancel this booking request?"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button class="btn btn-ghost btn-sm text-danger">Cancel</button></form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>