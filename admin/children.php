<?php
// Admin: every child (with progress) and the upcoming / overdue vaccination dates.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

$view  = ($_GET['view'] ?? '') === 'upcoming' ? 'upcoming' : 'children';
$qs    = trim($_GET['q'] ?? '');
$range = $_GET['range'] ?? '30';
if (!in_array($range, ['overdue', '7', '30', '90'], true)) $range = '30';

if ($view === 'children') {
    $where = ''; $args = [];
    if ($qs !== '') { $where = 'WHERE (c.name LIKE ? OR p.name LIKE ?)'; $args = ["%$qs%", "%$qs%"]; }
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM children c JOIN users p ON p.id = c.parent_id $where"); $cnt->execute($args);
    $pg = paginate((int) $cnt->fetchColumn(), 10);
    $stmt = $pdo->prepare("SELECT c.*, p.name AS parent, p.phone AS parent_phone,
        (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id) AS total,
        (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id AND status = 'vaccinated') AS done,
        (SELECT MIN(due_date) FROM child_vaccinations WHERE child_id = c.id AND status <> 'vaccinated') AS next_due
        FROM children c JOIN users p ON p.id = c.parent_id $where
        ORDER BY c.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}");
} else {
    // Doses that are not given yet, filtered by due date
    $where = "WHERE cv.status <> 'vaccinated' AND ";
    $where .= $range === 'overdue' ? 'cv.due_date < CURDATE()' : 'cv.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . (int) $range . ' DAY)';
    $args = [];
    if ($qs !== '') { $where .= ' AND (c.name LIKE ? OR p.name LIKE ?)'; $args = ["%$qs%", "%$qs%"]; }
    $from = "FROM child_vaccinations cv JOIN children c ON c.id = cv.child_id
             JOIN users p ON p.id = c.parent_id JOIN vaccines v ON v.id = cv.vaccine_id $where";
    $cnt = $pdo->prepare("SELECT COUNT(*) $from"); $cnt->execute($args);
    $pg = paginate((int) $cnt->fetchColumn(), 12);
    $stmt = $pdo->prepare("SELECT cv.*, c.id AS child_id, c.name AS child, p.name AS parent, p.phone AS parent_phone, v.name AS vaccine
        $from ORDER BY cv.due_date, c.name LIMIT {$pg['per']} OFFSET {$pg['offset']}");
}
$stmt->execute($args);
$rows = $stmt->fetchAll();

$layout = 'app'; $pageTitle = 'Children';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><h2 class="mb-0">Children</h2><p class="text-secondary mb-0">All registered children and their vaccination dates.</p></div>

<div class="panel">
  <div class="d-flex gap-1 mb-3">
    <a class="btn btn-sm <?= $view === 'children' ? 'btn-vx' : 'btn-ghost' ?>" href="?view=children">All children</a>
    <a class="btn btn-sm <?= $view === 'upcoming' ? 'btn-vx' : 'btn-ghost' ?>" href="?view=upcoming">Upcoming vaccinations</a>
  </div>

  <form class="toolbar" method="get"><input type="hidden" name="view" value="<?= $view ?>">
    <input class="form-control" name="q" value="<?= e($qs) ?>" placeholder="Search child or parent" aria-label="Search">
    <?php if ($view === 'upcoming'): ?>
      <select class="form-select" name="range" aria-label="Date range">
        <option value="overdue" <?= $range === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        <option value="7" <?= $range === '7' ? 'selected' : '' ?>>Next 7 days</option>
        <option value="30" <?= $range === '30' ? 'selected' : '' ?>>Next 30 days</option>
        <option value="90" <?= $range === '90' ? 'selected' : '' ?>>Next 90 days</option>
      </select>
    <?php endif; ?>
    <button class="btn btn-vx">Filter</button>
    <?php if ($qs !== ''): ?><a class="btn btn-ghost" href="?view=<?= $view ?>">Clear</a><?php endif; ?>
  </form>

  <?php if (!$rows): ?>
    <div class="empty"><i class="bi bi-emoji-smile"></i>Nothing to show here.</div>
  <?php elseif ($view === 'children'): ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Child</th><th>Parent</th><th>Progress</th><th>Next due</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $c): $pct = $c['total'] ? round($c['done'] / $c['total'] * 100) : 0; ?>
      <tr>
        <td><b><?= e($c['name']) ?></b><div class="small text-secondary"><?= child_age($c['dob']) ?> &middot; <?= ucfirst($c['gender']) ?></div></td>
        <td><?= e($c['parent']) ?><div class="small text-secondary"><?= e($c['parent_phone'] ?? '') ?></div></td>
        <td style="min-width:150px"><div class="small mb-1"><?= $c['done'] ?> / <?= $c['total'] ?></div>
          <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></td>
        <td><?= $c['next_due'] ? date('d M Y', strtotime($c['next_due'])) : 'All done' ?></td>
        <td class="text-end"><a class="btn btn-ghost btn-sm" href="child.php?id=<?= $c['id'] ?>">Schedule</a></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>Due date</th><th>Child</th><th>Vaccine</th><th>Parent</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): [$label, $cls] = dose_state($r['status'], $r['due_date']); ?>
      <tr><td><b><?= date('d M Y', strtotime($r['due_date'])) ?></b></td>
        <td><a href="child.php?id=<?= $r['child_id'] ?>"><?= e($r['child']) ?></a></td><td><?= e($r['vaccine']) ?></td>
        <td><?= e($r['parent']) ?><div class="small text-secondary"><?= e($r['parent_phone'] ?? '') ?></div></td>
        <td><span class="tag <?= $cls ?>"><?= $label ?></span></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>

  <?php if ($rows): ?><div class="d-flex justify-content-between align-items-center mt-3 small text-secondary">
    <span><?= $pg['total'] ?> result<?= $pg['total'] === 1 ? '' : 's' ?> &middot; page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>