<?php
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('admin');
$pdo  = db();

$stats = [
    ['Parents',          scalar_count("SELECT COUNT(*) FROM users WHERE role='parent'"),       'bi-people',           ''],
    ['Children',         scalar_count('SELECT COUNT(*) FROM children'),                        'bi-emoji-smile',      ''],
    ['Hospitals',        scalar_count('SELECT COUNT(*) FROM hospitals'),                       'bi-hospital',         ''],
    ['Pending requests', scalar_count("SELECT COUNT(*) FROM bookings WHERE status='pending'"), 'bi-hourglass-split', 'warn'],
];

// Vaccinations given in each of the last 6 months (months with none show 0)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $d = new DateTime('first day of this month');
    $d->modify("-$i month");
    $months[$d->format('Y-m')] = ['label' => $d->format('M'), 'n' => 0];
}
foreach ($pdo->query("SELECT DATE_FORMAT(vaccinated_on, '%Y-%m') ym, COUNT(*) n FROM child_vaccinations
        WHERE status = 'vaccinated' AND vaccinated_on >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY ym") as $r) {
    if (isset($months[$r['ym']])) $months[$r['ym']]['n'] = (int) $r['n'];
}

// Bookings by status
$byStatus = array_fill_keys(['pending', 'approved', 'completed', 'rejected', 'cancelled'], 0);
foreach ($pdo->query('SELECT status, COUNT(*) c FROM bookings GROUP BY status') as $r) $byStatus[$r['status']] = (int) $r['c'];

// Busiest hospitals and the newest pending requests
$topHosp = $pdo->query("SELECT h.name, COUNT(*) n FROM child_vaccinations cv JOIN hospitals h ON h.id = cv.hospital_id
    WHERE cv.status = 'vaccinated' GROUP BY h.id, h.name ORDER BY n DESC LIMIT 5")->fetchAll();
$pending = $pdo->query("SELECT b.preferred_date, c.name AS child, v.name AS vaccine, h.name AS hospital
    FROM bookings b JOIN child_vaccinations cv ON cv.id = b.child_vaccination_id JOIN children c ON c.id = cv.child_id
    JOIN vaccines v ON v.id = cv.vaccine_id JOIN hospitals h ON h.id = b.hospital_id
    WHERE b.status = 'pending' ORDER BY b.created_at DESC LIMIT 5")->fetchAll();

$layout = 'app'; $pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="welcome"><h2>Welcome back, <?= e($user['name']) ?></h2><p>Here is what is happening across VaxTrack.</p></div>

<div class="row g-3 mb-3">
<?php foreach ($stats as [$label, $num, $icon, $cls]): ?>
  <div class="col-6 col-xl-3"><div class="stat <?= $cls ?>"><div class="stat-ico"><i class="bi <?= $icon ?>"></i></div>
    <div><div class="stat-num"><?= $num ?></div><div class="stat-label"><?= e($label) ?></div></div></div></div>
<?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="panel h-100"><h5>Vaccinations, last 6 months</h5>
    <?php if (array_sum(array_column($months, 'n')) === 0): ?>
      <div class="empty"><i class="bi bi-graph-up"></i>No vaccinations recorded in this period yet.</div>
    <?php else: ?><div class="chart-box"><canvas id="cMonths"></canvas></div><?php endif; ?></div></div>
  <div class="col-lg-4"><div class="panel h-100"><h5>Bookings by status</h5>
    <?php if (array_sum($byStatus) === 0): ?>
      <div class="empty"><i class="bi bi-pie-chart"></i>No bookings yet.</div>
    <?php else: ?><div class="chart-box"><canvas id="cStatus"></canvas></div><?php endif; ?></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-5"><div class="panel h-100"><h5>Busiest hospitals</h5>
    <?php if (!$topHosp): ?>
      <div class="empty"><i class="bi bi-hospital"></i>No completed vaccinations yet.</div>
    <?php else: ?><div class="chart-box"><canvas id="cHosp"></canvas></div><?php endif; ?></div></div>
  <div class="col-lg-7"><div class="panel h-100">
    <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Waiting for approval</h5><a class="small" href="bookings.php">View all</a></div>
    <?php if (!$pending): ?>
      <div class="empty"><i class="bi bi-check2-circle"></i>No pending requests. You are all caught up.</div>
    <?php else: ?>
    <table class="vtable"><thead><tr><th>Child</th><th>Vaccine</th><th>Hospital</th><th>Date</th></tr></thead><tbody>
      <?php foreach ($pending as $p): ?>
        <tr><td><b><?= e($p['child']) ?></b></td><td><?= e($p['vaccine']) ?></td><td><?= e($p['hospital']) ?></td><td><?= date('d M', strtotime($p['preferred_date'])) ?></td></tr>
      <?php endforeach; ?></tbody></table>
    <?php endif; ?>
  </div></div>
</div>

<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
var charts = {};
function mk(id, cfg) {                       // draw (or redraw) a chart by canvas id
  var el = document.getElementById(id);
  if (!el) return;
  if (charts[id]) charts[id].destroy();
  charts[id] = new Chart(el, cfg);
}
function drawAll() {
  var muted = vxVar('--muted'), line = vxVar('--line');
  var axes = { x: { ticks: { color: muted }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: muted, precision: 0 }, grid: { color: line } } };
  mk('cMonths', { type: 'line',
    data: { labels: <?= json_encode(array_column($months, 'label')) ?>,
      datasets: [{ label: 'Vaccinations', data: <?= json_encode(array_column($months, 'n')) ?>, borderColor: vxVar('--primary'),
        backgroundColor: vxVar('--primary') + '22', fill: true, tension: .35, pointRadius: 4 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: axes } });
  mk('cStatus', { type: 'doughnut',
    data: { labels: ['Pending', 'Approved', 'Completed', 'Rejected', 'Cancelled'], datasets: [{ data: <?= json_encode(array_values($byStatus)) ?>, borderWidth: 0,
      backgroundColor: [vxVar('--accent'), vxVar('--primary'), vxVar('--primary-2'), '#ef4444', '#94a3b8'] }] },
    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { color: muted } } } } });
  mk('cHosp', { type: 'bar',
    data: { labels: <?= json_encode(array_column($topHosp, 'name')) ?>, datasets: [{ label: 'Vaccinations', data: <?= json_encode(array_map('intval', array_column($topHosp, 'n'))) ?>,
      backgroundColor: vxVar('--primary'), borderRadius: 6 }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
      scales: { x: axes.y, y: { ticks: { color: muted }, grid: { display: false } } } } });
}
drawAll();
document.addEventListener('vx-theme', drawAll);   // redraw with new colours when the theme changes
</script>
<?php $pageScripts = ob_get_clean(); require __DIR__ . '/../includes/footer.php'; ?>