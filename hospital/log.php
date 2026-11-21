<?php
// Hospital: history of completed vaccinations (searchable).
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('hospital');
$pdo  = db();

$hs = $pdo->prepare('SELECT id FROM hospitals WHERE user_id = ? LIMIT 1');
$hs->execute([$user['id']]);
$hid = (int) $hs->fetchColumn();

$qs = trim($_GET['q'] ?? '');
$where = "WHERE b.hospital_id = ? AND b.status = 'completed'";
$args = [$hid];
if ($qs !== '') { $where .= ' AND (c.name LIKE ? OR p.name LIKE ? OR v.name LIKE ?)'; array_push($args, "%$qs%", "%$qs%", "%$qs%"); }
$from = "FROM bookings b
    JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id
    JOIN children c ON c.id = cv.child_id
    JOIN users p ON p.id = b.parent_id
    JOIN vaccines v ON v.id = cv.vaccine_id $where";

$cnt = $pdo->prepare("SELECT COUNT(*) $from"); $cnt->execute($args);
$pg = paginate((int) $cnt->fetchColumn(), 10);
$stmt = $pdo->prepare("SELECT c.name AS child, c.dob, p.name AS parent, v.name AS vaccine, cv.vaccinated_on $from
    ORDER BY cv.vaccinated_on DESC, b.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$layout = 'app'; $pageTitle = 'Vaccination Log';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Vaccination log</h2><p class="text-secondary mb-0"><?= $pg['total'] ?> vaccination<?= $pg['total'] === 1 ? '' : 's' ?> completed here.</p></div>

<div class="panel">
  <form class="toolbar" method="get">
    <input class="form-control" name="q" value="<?= e($qs) ?>" placeholder="Search child, parent or vaccine" aria-label="Search">
    <button class="btn btn-vx">Search</button>
    <?php if ($qs !== ''): ?><a class="btn btn-ghost" href="log.php">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-journal-medical"></i>No completed vaccinations found.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Date given</th><th>Child</th><th>Vaccine</th><th>Parent</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= date('d M Y', strtotime($r['vaccinated_on'])) ?></td>
        <td><b><?= e($r['child']) ?></b><div class="small text-secondary"><?= child_age($r['dob']) ?> old</div></td>
        <td><?= e($r['vaccine']) ?></td><td><?= e($r['parent']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>