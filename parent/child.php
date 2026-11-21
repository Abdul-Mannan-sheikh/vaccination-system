<?php
// Parent: one child's full vaccination schedule (only their own child can be opened).
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');

$id = (int) ($_GET['id'] ?? 0);
$c = db()->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ?');   // ownership check
$c->execute([$id, $user['id']]);
$child = $c->fetch();
if (!$child) { flash('warning', 'Child not found.'); redirect('parent/children.php'); }

$s = db()->prepare("SELECT cv.*, v.name, v.protects_from, v.is_available, h.name AS hospital
    FROM child_vaccinations cv
    JOIN vaccines v ON v.id = cv.vaccine_id
    LEFT JOIN hospitals h ON h.id = cv.hospital_id
    WHERE cv.child_id = ? ORDER BY cv.due_date, v.id");
$s->execute([$id]);
$doses = $s->fetchAll();

$done  = count(array_filter($doses, fn($d) => $d['status'] === 'vaccinated'));
$total = count($doses);
$pct   = $total ? round($done / $total * 100) : 0;

$layout = 'app'; $pageTitle = $child['name'] . "'s schedule";
require __DIR__ . '/../includes/header.php';
?>
<a href="children.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>All children</a>

<div class="welcome mt-2">
  <h2><?= e($child['name']) ?></h2>
  <p><?= child_age($child['dob']) ?> old &middot; Born <?= date('d M Y', strtotime($child['dob'])) ?> &middot; <?= $done ?> of <?= $total ?> vaccines done</p>
  <div class="progress mt-3 bg-white bg-opacity-25" style="height:8px"><div class="progress-bar bg-white" style="width:<?= $pct ?>%"></div></div>
</div>

<div class="panel">
  <h5 class="mb-3">Vaccination timeline</h5>
  <?php foreach ($doses as $d): [$label, $cls] = dose_state($d['status'], $d['due_date']); ?>
    <div class="tl-row <?= $cls ?>">
      <div class="tl-dot"></div>
      <div class="tl-body">
        <div class="d-flex flex-wrap justify-content-between gap-2">
          <div><b><?= e($d['name']) ?></b><div class="text-secondary small">Protects from <?= e($d['protects_from']) ?></div></div>
          <div class="text-end"><span class="tag <?= $cls ?>"><?= $label ?></span>
            <div class="small text-secondary mt-1">
              <?= $d['status'] === 'vaccinated'
                  ? 'Given ' . date('d M Y', strtotime($d['vaccinated_on'])) . ($d['hospital'] ? ' at ' . e($d['hospital']) : '')
                  : 'Due ' . date('d M Y', strtotime($d['due_date'])) ?>
            </div>
            <?php if ($d['status'] === 'pending' && !$d['is_available']): ?><div class="small text-danger">Currently unavailable</div><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$doses): ?><div class="empty"><i class="bi bi-calendar-x"></i>No vaccines in the schedule yet.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>