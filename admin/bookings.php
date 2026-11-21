<?php
// Admin: review booking requests. Approve or reject; parent (and hospital) are notified.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

/* ---------------- approve / reject ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('admin/bookings.php'); }
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');

    try {
        $pdo->beginTransaction();
        // Lock the booking row so two admins cannot decide on it at the same time
        $q = $pdo->prepare("SELECT b.*, c.name AS child, v.name AS vaccine, h.name AS hospital, h.user_id AS hospital_user
            FROM bookings b
            JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
            JOIN children c ON c.id = cv.child_id
            JOIN vaccines v ON v.id = cv.vaccine_id
            JOIN hospitals h ON h.id = b.hospital_id
            WHERE b.id = ? AND b.status = 'pending' FOR UPDATE");
        $q->execute([$id]);
        $b = $q->fetch();

        if (!$b) {
            $pdo->rollBack();
            flash('warning', 'That request was already handled.');
        } elseif ($action === 'approve') {
            $pdo->prepare("UPDATE bookings SET status = 'approved', admin_remark = ? WHERE id = ?")->execute([$remark ?: null, $id]);
            // The dose becomes "booked" at the chosen hospital
            $pdo->prepare("UPDATE child_vaccinations SET status = 'booked', hospital_id = ? WHERE id = ?")->execute([$b['hospital_id'], $b['child_vaccination_id']]);
            $when = date('d M Y', strtotime($b['preferred_date'])) . ' at ' . date('h:i A', strtotime($b['preferred_time']));
            add_notification((int) $b['parent_id'], 'Booking approved', $b['vaccine'] . ' for ' . $b['child'] . ' at ' . $b['hospital'] . ' on ' . $when . '.');
            if ($b['hospital_user']) {
                add_notification((int) $b['hospital_user'], 'New appointment', $b['vaccine'] . ' for ' . $b['child'] . ' on ' . $when . '.');
            }
            $pdo->commit();
            flash('success', 'Booking approved. The parent and hospital have been notified.');
        } elseif ($action === 'reject') {
            if ($remark === '') {
                $pdo->rollBack();
                flash('danger', 'Please write a reason for rejecting.');
            } else {
                $pdo->prepare("UPDATE bookings SET status = 'rejected', admin_remark = ? WHERE id = ?")->execute([$remark, $id]);
                add_notification((int) $b['parent_id'], 'Booking rejected', $b['vaccine'] . ' for ' . $b['child'] . ' was not approved. Reason: ' . $remark);
                $pdo->commit();
                flash('success', 'Booking rejected. The parent has been notified.');
            }
        } else {
            $pdo->rollBack();
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('danger', 'Could not update the booking. Please try again.');
    }
    redirect('admin/bookings.php?status=' . urlencode($_GET['status'] ?? 'pending'));
}

/* ---------------- list ---------------- */
$statuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
$st = $_GET['status'] ?? 'pending';
if ($st !== 'all' && !in_array($st, $statuses, true)) $st = 'pending';
$qs = trim($_GET['q'] ?? '');

// Counts for the tabs
$counts = array_fill_keys($statuses, 0);
foreach ($pdo->query('SELECT status, COUNT(*) c FROM bookings GROUP BY status') as $r) $counts[$r['status']] = (int) $r['c'];

$where = []; $args = [];
if ($st !== 'all') { $where[] = 'b.status = ?'; $args[] = $st; }
if ($qs !== '') { $where[] = '(c.name LIKE ? OR p.name LIKE ? OR h.name LIKE ?)'; array_push($args, "%$qs%", "%$qs%", "%$qs%"); }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = "FROM bookings b
    JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
    JOIN children c ON c.id = cv.child_id
    JOIN users p ON p.id = b.parent_id
    JOIN vaccines v ON v.id = cv.vaccine_id
    JOIN hospitals h ON h.id = b.hospital_id $w";

$cnt = $pdo->prepare("SELECT COUNT(*) $from"); $cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 10);
$stmt = $pdo->prepare("SELECT b.*, c.name AS child, p.name AS parent, p.phone AS parent_phone, v.name AS vaccine, h.name AS hospital, h.city $from
    ORDER BY b.status = 'pending' DESC, b.preferred_date, b.id LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$cls = ['pending' => 'next', 'approved' => 'ok', 'completed' => 'ok', 'rejected' => 'bad', 'cancelled' => 'off'];

$layout = 'app'; $pageTitle = 'Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Booking requests</h2><p class="text-secondary mb-0">Approve or reject requests from parents.</p></div>

<div class="panel">
  <ul class="nav nav-pills mb-3 gap-1">
    <?php foreach (array_merge(['pending', 'approved', 'rejected', 'completed', 'cancelled'], ['all']) as $s): ?>
      <li class="nav-item"><a class="btn btn-sm <?= $st === $s ? 'btn-vx' : 'btn-ghost' ?>" href="?status=<?= $s ?>">
        <?= ucfirst($s) ?><?= $s !== 'all' ? ' <span class="opacity-75">(' . $counts[$s] . ')</span>' : '' ?></a></li>
    <?php endforeach; ?>
  </ul>
  <form class="toolbar" method="get"><input type="hidden" name="status" value="<?= e($st) ?>">
    <input class="form-control" name="q" value="<?= e($qs) ?>" placeholder="Search child, parent or hospital" aria-label="Search">
    <button class="btn btn-vx">Search</button>
    <?php if ($qs !== ''): ?><a class="btn btn-ghost" href="?status=<?= e($st) ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-inbox"></i>No <?= $st === 'all' ? '' : e($st) . ' ' ?>bookings found.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Child &amp; parent</th><th>Vaccine</th><th>Hospital</th><th>Date &amp; time</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
    <?php foreach ($rows as $b): ?>
      <tr>
        <td><b><?= e($b['child']) ?></b><div class="small text-secondary"><?= e($b['parent']) ?><?= $b['parent_phone'] ? ' &middot; ' . e($b['parent_phone']) : '' ?></div></td>
        <td><?= e($b['vaccine']) ?></td>
        <td><?= e($b['hospital']) ?><div class="small text-secondary"><?= e($b['city']) ?></div></td>
        <td><?= date('d M Y', strtotime($b['preferred_date'])) ?><div class="small text-secondary"><?= date('h:i A', strtotime($b['preferred_time'])) ?></div></td>
        <td><span class="tag <?= $cls[$b['status']] ?>"><?= ucfirst($b['status']) ?></span>
          <?php if ($b['admin_remark']): ?><div class="small text-secondary mt-1"><?= e($b['admin_remark']) ?></div><?php endif; ?></td>
        <td class="text-end"><?php if ($b['status'] === 'pending'): ?><div class="act">
          <form method="post" action="?status=<?= e($st) ?>" data-confirm="Approve <?= e($b['vaccine']) ?> for <?= e($b['child']) ?>?"><?= csrf_field() ?>
            <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button class="btn btn-vx btn-sm">Approve</button></form>
          <button class="btn btn-ghost btn-sm text-danger" data-bs-toggle="modal" data-bs-target="#rModal" data-fill="#rModal" data-title="Reject booking"
            data-json="<?= e(json_encode(['id' => $b['id']])) ?>">Reject</button>
        </div><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>

<!-- Reject reason -->
<div class="modal fade" id="rModal" tabindex="-1" aria-labelledby="rTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="?status=<?= e($st) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="">
    <div class="modal-header"><h5 class="modal-title" id="rTitle">Reject booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><label class="form-label" for="r-remark">Reason (the parent will see this)</label>
      <textarea class="form-control" id="r-remark" name="remark" rows="3" maxlength="255" required placeholder="e.g. Vaccine out of stock on that date. Please choose another day."></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button class="btn btn-vx">Reject request</button></div>
  </form></div></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>