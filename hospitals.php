<?php
// Public: Hospitals (15 sections; directory, map and numbers come from the database)
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/pagination.php';
$pageTitle = 'Hospitals'; $layout = 'public';

$q = trim($_GET['q'] ?? '');  $city = trim($_GET['city'] ?? '');
$where = "WHERE status = 'active'"; $args = [];
if ($q !== '')    { $where .= ' AND (name LIKE ? OR address LIKE ?)'; array_push($args, "%$q%", "%$q%"); }
if ($city !== '') { $where .= ' AND city = ?'; $args[] = $city; }

$pg = paginate(scalar_count("SELECT COUNT(*) FROM hospitals $where", $args), 9);
$stmt = db()->prepare("SELECT name, address, city, phone FROM hospitals $where ORDER BY city, name LIMIT {$pg['per']} OFFSET {$pg['offset']}");
$stmt->execute($args);
$rows = $stmt->fetchAll();

$ps = db()->prepare("SELECT name, address, city, latitude, longitude FROM hospitals $where AND latitude IS NOT NULL AND longitude IS NOT NULL");
$ps->execute($args);
$pins = array_map(fn($h) => ['name' => $h['name'], 'address' => $h['address'] . ', ' . $h['city'], 'lat' => (float) $h['latitude'], 'lng' => (float) $h['longitude']], $ps->fetchAll());
$cities = db()->query("SELECT DISTINCT city FROM hospitals WHERE status = 'active' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$counts = [
    ['fa-hospital',     scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"),                  'Active hospitals'],
    ['fa-city',         count($cities),                                                                         'Cities covered'],
    ['fa-location-dot', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active' AND latitude IS NOT NULL"), 'Shown on the map'],
    ['fa-syringe',      scalar_count("SELECT COUNT(*) FROM child_vaccinations WHERE status='vaccinated'"),     'Doses recorded'],
];
$me = current_user();
$bookLink = ($me && $me['role'] === 'parent') ? 'parent/book.php' : 'register.php';

require __DIR__ . '/includes/header.php';
page_banner('Partner Hospitals', 'Find a hospital near you and book a vaccination slot');
?>
<section class="section <?= sec_bg() ?>"><div class="container">
  <form method="get" class="filter-bar row g-2 align-items-end mb-4" data-aos="fade-up">
    <div class="col-md-6"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Hospital name or area"></div>
    <div class="col-md-3"><label class="form-label" for="city">City</label><select class="form-select" id="city" name="city"><option value="">All cities</option>
      <?php foreach ($cities as $c): ?><option <?= $c === $city ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-vx flex-grow-1">Search</button><?php if ($q !== '' || $city !== ''): ?><a class="btn btn-outline-vx" href="hospitals.php">Clear</a><?php endif; ?></div>
  </form>
  <p class="text-muted"><?= $pg['total'] ?> hospital<?= $pg['total'] === 1 ? '' : 's' ?> found</p>
  <div class="row g-4">
    <?php if (!$rows): ?><div class="col-12"><div class="role-card text-center"><h4>No hospitals match your search</h4><p class="mb-0">Try a different name or choose all cities.</p></div></div><?php endif; ?>
    <?php foreach ($rows as $i => $h): ?>
      <div class="col-md-6 col-lg-4" <?= aos($i, 'mix') ?>><div class="hosp-card">
        <div class="role-ico"><i class="fas fa-hospital"></i></div><h4><?= e($h['name']) ?></h4>
        <p><i class="bi bi-geo-alt me-1"></i><?= e($h['address']) ?>, <?= e($h['city']) ?></p>
        <?php if ($h['phone']): ?><p><i class="bi bi-telephone me-1"></i><?= e($h['phone']) ?></p><?php endif; ?>
        <a class="btn btn-vx btn-sm mt-auto align-self-start" href="<?= url($bookLink) ?>">Book a slot</a></div></div>
    <?php endforeach; ?>
  </div>
  <div class="d-flex justify-content-center mt-4"><?= pagination_links($pg) ?></div>
</div></section>

<section class="section <?= sec_bg() ?>"><?php section_head('Find us on the map', 'Tap a pin to see the hospital'); ?>
  <div class="container" data-aos="zoom-in">
    <?php if ($pins): ?><div id="dirMap" class="map-wide" role="application" aria-label="Map of hospitals"></div>
    <?php else: ?><div class="role-card text-center"><h4>No map locations yet</h4><p class="mb-0">Hospitals appear here once they add a location.</p></div><?php endif; ?>
  </div></section>
<?php
sec_stats($counts);

sec_chips('Cities with hospitals', 'Where you can book today', $cities ?: ['Hospitals will appear here soon']);

sec_cards('How to choose a hospital', 'A few things worth checking', [
  ['bi-geo-alt', 'Distance', 'Pick one you can reach easily with a small child.'], ['bi-clock', 'Free time slots', 'Choose a time that is not full.'],
  ['bi-telephone', 'Phone number', 'Call the hospital if you have a special question.'], ['bi-calendar-check', 'Date', 'Pick a day on or after the vaccine due date.']], 4);

sec_cards('What hospitals do on VaxTrack', 'From appointment to record', [
  ['bi-calendar2-check', 'Receive approved bookings', 'Only requests approved by the administrator appear.'],
  ['bi-clipboard2-pulse', 'Vaccinate and record', 'Mark each child vaccinated with one tap.'],
  ['bi-journal-medical', 'Keep a log', 'A searchable history of every dose given.']], 3, 'mini', 'flip');

sec_flow('Booking in four steps', 'What a parent does', [['bi-search', 'Choose hospital'], ['bi-calendar-date', 'Pick date'], ['bi-clock', 'Pick time'], ['bi-send-check', 'Send request']]);

sec_split('Slot capacity', 'Every hospital controls its own pace',
  'Hospitals decide how many patients they can see per half-hour slot. Parents see how many places are left and cannot book a full slot.',
  ['Set from the hospital profile any time', 'Free places shown next to each time', 'No overbooking, no long waits'], 'features.jpg');

sec_cards('Why hospitals join VaxTrack', 'Less paperwork, more patients served', [
  ['bi-person-plus', 'Free registration', 'Create an account with your hospital name, address and phone.'],
  ['bi-clock-history', 'Organised days', 'See the day\'s approved appointments in one list.'],
  ['bi-graph-up', 'Clear records', 'Reports show how many doses you have given.']], 3);

sec_timeline('Register your hospital', 'Four steps to receive appointments', [
  ['Step 1', 'Create an account', 'Choose Register, then I am a hospital.'], ['Step 2', 'Add your details', 'Name, address, phone and an optional map location.'],
  ['Step 3', 'Set your capacity', 'Choose patients per time slot in your profile.'], ['Step 4', 'Start receiving bookings', 'Approved appointments appear on your dashboard.']]);

sec_dashes();

sec_cards('What we ask of hospitals', 'Simple requirements', [
  ['bi-card-checklist', 'Accurate details', 'Correct name, address and phone number.'], ['bi-check2-square', 'Honest records', 'Mark vaccinations only when they are given.'],
  ['bi-bell', 'Check regularly', 'Look at appointments before each day starts.'], ['bi-lock', 'Protect the login', 'Keep your password private.']], 4, 'mini', 'zoom');

sec_dark('Safety and standards', 'Care in every record', [
  ['bi-person-lock', 'Own data only', 'A hospital sees only its own appointments.'], ['bi-key', 'Secure login', 'Hashed passwords and login limits.'],
  ['bi-shield-check', 'Protected forms', 'Every action carries a secret token.'], ['bi-journal-check', 'Clear history', 'Every change is recorded.']]);

sec_faq('Hospital questions', 'Answers for hospital staff', [
  ['Does it cost anything to join?', 'No. Registering a hospital is free.'], ['Who approves bookings?', 'The administrator reviews each request before the hospital sees it.'],
  ['What if a child does not come?', 'Choose Not vaccinated. The dose returns to pending and the parent is told to book again.'],
  ['Can I change my capacity?', 'Yes. Update patients per time slot in your hospital profile at any time.']]);

sec_cta('Register your hospital', 'Start receiving approved vaccination appointments today.', ['Register a hospital', 'register.php'], ['Contact us', 'contact.php'], 'hero-carousel/hero-carousel-3.jpg');
$pageScripts = '';
if ($pins) {
    ob_start(); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var pins = <?= json_encode($pins) ?>, map = L.map('dirMap', { scrollWheelZoom: false }), b = [];
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
pins.forEach(function (p) {
  var box = document.createElement('div'), t = document.createElement('b'), a = document.createElement('div');
  t.textContent = p.name; a.textContent = p.address; box.appendChild(t); box.appendChild(a);
  L.marker([p.lat, p.lng]).addTo(map).bindPopup(box); b.push([p.lat, p.lng]);
});
map.fitBounds(b, { padding: [30, 30], maxZoom: 12 });
</script>
<?php $pageScripts = ob_get_clean(); }
require __DIR__ . '/includes/footer.php'; ?>