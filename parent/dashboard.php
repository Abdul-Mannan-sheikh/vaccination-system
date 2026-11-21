<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

$stats = [
    ['My children',   scalar_count('SELECT COUNT(*) FROM children WHERE parent_id = ?', [$user['id']]),                    'bi-emoji-smile',     ''],
    ['Bookings',      scalar_count('SELECT COUNT(*) FROM bookings WHERE parent_id = ?', [$user['id']]),                    'bi-calendar2-check', ''],
    ['Unread alerts', scalar_count('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['id']]), 'bi-bell',            'warn'],
];

// Each child with progress
$cs = $pdo->prepare("SELECT c.id, c.name, c.dob,
    (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id) AS total,
    (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id AND status = 'vaccinated') AS done,
    (SELECT COUNT(*) FROM child_vaccinations WHERE child_id = c.id AND status = 'pending' AND due_date < CURDATE()) AS overdue
    FROM children c WHERE c.parent_id = ? ORDER BY c.name");
$cs->execute([$user['id']]);
$children = $cs->fetchAll();

// Next 5 doses not given yet
$up = $pdo->prepare("SELECT c.id AS child_id, c.name AS child, v.name AS vaccine, cv.due_date, cv.status
    FROM child_vaccinations cv JOIN children c ON c.id = cv.child_id JOIN vaccines v ON v.id = cv.vaccine_id
    WHERE c.parent_id = ? AND cv.status <> 'vaccinated' ORDER BY cv.due_date LIMIT 5");
$up->execute([$user['id']]);
$upcoming = $up->fetchAll();

$ns = $pdo->prepare('SELECT title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 4');
$ns->execute([$user['id']]);
$notes = $ns->fetchAll();

$layout = 'app'; $pageTitle = 'My Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="welcome"><h2>Hello, <?= e($user['name']) ?></h2><p>Keep every child's vaccinations on track.</p></div>

<div class="row g-3 mb-3">
<?php foreach ($stats as [$label, $num, $icon, $cls]): ?>
  <div class="col-sm-4"><div class="stat <?= $cls ?>"><div class="stat-ico"><i class="bi <?= $icon ?>"></i></div>
    <div><div class="stat-num"><?= $num ?></div><div class="stat-label"><?= e($label) ?></div></div></div></div>
<?php endforeach; ?>
</div>

<?php if ($children): ?>
<div class="row g-3 mb-3">
  <?php foreach ($children as $c): $pct = $c['total'] ? round($c['done'] / $c['total'] * 100) : 0; ?>
  <div class="col-md-6 col-xl-4"><div class="panel h-100">
    <div class="d-flex align-items-center gap-3 mb-3">
      <div class="avatar-c"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
      <div class="flex-grow-1"><b><?= e($c['name']) ?></b><div class="small text-secondary"><?= child_age($c['dob']) ?> old</div></div>
      <?php if ($c['overdue']): ?><span class="tag bad"><?= $c['overdue'] ?> overdue</span><?php endif; ?>
    </div>
    <div class="d-flex justify-content-between small mb-1"><span>Vaccinated</span><b><?= $c['done'] ?> / <?= $c['total'] ?> (<?= $pct ?>%)</b></div>
    <div class="progress mb-3" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
    <a class="btn btn-ghost btn-sm w-100" href="child.php?id=<?= $c['id'] ?>">View schedule</a>
  </div></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7"><div class="panel h-100"><h5>Upcoming vaccinations</h5>
    <?php if (!$upcoming): ?>
      <div class="empty"><i class="bi bi-calendar-heart"></i>Nothing due right now.<br>
        <a class="btn btn-vx btn-sm mt-3" href="children.php">Add a child</a></div>
    <?php else: foreach ($upcoming as $u): [$label, $cls] = dose_state($u['status'], $u['due_date']); ?>
      <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
        <div><b><?= e($u['vaccine']) ?></b><div class="small text-secondary"><?= e($u['child']) ?> &middot; due <?= date('d M Y', strtotime($u['due_date'])) ?></div></div>
        <div class="d-flex align-items-center gap-2"><span class="tag <?= $cls ?>"><?= $label ?></span>
          <?php if ($u['status'] === 'pending'): ?><a class="btn btn-vx btn-sm" href="book.php?child=<?= $u['child_id'] ?>">Book</a><?php endif; ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div></div>
  <div class="col-lg-5"><div class="panel h-100">
    <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Notifications</h5><a class="small" href="../notifications.php">View all</a></div>
    <?php if (!$notes): ?>
      <div class="empty"><i class="bi bi-bell"></i>No notifications yet.</div>
    <?php else: foreach ($notes as $n): ?>
      <div class="py-2 border-bottom"><b><?= e($n['title']) ?></b><?= $n['is_read'] ? '' : ' <span class="tag next">New</span>' ?>
        <div class="text-secondary small"><?= e($n['message']) ?></div></div>
    <?php endforeach; endif; ?>
  </div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>