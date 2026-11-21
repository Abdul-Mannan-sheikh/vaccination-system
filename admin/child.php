<?php
// Admin: one child's full vaccination schedule.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('admin');

$c = db()->prepare('SELECT c.*, p.name AS parent, p.phone AS parent_phone FROM children c JOIN users p ON p.id = c.parent_id WHERE c.id = ?');
$c->execute([(int) ($_GET['id'] ?? 0)]);
$child = $c->fetch();
if (!$child) { flash('warning', 'Child not found.'); redirect('admin/children.php'); }

$s = db()->prepare("SELECT cv.*, v.name, v.protects_from, h.name AS hospital
    FROM child_vaccinations cv JOIN vaccines v ON v.id = cv.vaccine_id
    LEFT JOIN hospitals h ON h.id = cv.hospital_id
    WHERE cv.child_id = ? ORDER BY cv.due_date, v.id");
$s->execute([$child['id']]);
$doses = $s->fetchAll();
$done = count(array_filter($doses, fn($d) => $d['status'] === 'vaccinated'));
$total = count($doses);

$layout = 'app'; $pageTitle = $child['name'] . "'s schedule";
require __DIR__ . '/../includes/header.php';
?>
<a href="children.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>All children</a>
<div class="welcome mt-2">
  <h2><?= e($child['name']) ?></h2>
  <p><?= child_age($child['dob']) ?> old &middot; Born <?= date('d M Y', strtotime($child['dob'])) ?> &middot; Parent: <?= e($child['parent']) ?> <?= e($child['parent_phone'] ?? '') ?> &middot; <?= $done ?> of <?= $total ?> done</p>
</div>

<div class="panel">
  <h5 class="mb-3">Vaccination timeline</h5>
  <?php foreach ($doses as $d): [$label, $cls] = dose_state($d['status'], $d['due_date']); ?>
    <div class="tl-row <?= $cls ?>"><div class="tl-dot"></div><div class="tl-body">
      <div class="d-flex flex-wrap justify-content-between gap-2">
        <div><b><?= e($d['name']) ?></b><div class="text-secondary small">Protects from <?= e($d['protects_from']) ?></div></div>
        <div class="text-end"><span class="tag <?= $cls ?>"><?= $label ?></span>
          <div class="small text-secondary mt-1"><?= $d['status'] === 'vaccinated'
              ? 'Given ' . date('d M Y', strtotime($d['vaccinated_on'])) . ($d['hospital'] ? ' at ' . e($d['hospital']) : '')
              : 'Due ' . date('d M Y', strtotime($d['due_date'])) . ($d['status'] === 'booked' && $d['hospital'] ? ' at ' . e($d['hospital']) : '') ?></div></div>
      </div></div></div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>