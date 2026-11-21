<?php
// Public: Vaccines (15 sections, schedule and availability come from the database)
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Vaccines'; $layout = 'public';

$all = db()->query('SELECT name, protects_from, age_days, is_available FROM vaccines ORDER BY age_days, id')->fetchAll();
$schedule = [];
foreach ($all as $v) $schedule[(int) $v['age_days']][] = $v;
$miles = db()->query('SELECT age_days, COUNT(*) n FROM vaccines GROUP BY age_days ORDER BY age_days')->fetchAll();
$counts = [
    ['fa-syringe',      count($all),                                                       'Vaccines in the schedule'],
    ['fa-circle-check', scalar_count('SELECT COUNT(*) FROM vaccines WHERE is_available = 1'), 'Currently available'],
    ['fa-calendar',     count($miles),                                                     'Age milestones'],
    ['fa-virus-slash',  scalar_count('SELECT COUNT(DISTINCT protects_from) FROM vaccines'), 'Protection groups'],
];
require __DIR__ . '/includes/header.php';
page_banner('Vaccines', 'The childhood schedule and what each vaccine protects against');

sec_split('Why vaccinate', 'Protection that starts on day one',
  'Vaccines teach the body to recognise serious illnesses before it meets them. Giving each dose at the recommended age gives a child the strongest protection when it matters most.',
  ['Doses are spaced over the first 15 months', 'Combination vaccines reduce the number of injections', 'VaxTrack shows exactly which dose is next'], 'about.jpg');

sec_marquee(array_values(array_unique(array_column($all, 'name'))));
?>
<section class="tabs section <?= sec_bg() ?>">
  <?php section_head('Vaccination schedule', 'Recommended doses from birth to 15 months'); ?>
  <div class="container"><div class="row">
    <div class="col-lg-3"><ul class="nav nav-tabs flex-column">
      <?php $first = true; foreach ($schedule as $age => $list): ?>
        <li class="nav-item"><a class="nav-link <?= $first ? 'active show' : '' ?>" data-bs-toggle="tab" href="#age-<?= $age ?>"><?= age_label($age) ?></a></li>
      <?php $first = false; endforeach; ?></ul></div>
    <div class="col-lg-9 mt-4 mt-lg-0"><div class="tab-content">
      <?php $first = true; foreach ($schedule as $age => $list): ?>
        <div class="tab-pane <?= $first ? 'active show' : '' ?>" id="age-<?= $age ?>"><div class="details">
          <h3><?= age_label($age) ?> <small class="text-muted fs-6">&middot; <?= count($list) ?> vaccine<?= count($list) > 1 ? 's' : '' ?></small></h3>
          <?php foreach ($list as $v): ?><div class="vac-item"><i class="fas fa-syringe"></i><div><b><?= e($v['name']) ?></b><span>Protects from <?= e($v['protects_from']) ?></span></div></div><?php endforeach; ?>
        </div></div>
      <?php $first = false; endforeach; ?>
    </div></div>
  </div></div>
</section>
<?php
sec_miles('The vaccination journey', 'How many doses fall at each age', $miles);

sec_cards('What vaccines protect against', 'Serious illnesses that on-time doses help prevent', [
  ['fa-lungs', 'Tuberculosis', 'BCG at birth helps protect infants from severe TB.'], ['fa-shield-virus', 'Polio', 'Prevents a disease that can cause paralysis.'],
  ['fa-droplet', 'Hepatitis B', 'Protects the liver from a long-term viral infection.'], ['fa-head-side-cough', 'DTP and Hib', 'Diphtheria, tetanus, whooping cough and Hib.'],
  ['fa-bacteria', 'Pneumococcal', 'Reduces pneumonia and meningitis risk.'], ['fa-virus', 'Rotavirus', 'A common cause of severe diarrhoea in infants.'],
  ['fa-temperature-high', 'Measles and Rubella', 'Highly contagious, controlled by two doses.'], ['fa-notes-medical', 'Typhoid', 'Protects against a serious waterborne fever.']], 4, 'service', 'flip');

sec_stats($counts);

sec_cards('Why on time matters', 'Every delay leaves a gap in protection', [
  ['bi-shield-plus', 'Earlier protection', 'Babies are most at risk in the first months, so early doses count.'],
  ['bi-people', 'Community protection', 'When most children are vaccinated, illness spreads less for everyone.'],
  ['bi-clipboard-check', 'Fewer catch-ups', 'Staying on schedule means fewer extra visits later.']], 3);

sec_cards('Types of vaccines', 'How the doses are given', [
  ['bi-droplet-half', 'Oral drops', 'Some vaccines, such as polio and rotavirus, are given by mouth.'],
  ['bi-capsule-pill', 'Injections', 'Most vaccines are given as a small injection, usually in the arm or thigh.'],
  ['bi-layers', 'Combination vaccines', 'One injection can protect against several diseases at once.']], 3, 'mini', 'flip');
?>
<section class="section <?= sec_bg() ?>"><?php section_head('Before and after the visit', 'A short checklist for parents'); ?>
  <div class="container"><div class="row g-4">
    <div class="col-lg-6" data-aos="fade-right"><div class="role-card"><h4><i class="bi bi-clipboard-check me-1" style="color:var(--accent-color)"></i>Before the visit</h4><ul class="check-list mt-2">
      <li><i class="bi bi-check2-circle"></i><span>Confirm the booking is approved on your dashboard</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Tell the doctor if your child is unwell or has allergies</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Bring any previous vaccination card</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Arrive about ten minutes early</span></li></ul></div></div>
    <div class="col-lg-6" data-aos="fade-left"><div class="role-card"><h4><i class="bi bi-heart-pulse me-1" style="color:var(--accent-color)"></i>After the visit</h4><ul class="check-list mt-2">
      <li><i class="bi bi-check2-circle"></i><span>Stay a short while so staff can watch your child</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Keep your child comfortable and well fed</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Call your doctor if you are worried about any reaction</span></li>
      <li><i class="bi bi-check2-circle"></i><span>Check the dose appears in your VaxTrack report</span></li></ul></div></div>
  </div></div></section>
<?php
sec_cards('Common reactions', 'Usually mild and short-lived', [
  ['bi-thermometer-half', 'Low fever', 'Can happen for a day or so.'], ['bi-bandaid', 'Sore arm or leg', 'Redness or swelling where the injection was given.'],
  ['bi-emoji-frown', 'Fussiness', 'Babies may cry more or sleep more than usual.'], ['bi-telephone-outbound', 'When to call', 'Ask your doctor if you are worried about anything.']], 4);
?>
<section class="section <?= sec_bg() ?>"><?php section_head('Availability right now', 'Set by the administrator and shown live'); ?>
  <div class="container"><div class="row justify-content-center"><div class="col-lg-9" data-aos="fade-up"><div class="role-card p-2">
    <?php foreach ($all as $v): ?>
      <div class="av-row"><div><b><?= e($v['name']) ?></b><small><?= age_label((int) $v['age_days']) ?> &middot; <?= e($v['protects_from']) ?></small></div>
        <span class="av-tag <?= $v['is_available'] ? 'yes' : 'no' ?>"><?= $v['is_available'] ? 'Available' : 'Unavailable' ?></span></div>
    <?php endforeach; ?>
  </div></div></div></div></section>
<?php
sec_faq('Myths and facts', 'Clearing up common worries', [
  ['Myth: vaccines are only needed at birth.', 'Fact: many doses are given at 6, 10 and 14 weeks and again at 9 and 15 months.'],
  ['Myth: a mild fever means the vaccine did not work.', 'Fact: a mild fever can happen after a vaccine and usually passes quickly. Ask your doctor if you are unsure.'],
  ['Myth: if I miss a dose, everything must restart.', 'Fact: in most cases the schedule continues from where it left off. Your doctor will advise you.'],
  ['Myth: a digital record replaces the doctor.', 'Fact: VaxTrack helps you stay organised. Medical advice always comes from your doctor.']]);

sec_steps('Missed a dose?', 'It is not too late to catch up', [
  ['Open the schedule', 'Your child\'s page marks overdue doses in red.'], ['Book the next free slot', 'Choose a hospital, a date and a time that is not full.'],
  ['Follow your doctor\'s advice', 'The hospital will confirm what is needed and record the dose.']]);

sec_faq('Vaccine questions', 'Short answers', [
  ['Who decides the schedule?', 'The administrator manages the schedule stored in VaxTrack, based on a standard childhood schedule.'],
  ['What if a vaccine is unavailable?', 'You are told before booking, so you never travel for nothing.'],
  ['Can I see my child\'s past doses?', 'Yes. The vaccination report lists every dose with its date and hospital.']]);

sec_cta('Track every dose for your child', 'Add your child to see a personal schedule with due dates.', ['Create free account', 'register.php'], ['Find a hospital', 'hospitals.php']);
require __DIR__ . '/includes/footer.php'; ?>