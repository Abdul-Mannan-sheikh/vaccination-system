<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('hospital');
$pdo  = db();

$stmt = $pdo->prepare('SELECT * FROM hospitals WHERE user_id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$hospital = $stmt->fetch();
$hid = (int) ($hospital['id'] ?? 0);

$stats = [
    ['Today',                  scalar_count("SELECT COUNT(*) FROM bookings WHERE hospital_id = ? AND status='approved' AND preferred_date = CURDATE()", [$hid]), 'bi-clock',          'warn'],
    ['Upcoming approved',      scalar_count("SELECT COUNT(*) FROM bookings WHERE hospital_id = ? AND status='approved' AND preferred_date >= CURDATE()", [$hid]), 'bi-calendar2-check', ''],
    ['Vaccinations completed', scalar_count("SELECT COUNT(*) FROM bookings WHERE hospital_id = ? AND status='completed'", [$hid]),                                 'bi-check2-circle',   ''],
];

// Next appointments (today first, then upcoming)
$ap = $pdo->prepare("SELECT b.preferred_date, b.preferred_time, c.name AS child, v.name AS vaccine, p.name AS parent
    FROM bookings b JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id JOIN children c ON c.id = cv.child_id
    JOIN vaccines v ON v.id = cv.vaccine_id JOIN users p ON p.id = b.parent_id
    WHERE b.hospital_id = ? AND b.status = 'approved' AND b.preferred_date >= CURDATE()
    ORDER BY b.preferred_date, b.preferred_time LIMIT 6");
$ap->execute([$hid]);
$appts = $ap->fetchAll();

$layout = 'app'; $pageTitle = 'Hospital Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="welcome"><h2><?= e($hospital['name'] ?? $user['name']) ?></h2>
  <p><i class="bi bi-geo-alt me-1"></i><?= e(trim(($hospital['address'] ?? '') . ', ' . ($hospital['city'] ?? ''), ', ')) ?></p></div>

<div class="row g-3 mb-3">
<?php foreach ($stats as [$label, $num, $icon, $cls]): ?>
  <div class="col-sm-4"><div class="stat <?= $cls ?>"><div class="stat-ico"><i class="bi <?= $icon ?>"></i></div>
    <div><div class="stat-num"><?= $num ?></div><div class="stat-label"><?= e($label) ?></div></div></div></div>
<?php endforeach; ?>
</div>

<div class="panel">
  <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Next appointments</h5><a class="small" href="appointments.php">View all</a></div>
  <?php if (!$appts): ?>
    <div class="empty"><i class="bi bi-calendar-check"></i>No approved appointments yet.<br>They appear here once the admin approves a booking.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="vtable">
    <thead><tr><th>When</th><th>Child</th><th>Vaccine</th><th>Parent</th></tr></thead><tbody>
    <?php foreach ($appts as $a): $isToday = $a['preferred_date'] === date('Y-m-d'); ?>
      <tr><td><b><?= $isToday ? 'Today' : date('d M Y', strtotime($a['preferred_date'])) ?></b> <span class="text-secondary small"><?= date('h:i A', strtotime($a['preferred_time'])) ?></span></td>
        <td><?= e($a['child']) ?></td><td><?= e($a['vaccine']) ?></td><td><?= e($a['parent']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>