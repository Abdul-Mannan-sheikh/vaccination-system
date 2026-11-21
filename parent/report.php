<?php
// Parent: vaccination report + printable vaccination card for one child.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

$cs = $pdo->prepare('SELECT * FROM children WHERE parent_id = ? ORDER BY name');
$cs->execute([$user['id']]);
$children = $cs->fetchAll();

// Pick the requested child, but only from this parent's own children
$child = null;
foreach ($children as $c) { if ($c['id'] == ($_GET['child'] ?? 0)) $child = $c; }
if (!$child && $children) $child = $children[0];

$doses = []; $total = 0;
if ($child) {
    $ds = $pdo->prepare("SELECT v.name, v.protects_from, cv.vaccinated_on, h.name AS hospital
        FROM child_vaccinations cv
        JOIN vaccines v ON v.id = cv.vaccine_id
        LEFT JOIN hospitals h ON h.id = cv.hospital_id
        WHERE cv.child_id = ? AND cv.status = 'vaccinated'
        ORDER BY cv.vaccinated_on, v.age_days");
    $ds->execute([$child['id']]);
    $doses = $ds->fetchAll();
    $total = scalar_count('SELECT COUNT(*) FROM child_vaccinations WHERE child_id = ?', [$child['id']]);
}

$layout = 'app'; $pageTitle = 'Vaccination Report';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h2 class="mb-0">Vaccination report</h2><p class="text-secondary mb-0">A record of every dose your child has received.</p></div>
  <?php if ($child): ?><button class="btn btn-vx" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button><?php endif; ?>
</div>

<?php if (!$children): ?>
  <div class="panel"><div class="empty"><i class="bi bi-emoji-smile"></i>Add a child to see their vaccination report.<br>
    <a href="children.php" class="btn btn-vx btn-sm mt-3">Add child</a></div></div>
<?php else: ?>
<form method="get" class="panel mb-3 no-print">
  <label class="form-label" for="child">Child</label>
  <select class="form-select" style="max-width:300px" id="child" name="child" onchange="this.form.submit()">
    <?php foreach ($children as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $child['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
</form>

<div class="cert">
  <div class="cert-head">
    <div class="brand-mark"><i class="bi bi-shield-plus"></i></div>
    <div><h3 class="mb-0"><?= APP_NAME ?></h3><div class="small">Child Vaccination Record</div></div>
  </div>
  <div class="row g-3 my-3">
    <div class="col-6 col-md-3"><small>Child</small><b><?= e($child['name']) ?></b></div>
    <div class="col-6 col-md-3"><small>Date of birth</small><b><?= date('d M Y', strtotime($child['dob'])) ?></b></div>
    <div class="col-6 col-md-3"><small>Gender</small><b><?= ucfirst($child['gender']) ?></b></div>
    <div class="col-6 col-md-3"><small>Parent</small><b><?= e($user['name']) ?></b></div>
  </div>
  <div class="small mb-2"><?= count($doses) ?> of <?= $total ?> scheduled vaccines completed</div>

  <?php if (!$doses): ?>
    <div class="empty"><i class="bi bi-clipboard2"></i>No vaccinations have been recorded yet.</div>
  <?php else: ?>
  <table class="vtable cert-table">
    <thead><tr><th>#</th><th>Vaccine</th><th>Protects from</th><th>Date given</th><th>Hospital</th></tr></thead><tbody>
    <?php foreach ($doses as $i => $d): ?>
      <tr><td><?= $i + 1 ?></td><td><b><?= e($d['name']) ?></b></td><td><?= e($d['protects_from']) ?></td>
        <td><?= date('d M Y', strtotime($d['vaccinated_on'])) ?></td><td><?= e($d['hospital'] ?? '-') ?></td></tr>
    <?php endforeach; ?></tbody></table>
  <?php endif; ?>
  <p class="cert-foot">Generated on <?= date('d M Y') ?> from <?= APP_NAME ?>. This is a record kept by the system and is not an official government certificate.</p>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>