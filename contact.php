<?php
// Public: Contact (15 sections). Messages are saved for the admin.
require_once __DIR__ . '/includes/functions.php';

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $n = trim($_POST['cname'] ?? '');    $m = strtolower(trim($_POST['cemail'] ?? ''));
    $s = trim($_POST['csubject'] ?? ''); $t = trim($_POST['cmessage'] ?? '');
    if (!csrf_valid())                                 $err[] = 'Your session expired. Please try again.';
    elseif (!empty($_POST['website']))               { $_SESSION['contact_ok'] = true; redirect('contact.php'); }   // hidden field filled = bot
    elseif (time() - ($_SESSION['last_contact'] ?? 0) < 60) $err[] = 'Please wait a minute before sending another message.';
    else {
        if ($n === '' || mb_strlen($n) > 100)           $err[] = 'Please enter your name.';
        if (!filter_var($m, FILTER_VALIDATE_EMAIL))     $err[] = 'Please enter a valid email.';
        if ($s === '' || mb_strlen($s) > 150)           $err[] = 'Please enter a subject.';
        if (mb_strlen($t) < 10 || mb_strlen($t) > 2000) $err[] = 'Message must be 10 to 2000 characters.';
        if (!$err) {
            db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)')->execute([$n, $m, $s, $t]);
            foreach (db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll() as $a) add_notification((int) $a['id'], 'New contact message', $n . ': ' . $s);
            $_SESSION['last_contact'] = time(); $_SESSION['contact_ok'] = true;
            redirect('contact.php');
        }
    }
}
$ok = !empty($_SESSION['contact_ok']); unset($_SESSION['contact_ok']);
$counts = [
    ['fa-users',    scalar_count("SELECT COUNT(*) FROM users WHERE role='parent'"),       'Parents'],
    ['fa-hospital', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"), 'Hospitals'],
    ['fa-child',    scalar_count('SELECT COUNT(*) FROM children'),                        'Children'],
    ['fa-syringe',  scalar_count("SELECT COUNT(*) FROM child_vaccinations WHERE status='vaccinated'"), 'Doses recorded'],
];

$pageTitle = 'Contact'; $layout = 'public';
require __DIR__ . '/includes/header.php';
page_banner('Contact us', 'Questions, feedback or want to partner with us? Write to us.');
?>
<section class="contact section <?= sec_bg() ?>"><div class="container"><div class="row gy-4">
  <?php foreach ([['bi-geo-alt', 'Address', 'Karachi, Sindh, Pakistan'], ['bi-envelope', 'Email us', 'support@vaxtrack.local'], ['bi-clock', 'Booking hours', 'Online, 24 hours a day']] as $i => [$ic, $t, $d]): ?>
    <div class="col-md-4" <?= aos($i, 'flip') ?>><div class="info-item d-flex flex-column justify-content-center align-items-center"><i class="bi <?= $ic ?>"></i><h3><?= $t ?></h3><p><?= $d ?></p></div></div>
  <?php endforeach; ?>
</div></div></section>

<section class="section <?= sec_bg() ?>"><?php section_head('Send us a message', 'We aim to reply within one working day'); ?>
  <div class="container"><div class="row g-4">
    <div class="col-lg-7" data-aos="fade-right">
      <form method="post" class="php-email-form" novalidate>
        <?= csrf_field() ?><input type="text" name="website" class="visually-hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
        <?php if ($ok): ?><div class="alert alert-success">Thank you! Your message has been sent. We will reply by email.</div><?php endif; ?>
        <?php foreach ($err as $x): ?><div class="alert alert-danger py-2"><?= e($x) ?></div><?php endforeach; ?>
        <div class="row gy-3">
          <div class="col-md-6"><label class="form-label" for="cname">Your name</label><input type="text" class="form-control" id="cname" name="cname" value="<?= old('cname') ?>" required maxlength="100"></div>
          <div class="col-md-6"><label class="form-label" for="cemail">Your email</label><input type="email" class="form-control" id="cemail" name="cemail" value="<?= old('cemail') ?>" required></div>
          <div class="col-12"><label class="form-label" for="csubject">Subject</label><input type="text" class="form-control" id="csubject" name="csubject" value="<?= old('csubject') ?>" required maxlength="150"></div>
          <div class="col-12"><label class="form-label" for="cmessage">Message</label><textarea class="form-control" id="cmessage" name="cmessage" rows="6" required maxlength="2000"><?= old('cmessage') ?></textarea></div>
          <div class="col-12"><button type="submit" class="btn btn-vx px-5">Send message</button></div>
        </div>
      </form>
    </div>
    <div class="col-lg-5" data-aos="fade-left"><div class="role-card"><h4>Before you write</h4>
      <ul class="check-list mt-2">
        <li><i class="bi bi-question-circle"></i><span>Many answers are in the <a href="<?= url('faq.php') ?>">FAQ</a></span></li>
        <li><i class="bi bi-key"></i><span>Forgot your password? <a href="<?= url('forgot.php') ?>">Reset it here</a></span></li>
        <li><i class="bi bi-hospital"></i><span>Hospitals can <a href="<?= url('register.php') ?>">register free</a></span></li>
        <li><i class="bi bi-shield-check"></i><span>We never share your details with anyone</span></li>
      </ul></div></div>
  </div></div></section>

<section class="section <?= sec_bg() ?>"><?php section_head('Where we are', 'Based in Karachi, serving families everywhere'); ?>
  <div class="container" data-aos="zoom-in"><div id="cMap" class="map-wide" role="application" aria-label="Map showing Karachi"></div></div></section>
<?php
sec_cards('Ways to reach us', 'Pick what suits you best', [
  ['bi-envelope-paper', 'Contact form', 'Write to us on this page.'], ['bi-envelope', 'Email', 'support@vaxtrack.local'], ['bi-question-circle', 'Self-help', 'Browse the FAQ for quick answers.']], 3, 'mini', 'flip');

sec_stats($counts);

sec_cards('What can we help with?', 'Common reasons people write to us', [
  ['bi-calendar-check', 'Booking help', 'A problem making or changing a booking.'], ['bi-person-gear', 'Account help', 'Login, password or profile questions.'],
  ['bi-hospital', 'Hospital onboarding', 'Joining VaxTrack as a hospital.'], ['bi-chat-heart', 'Feedback', 'Ideas to make VaxTrack better.'],
  ['bi-handshake', 'Partnerships', 'Working together with us.'], ['bi-bug', 'Report a problem', 'Something looks wrong.']], 3, 'mini', 'zoom');

sec_timeline('What happens after you write', 'From your message to our reply', [
  ['Step 1', 'Message received', 'Your message is saved and the administrator is alerted.'], ['Step 2', 'We read it', 'A team member reviews your question.'],
  ['Step 3', 'We reply by email', 'Expect an answer within one working day.'], ['Step 4', 'Follow up', 'Reply to the email if you need anything more.']]);

sec_faq('Contact questions', 'Before you send', [
  ['How fast will you reply?', 'We aim to reply within one working day.'], ['Do I need an account to write?', 'No. Anyone can send a message from this page.'],
  ['Can I send attachments?', 'Not on this form. Describe the problem and we will tell you if we need more.']]);

sec_steps('Reporting a problem', 'Help us fix it quickly', [
  ['Tell us what you did', 'Describe the page and the steps you took.'], ['Tell us what happened', 'Include any message you saw on screen.'], ['Send it to us', 'Use the form above and we will look into it.']]);

sec_split('For hospitals', 'Want your hospital on VaxTrack?',
  'Registration is free. Add your details, set how many patients you can see per time slot, and start receiving approved appointments.',
  ['Free registration', 'Control your own capacity', 'Searchable vaccination log'], 'features.jpg', true, ['Register a hospital', 'register.php']);

sec_cards('Work with us', 'Open to ideas and partnerships', [
  ['bi-hospital', 'Hospitals and clinics', 'Join and receive appointments.'], ['bi-building', 'Schools and NGOs', 'Help families keep records.'], ['bi-code-slash', 'Developers', 'Share ideas to improve the system.']], 3);

sec_dark('When you contact us', 'Your privacy is respected', [
  ['bi-lock', 'Private', 'Messages are visible only to the administrator.'], ['bi-envelope-check', 'Email only', 'We reply only to the address you give.'],
  ['bi-ban', 'No spam', 'We never add you to mailing lists.'], ['bi-trash', 'Removed on request', 'Ask us to delete your message.']]);

sec_cards('Quick links', 'Jump to a page', [
  ['bi-info-circle', 'About', 'Our story and mission.', url('about.php')], ['bi-diagram-3', 'How it works', 'The full process.', url('how-it-works.php')],
  ['bi-capsule', 'Vaccines', 'The schedule.', url('vaccines.php')], ['bi-hospital', 'Hospitals', 'Find a hospital.', url('hospitals.php')]], 4);

sec_chips('Popular topics', 'What other people ask about', ['Booking a slot', 'Changing a date', 'Forgot password', 'Add a second child', 'Print the card', 'Hospital sign-up']);

sec_cta('Let us keep every child protected', 'Create a free account and add your first child in under two minutes.', ['Create free account', 'register.php'], ['Read the FAQ', 'faq.php']);
$pageScripts = '';
ob_start(); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var m = L.map('cMap', { scrollWheelZoom: false }).setView([24.8607, 67.0011], 11);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(m);
L.marker([24.8607, 67.0011]).addTo(m).bindPopup('VaxTrack, Karachi');
</script>
<?php $pageScripts = ob_get_clean();
require __DIR__ . '/includes/footer.php'; ?>