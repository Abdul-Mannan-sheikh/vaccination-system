<?php
// Parent: request a hospital slot for one of a child's due vaccines.
require_once __DIR__ . '/../includes/functions.php';
$user = require_role('parent');
$pdo  = db();

$slots = booking_slots();
$today = date('Y-m-d');

/* ---------------- submit a booking request ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childId = (int) ($_POST['child'] ?? 0);
    if (!csrf_valid()) { flash('danger', 'Session expired. Please try again.'); redirect('parent/book.php'); }

    $cvId = (int) ($_POST['dose'] ?? 0);
    $hid  = (int) ($_POST['hospital'] ?? 0);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // The dose must belong to one of THIS parent's children and still be pending
    $q = $pdo->prepare("SELECT cv.id, cv.due_date, c.name AS child, v.name AS vaccine, v.is_available
        FROM child_vaccinations cv
        JOIN children c ON c.id = cv.child_id
        JOIN vaccines v ON v.id = cv.vaccine_id
        WHERE cv.id = ? AND c.parent_id = ? AND cv.status = 'pending'");
    $q->execute([$cvId, $user['id']]);
    $dose = $q->fetch();

    $err = [];
    if (!$dose) {
        $err[] = 'Please choose a valid vaccine dose.';
    } else {
        if (!$dose['is_available']) $err[] = 'This vaccine is currently unavailable.';
        if (scalar_count("SELECT COUNT(*) FROM bookings WHERE child_vaccination_id = ? AND status IN ('pending','approved')", [$cvId]))
            $err[] = 'This dose already has an active booking.';
    }
    if (!scalar_count("SELECT COUNT(*) FROM hospitals WHERE id = ? AND status = 'active'", [$hid])) $err[] = 'Please choose a hospital.';

    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $err[] = 'Please choose a valid date.';
    } else {
        $min = $dose ? max($today, $dose['due_date']) : $today;   // never before today or before the due date
        if ($date < $min) $err[] = 'The date cannot be before ' . date('d M Y', strtotime($min)) . '.';
        if ($date > date('Y-m-d', strtotime('+180 days'))) $err[] = 'Please choose a date within the next 6 months.';
    }
    if (!in_array($time, $slots, true)) $err[] = 'Please choose a time slot.';

    if (!$err) {
        try {
            $pdo->beginTransaction();
            // Lock the hospital row so two parents cannot take the last place at the same moment
            $cap = $pdo->prepare("SELECT slot_capacity FROM hospitals WHERE id = ? AND status = 'active' FOR UPDATE");
            $cap->execute([$hid]);
            $capacity = (int) $cap->fetchColumn();
            $used = scalar_count("SELECT COUNT(*) FROM bookings WHERE hospital_id = ? AND preferred_date = ? AND preferred_time = ? AND status IN ('pending','approved')",
                                 [$hid, $date, $time . ':00']);

            if ($used >= $capacity) {
                $pdo->rollBack();
                flash('danger', 'That time slot is full. Please choose another time.');
                redirect('parent/book.php?child=' . $childId);
            }

            $pdo->prepare('INSERT INTO bookings (child_vaccination_id, parent_id, hospital_id, preferred_date, preferred_time) VALUES (?,?,?,?,?)')
                ->execute([$cvId, $user['id'], $hid, $date, $time . ':00']);
            foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll() as $a) {
                add_notification((int) $a['id'], 'New booking request', $user['name'] . ' requested ' . $dose['vaccine'] . ' for ' . $dose['child'] . '.');
            }
            $pdo->commit();
            flash('success', 'Request sent. You will be notified once the admin reviews it.');
            redirect('parent/bookings.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Could not save your request. Please try again.');
            redirect('parent/book.php?child=' . $childId);
        }
    }
    flash('danger', implode(' ', $err));
    redirect('parent/book.php?child=' . $childId);
}

/* ---------------- page data ---------------- */
$cs = $pdo->prepare('SELECT id, name FROM children WHERE parent_id = ? ORDER BY name');
$cs->execute([$user['id']]);
$children = $cs->fetchAll();

$childId = (int) ($_GET['child'] ?? 0);
$doses = [];
if ($childId && in_array($childId, array_column($children, 'id'))) {      // child must be this parent's own
    $ds = $pdo->prepare("SELECT cv.id, cv.due_date, v.name FROM child_vaccinations cv
        JOIN vaccines v ON v.id = cv.vaccine_id
        WHERE cv.child_id = ? AND cv.status = 'pending' AND v.is_available = 1
          AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.child_vaccination_id = cv.id AND b.status IN ('pending','approved'))
        ORDER BY cv.due_date, v.id");
    $ds->execute([$childId]);
    $doses = $ds->fetchAll();
} else {
    $childId = 0;
}
$hospitals = $pdo->query("SELECT id, name, city, address, latitude, longitude FROM hospitals WHERE status = 'active' ORDER BY city, name")->fetchAll();
$pins = [];
foreach ($hospitals as $h) {
    if ($h['latitude'] !== null && $h['longitude'] !== null)
        $pins[] = ['id' => (int) $h['id'], 'name' => $h['name'], 'address' => $h['address'] . ', ' . $h['city'], 'lat' => (float) $h['latitude'], 'lng' => (float) $h['longitude']];
}

$layout = 'app'; $pageTitle = 'Book Vaccine';
require __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-1">Book a vaccine</h2>
<p class="text-secondary mb-4">Choose a child, a due vaccine, a hospital and a time. The admin will review your request.</p>

<?php if (!$children): ?>
  <div class="panel"><div class="empty"><i class="bi bi-emoji-smile"></i>Add a child first to book a vaccine.<br>
    <a href="children.php" class="btn btn-vx btn-sm mt-3">Add child</a></div></div>
<?php else: ?>
<div class="panel mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-sm-6 col-md-4"><label class="form-label" for="child">1. Which child?</label>
      <select class="form-select" id="child" name="child" onchange="this.form.submit()">
        <option value="">Choose a child...</option>
        <?php foreach ($children as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $childId ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <noscript><div class="col-auto"><button class="btn btn-vx">Select</button></div></noscript>
  </form>
</div>

<?php if ($childId && !$doses): ?>
  <div class="panel"><div class="empty"><i class="bi bi-check2-circle"></i>No vaccines are available to book for this child right now.<br>
    They may already be booked or completed, or a vaccine is marked unavailable.</div></div>

<?php elseif ($childId): ?>
<form method="post" class="panel">
  <?= csrf_field() ?><input type="hidden" name="child" value="<?= $childId ?>">
  <div class="row g-3">
    <div class="col-12"><label class="form-label" for="dose">2. Which vaccine?</label>
      <select class="form-select" id="dose" name="dose" required>
        <option value="">Choose a vaccine...</option>
        <?php foreach ($doses as $d): [$lbl] = dose_state('pending', $d['due_date']); ?>
          <option value="<?= $d['id'] ?>" data-due="<?= e($d['due_date']) ?>"><?= e($d['name']) ?> &mdash; due <?= date('d M Y', strtotime($d['due_date'])) ?> (<?= $lbl ?>)</option>
        <?php endforeach; ?>
      </select></div>

    <div class="col-12"><label class="form-label" for="hq">3. Which hospital?</label>
      <?php if ($pins): ?>
        <div id="map" class="map-box mb-2" role="application" aria-label="Map of hospitals"></div>
        <div class="form-text mb-2">Tap a pin on the map, then <b>Choose this hospital</b>, or pick from the list below.</div>
      <?php endif; ?>
      <input class="form-control mb-2" id="hq" placeholder="Search hospital or city..." autocomplete="off">
      <?php if (!$hospitals): ?><div class="text-secondary">No hospitals are available yet.</div><?php endif; ?>
      <div class="row g-2" id="hlist">
        <?php foreach ($hospitals as $h): ?>
          <div class="col-md-6 hosp-col" data-s="<?= e(strtolower($h['name'] . ' ' . $h['city'])) ?>">
            <input type="radio" class="btn-check" name="hospital" id="h<?= $h['id'] ?>" value="<?= $h['id'] ?>" required>
            <label class="hosp-opt" for="h<?= $h['id'] ?>"><i class="bi bi-hospital"></i>
              <span><b><?= e($h['name']) ?></b><small><?= e($h['address']) ?>, <?= e($h['city']) ?></small></span></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="col-md-6"><label class="form-label" for="date">4. Date</label>
      <input class="form-control" type="date" id="date" name="date" min="<?= $today ?>" max="<?= date('Y-m-d', strtotime('+180 days')) ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="time">5. Time</label>
      <select class="form-select" id="time" name="time" required><option value="">Choose a hospital and date first...</option></select>
      <div class="form-text" id="slotHint"></div></div>

    <div class="col-12 text-end"><button class="btn btn-vx px-4">Send booking request</button></div>
  </div>
</form>

<?php ob_start(); ?>
<?php if ($pins): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script>
var today = '<?= $today ?>', dateEl = document.getElementById('date'), timeEl = document.getElementById('time');

// Filter the hospital list as the parent types
document.getElementById('hq').addEventListener('input', function () {
  var q = this.value.toLowerCase();
  document.querySelectorAll('.hosp-col').forEach(function (c) { c.style.display = c.dataset.s.indexOf(q) > -1 ? '' : 'none'; });
});

// The date cannot be before today or before the vaccine's due date
document.getElementById('dose').addEventListener('change', function () {
  var due = this.selectedOptions[0].dataset.due || today;
  dateEl.min = due > today ? due : today;
  if (dateEl.value && dateEl.value < dateEl.min) dateEl.value = dateEl.min;
  loadSlots();
});

// Ask the server which time slots are still free for this hospital and date
function loadSlots() {
  var r = document.querySelector('input[name=hospital]:checked'), hint = document.getElementById('slotHint');
  if (!r || !dateEl.value) return;
  fetch('slots.php?hospital=' + encodeURIComponent(r.value) + '&date=' + encodeURIComponent(dateEl.value), { credentials: 'same-origin' })
    .then(function (x) { return x.json(); })
    .then(function (d) {
      var keep = timeEl.value, free = 0;
      timeEl.innerHTML = '<option value="">Choose a time...</option>';
      d.slots.forEach(function (s) {
        var o = document.createElement('option');
        o.value = s.time; o.disabled = s.left <= 0;
        o.textContent = s.time + (s.left <= 0 ? '  (full)' : '  (' + s.left + ' left)');
        if (s.left > 0) free++;
        timeEl.appendChild(o);
      });
      if (keep) timeEl.value = keep;
      hint.textContent = free ? '' : 'All slots are full on this date. Please choose another day.';
    })
    .catch(function () { hint.textContent = 'Could not load time slots. Please try again.'; });
}
dateEl.addEventListener('change', loadSlots);
document.querySelectorAll('input[name=hospital]').forEach(function (r) { r.addEventListener('change', loadSlots); });

<?php if ($pins): ?>
// Map of hospitals (OpenStreetMap). The pin popup has a button that selects the hospital.
var pins = <?= json_encode($pins) ?>;
var map = L.map('map', { scrollWheelZoom: false });
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
var bounds = [];
pins.forEach(function (p) {
  var box = document.createElement('div');
  var b = document.createElement('b'); b.textContent = p.name;
  var a = document.createElement('div'); a.textContent = p.address; a.style.margin = '.25rem 0 .5rem';
  var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'btn btn-vx btn-sm'; btn.textContent = 'Choose this hospital';
  btn.onclick = function () {
    var radio = document.getElementById('h' + p.id);
    radio.checked = true; radio.dispatchEvent(new Event('change'));
    radio.closest('.hosp-col').scrollIntoView({ behavior: 'smooth', block: 'center' });
    map.closePopup();
  };
  box.appendChild(b); box.appendChild(a); box.appendChild(btn);
  L.marker([p.lat, p.lng]).addTo(map).bindPopup(box);
  bounds.push([p.lat, p.lng]);
});
map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
<?php endif; ?>
</script>
<?php $pageScripts = ob_get_clean(); ?>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>