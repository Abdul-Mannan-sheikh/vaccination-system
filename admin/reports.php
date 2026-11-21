<?php
// Admin: date-wise vaccination report with filters, CSV export and print view.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';
$user = require_role('admin');
$pdo  = db();

function valid_date(string $d): bool { $x = DateTime::createFromFormat('Y-m-d', $d); return $x && $x->format('Y-m-d') === $d; }
/** Stop spreadsheet formulas: a cell starting with = + - @ gets a leading quote. */
function csv_safe(string $v): string { return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }

// Filters (default: this month)
$from = valid_date($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$to   = valid_date($_GET['to'] ?? '')   ? $_GET['to']   : date('Y-m-d');
if ($from > $to) [$from, $to] = [$to, $from];
$hid = (int) ($_GET['hospital'] ?? 0);
$vid = (int) ($_GET['vaccine'] ?? 0);

$where = "WHERE cv.status = 'vaccinated' AND cv.vaccinated_on BETWEEN ? AND ?";
$args  = [$from, $to];
if ($hid) { $where .= ' AND cv.hospital_id = ?'; $args[] = $hid; }
if ($vid) { $where .= ' AND cv.vaccine_id = ?';  $args[] = $vid; }
$from_sql = "FROM child_vaccinations cv
    JOIN children c ON c.id = cv.child_id JOIN users p ON p.id = c.parent_id
    JOIN vaccines v ON v.id = cv.vaccine_id LEFT JOIN hospitals h ON h.id = cv.hospital_id $where";
$cols = 'cv.vaccinated_on, c.name AS child, p.name AS parent, v.name AS vaccine, h.name AS hospital';

/* ---------------- CSV download (before any HTML) ---------------- */
if (($_GET['export'] ?? '') === 'csv') {
    $all = $pdo->prepare("SELECT $cols $from_sql ORDER BY cv.vaccinated_on, c.name");
    $all->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="vaccination-report-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");                       // BOM so Excel reads UTF-8 correctly
    fputcsv($out, ['Date given', 'Child', 'Parent', 'Vaccine', 'Hospital']);
    foreach ($all as $r) fputcsv($out, [$r['vaccinated_on'], csv_safe($r['child']), csv_safe($r['parent']), csv_safe($r['vaccine']), csv_safe($r['hospital'] ?? '')]);
    fclose($out);
    exit;
}

// Summary numbers
$sum = $pdo->prepare("SELECT COUNT(*) AS doses, COUNT(DISTINCT cv.child_id) AS kids, COUNT(DISTINCT cv.hospital_id) AS hosp $from_sql");
$sum->execute($args);
$s = $sum->fetch();
$byVac = $pdo->prepare("SELECT v.name, COUNT(*) AS n $from_sql GROUP BY v.id, v.name ORDER BY n DESC LIMIT 6");
$byVac->execute($args);
$byVac = $byVac->fetchAll();

$pg = paginate((int) $s['doses'], 15);
$stmt = $pdo->prepare("SELECT $cols $from_sql ORDER BY cv.vaccinated_on DESC, c.name LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$hospitals = $pdo->query('SELECT id, name FROM hospitals ORDER BY name')->fetchAll();
$vaccines  = $pdo->query('SELECT id, name FROM vaccines ORDER BY age_days, id')->fetchAll();

$layout = 'app'; $pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
$qstr = http_build_query(['from' => $from, 'to' => $to, 'hospital' => $hid, 'vaccine' => $vid]);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div><h2 class="mb-0">Vaccination report</h2><p class="text-secondary mb-0">Date-wise record of vaccinations given.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-ghost" href="?<?= e($qstr) ?>&export=csv"><i class="bi bi-download me-1"></i>CSV</a>
    <button class="btn btn-vx" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form class="panel toolbar no-print" method="get">
  <div><label class="form-label mb-0 small" for="from">From</label><input class="form-control" type="date" id="from" name="from" value="<?= $from ?>"></div>
  <div><label class="form-label mb-0 small" for="to">To</label><input class="form-control" type="date" id="to" name="to" value="<?= $to ?>"></div>
  <div><label class="form-label mb-0 small" for="hospital">Hospital</label><select class="form-select" id="hospital" name="hospital"><option value="0">All</option>
    <?php foreach ($hospitals as $h): ?><option value="<?= $h['id'] ?>" <?= $h['id'] == $hid ? 'selected' : '' ?>><?= e($h['name']) ?></option><?php endforeach; ?></select></div>
  <div><label class="form-label mb-0 small" for="vaccine">Vaccine</label><select class="form-select" id="vaccine" name="vaccine"><option value="0">All</option>
    <?php foreach ($vaccines as $v): ?><option value="<?= $v['id'] ?>" <?= $v['id'] == $vid ? 'selected' : '' ?>><?= e($v['name']) ?></option><?php endforeach; ?></select></div>
  <div class="align-self-end"><button class="btn btn-vx">Apply</button></div>
</form>

<p class="small text-secondary mt-2">Period: <b><?= date('d M Y', strtotime($from)) ?></b> to <b><?= date('d M Y', strtotime($to)) ?></b></p>
<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="stat"><div class="stat-ico"><i class="bi bi-capsule"></i></div><div><div class="stat-num"><?= $s['doses'] ?></div><div class="stat-label">Doses given</div></div></div></div>
  <div class="col-sm-4"><div class="stat"><div class="stat-ico"><i class="bi bi-emoji-smile"></i></div><div><div class="stat-num"><?= $s['kids'] ?></div><div class="stat-label">Children vaccinated</div></div></div></div>
  <div class="col-sm-4"><div class="stat"><div class="stat-ico"><i class="bi bi-hospital"></i></div><div><div class="stat-num"><?= $s['hosp'] ?></div><div class="stat-label">Hospitals involved</div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-8"><div class="panel h-100">
    <?php if (!$rows): ?>
      <div class="empty"><i class="bi bi-clipboard2"></i>No vaccinations were recorded in this period.</div>
    <?php else: ?>
    <div class="table-wrap"><table class="vtable">
      <thead><tr><th>Date</th><th>Child</th><th>Vaccine</th><th>Hospital</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><?= date('d M Y', strtotime($r['vaccinated_on'])) ?></td>
          <td><b><?= e($r['child']) ?></b><div class="small text-secondary"><?= e($r['parent']) ?></div></td>
          <td><?= e($r['vaccine']) ?></td><td><?= e($r['hospital'] ?? '-') ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <div class="d-flex justify-content-between align-items-center mt-3 small text-secondary no-print">
      <span>Page <?= $pg['page'] ?> of <?= $pg['pages'] ?></span><?= pagination_links($pg) ?></div>
    <?php endif; ?>
  </div></div>
  <div class="col-lg-4"><div class="panel h-100"><h5>Top vaccines</h5>
    <?php if (!$byVac): ?><p class="text-secondary mb-0">No data for this period.</p>
    <?php else: $max = max(array_column($byVac, 'n')); foreach ($byVac as $v): ?>
      <div class="mb-3"><div class="d-flex justify-content-between small"><span><?= e($v['name']) ?></span><b><?= $v['n'] ?></b></div>
        <div class="progress"><div class="progress-bar" style="width:<?= round($v['n'] / $max * 100) ?>%"></div></div></div>
    <?php endforeach; endif; ?>
  </div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>