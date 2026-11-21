<?php
// Public landing page: a showcase. Details live on the About, How it works, Vaccines, Hospitals, FAQ and Contact pages.
require_once __DIR__ . '/includes/functions.php';
redirect_if_logged_in();

$counts = [
    ['fa-syringe',  scalar_count('SELECT COUNT(*) FROM vaccines'),                        'Vaccines in the schedule'],
    ['fa-hospital', scalar_count("SELECT COUNT(*) FROM hospitals WHERE status='active'"), 'Partner hospitals'],
    ['fa-users',    scalar_count("SELECT COUNT(*) FROM users WHERE role='parent'"),       'Registered parents'],
    ['fa-child',    scalar_count('SELECT COUNT(*) FROM children'),                        'Children protected'],
];
$names = db()->query('SELECT DISTINCT name FROM vaccines ORDER BY age_days')->fetchAll(PDO::FETCH_COLUMN);
$miles = [];
foreach (db()->query('SELECT age_days, COUNT(*) n FROM vaccines GROUP BY age_days ORDER BY age_days') as $m) $miles[] = $m;
$hospitals = db()->query("SELECT name, city FROM hospitals WHERE status = 'active' ORDER BY id DESC LIMIT 8")->fetchAll();

$pageTitle = 'Never miss a vaccine'; $layout = 'public';
require __DIR__ . '/includes/header.php';
$img = url('assets/medicio/img/');
?>
<!-- 1. Hero -->
<section class="vx-hero">
  <div class="container"><div class="row align-items-center gy-5">
    <div class="col-lg-6">
      <span class="eyebrow rise"><i class="bi bi-shield-check"></i> Digital child vaccination records</span>
      <h1 class="rise mt-3" style="animation-delay:.1s">Every dose, <span>on time</span>, for every child.</h1>
      <p class="lead rise mt-3" style="animation-delay:.2s">VaxTrack builds a vaccination schedule from your child's date of birth, lets you book a hospital slot in a few taps, and keeps the complete record safe in one place.</p>
      <div class="d-flex flex-wrap gap-3 mt-4 rise" style="animation-delay:.3s">
        <a href="<?= url('register.php') ?>" class="btn btn-vx btn-lg-vx">Create free account</a>
        <a href="<?= url('how-it-works.php') ?>" class="btn btn-outline-vx btn-lg-vx">See how it works</a>
      </div>
      <div class="hero-trust rise" style="animation-delay:.4s">
        <span><i class="bi bi-check-circle-fill"></i>Free for parents</span><span><i class="bi bi-check-circle-fill"></i>Automatic schedule</span><span><i class="bi bi-check-circle-fill"></i>Secure and private</span>
      </div>
    </div>
    <div class="col-lg-6 rise" style="animation-delay:.25s">
      <div class="mock">
        <div class="float-chip c1"><i class="bi bi-bell-fill"></i>Dose due in 3 days</div>
        <div class="float-chip c2"><i class="bi bi-patch-check-fill"></i>Booking approved</div>
        <div class="mock-head"><div class="mock-av">A</div><div><b>Aayan</b><div class="small text-muted">10 weeks old</div></div></div>
        <div class="mock-row"><div>BCG<small>At birth</small></div><span class="tg ok">Done</span></div>
        <div class="mock-row"><div>Pentavalent-1<small>6 weeks</small></div><span class="tg ok">Done</span></div>
        <div class="mock-row"><div>Pentavalent-2<small>10 weeks</small></div><span class="tg due">Due soon</span></div>
        <div class="mock-row"><div>Pentavalent-3<small>14 weeks</small></div><span class="tg up">Upcoming</span></div>
        <div class="d-flex justify-content-between small mt-3"><span>Vaccination progress</span><b>64%</b></div>
        <div class="mock-bar"><i></i></div>
      </div>
    </div>
  </div></div>
</section>

<!-- 2. Vaccine marquee -->
<div class="marquee" aria-hidden="true"><div class="marquee-track">
  <?php for ($r = 0; $r < 2; $r++) foreach ($names as $n): ?><span><i class="bi bi-capsule"></i><?= e($n) ?></span><?php endforeach; ?>
</div></div>

<!-- 3. The problem -->
<section class="section">
  <?php section_head('The problem with paper', 'Why so many children miss a dose'); ?>
  <div class="container"><div class="row g-4">
    <?php foreach ([['bi-file-earmark-x', 'Lost or damaged cards', 'A paper card is easy to misplace, and without it nobody knows which doses are done.'],
                    ['bi-calendar-x', 'Missed visits', 'If a child is absent on the day, the dose is skipped and nobody follows up.'],
                    ['bi-hourglass-split', 'Slow and manual', 'Staff chase families place by place, which wastes hours and still misses children.']] as $i => [$ic, $t, $d]): ?>
      <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?= $i * 120 ?>"><div class="pain-card"><div class="ic"><i class="bi <?= $ic ?>"></i></div><h4><?= $t ?></h4><p class="mb-0 text-muted"><?= $d ?></p></div></div>
    <?php endforeach; ?>
  </div></div>
</section>

<!-- 4. Live numbers -->
<section class="stat-band"><div class="container"><div class="row gy-4">
  <?php foreach ($counts as $i => [$ic, $n, $label]): ?>
    <div class="col-6 col-lg-3" data-aos="zoom-in" data-aos-delay="<?= $i * 100 ?>"><div class="big-stat"><i class="fas <?= $ic ?>"></i>
      <span class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $n ?>" data-purecounter-duration="2"></span><p><?= $label ?></p></div></div>
  <?php endforeach; ?>
</div></div></section>

<!-- 5. How it works teaser -->
<section class="section">
  <?php section_head('Four steps to protected', 'Simple for parents, simple for hospitals'); ?>
  <div class="container"><div class="flow">
    <?php foreach ([['bi-person-plus', 'Register'], ['bi-emoji-smile', 'Add your child'], ['bi-calendar-check', 'Book a slot'], ['bi-patch-check', 'Get vaccinated']] as $i => [$ic, $t]): ?>
      <div class="flow-item" data-aos="fade-up" data-aos-delay="<?= $i * 150 ?>"><div class="flow-dot"><i class="bi <?= $ic ?>"></i></div><h5><?= $t ?></h5>
        <p class="text-muted small mb-0">Step <?= $i + 1 ?></p></div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-5" data-aos="fade-up"><a class="btn btn-outline-vx" href="<?= url('how-it-works.php') ?>">Read the full guide <i class="bi bi-arrow-right ms-1"></i></a></div></div>
</section>

<!-- 6. Showcase rows -->
<section class="section light-background">
  <div class="container">
    <div class="row align-items-center gy-4 mb-5 pb-3">
      <div class="col-lg-6" data-aos="fade-right"><img class="show-img" src="<?= $img ?>about.jpg" alt="Child receiving a vaccination"></div>
      <div class="col-lg-6 show-copy" data-aos="fade-left"><span class="show-tag">For parents</span><h3>A calm, clear view of every dose</h3>
        <p class="text-muted">Add a child once and the full schedule appears. Upcoming and overdue vaccines are highlighted, and booking is a few taps away.</p>
        <ul class="check-list"><li><i class="bi bi-check2-circle"></i><span>Schedule created from the date of birth</span></li><li><i class="bi bi-check2-circle"></i><span>Alerts for approvals and completed doses</span></li><li><i class="bi bi-check2-circle"></i><span>A printable vaccination card</span></li></ul></div>
    </div>
    <div class="row align-items-center gy-4 flex-lg-row-reverse">
      <div class="col-lg-6" data-aos="fade-left"><img class="show-img" src="<?= $img ?>features.jpg" alt="Doctor with a young patient"></div>
      <div class="col-lg-6 show-copy" data-aos="fade-right"><span class="show-tag">For hospitals</span><h3>Fewer no-shows, no overbooking</h3>
        <p class="text-muted">Hospitals set how many patients they can see per time slot and only receive appointments the administrator has approved.</p>
        <ul class="check-list"><li><i class="bi bi-check2-circle"></i><span>Slot capacity control</span></li><li><i class="bi bi-check2-circle"></i><span>One-tap vaccinated or not vaccinated</span></li><li><i class="bi bi-check2-circle"></i><span>A searchable vaccination log</span></li></ul></div>
    </div>
  </div>
</section>

<!-- 7. Three dashboards -->
<section class="section">
  <?php section_head('One platform, three dashboards', 'Each role sees exactly what it needs'); ?>
  <div class="container"><div class="row g-4">
    <div class="col-lg-4" data-aos="flip-left"><div class="mini-dash"><div class="d-flex justify-content-between align-items-center mb-2"><h4 class="mb-0">Parent</h4><span class="tag-role">My children</span></div>
      <div class="bar-line">Aayan <b class="float-end">8 / 18</b><div class="bar"><i style="width:44%"></i></div></div>
      <div class="bar-line">Zara <b class="float-end">14 / 18</b><div class="bar"><i style="width:78%"></i></div></div>
      <div class="mock-row mt-3"><div>Next dose<small>Pentavalent-2</small></div><span class="tg due">3 days</span></div></div></div>
    <div class="col-lg-4" data-aos="flip-left" data-aos-delay="120"><div class="mini-dash"><div class="d-flex justify-content-between align-items-center mb-2"><h4 class="mb-0">Hospital</h4><span class="tag-role">Today</span></div>
      <div class="mock-row"><div>10:00 AM<small>Hamza, MR-1</small></div><span class="tg ok">Approved</span></div>
      <div class="mock-row"><div>10:30 AM<small>Zara, OPV-3</small></div><span class="tg ok">Approved</span></div>
      <div class="mock-row"><div>11:00 AM<small>Rayyan, TCV</small></div><span class="tg due">Waiting</span></div></div></div>
    <div class="col-lg-4" data-aos="flip-left" data-aos-delay="240"><div class="mini-dash"><div class="d-flex justify-content-between align-items-center mb-2"><h4 class="mb-0">Admin</h4><span class="tag-role">Overview</span></div>
      <div class="bar-line">Approved <b class="float-end">62%</b><div class="bar"><i style="width:62%"></i></div></div>
      <div class="bar-line">Pending <b class="float-end">24%</b><div class="bar"><i style="width:24%"></i></div></div>
      <div class="bar-line">Rejected <b class="float-end">14%</b><div class="bar"><i style="width:14%"></i></div></div></div></div>
  </div>
  <p class="text-center text-muted small mt-3 mb-0">Illustrative dashboard previews</p></div>
</section>

<!-- 8. Schedule milestones (live) -->
<section class="section light-background">
  <?php section_head('The vaccination journey', 'Milestones from birth to 15 months'); ?>
  <div class="container"><div class="miles" data-aos="fade-up">
    <?php foreach ($miles as $m): ?><div class="mile"><b><?= (int) $m['n'] ?></b><span>vaccine<?= $m['n'] > 1 ? 's' : '' ?></span><h6><?= age_label((int) $m['age_days']) ?></h6></div><?php endforeach; ?>
  </div>
  <div class="text-center mt-3" data-aos="fade-up"><a class="btn btn-outline-vx" href="<?= url('vaccines.php') ?>">View the full schedule <i class="bi bi-arrow-right ms-1"></i></a></div></div>
</section>

<!-- 9. Benefits -->
<section class="section">
  <?php section_head('Why families and hospitals choose VaxTrack', 'Small details that make a big difference'); ?>
  <div class="container"><div class="row g-4">
    <?php foreach ([['bi-lightning-charge', 'Fast setup', 'Ready in under two minutes.'], ['bi-geo-alt', 'Find hospitals', 'Pick on a map or from a list.'], ['bi-bell', 'Smart alerts', 'Know the moment a dose is due.'],
      ['bi-printer', 'Print and share', 'Save the card as a PDF.'], ['bi-bar-chart-line', 'Live reports', 'Date-wise charts for admins.'], ['bi-phone', 'Works everywhere', 'Phone, tablet or desktop.']] as $i => [$ic, $t, $d]): ?>
      <div class="col-6 col-lg-4" data-aos="zoom-in" data-aos-delay="<?= ($i % 3) * 100 ?>"><div class="mini-card"><i class="bi <?= $ic ?>"></i><h5><?= $t ?></h5><p><?= $d ?></p></div></div>
    <?php endforeach; ?>
  </div></div>
</section>

<!-- 10. Trust and security -->
<section class="dark-band">
  <?php section_head('Privacy and security first', 'Children\'s health records deserve real protection'); ?>
  <div class="container"><div class="row g-4">
    <?php foreach ([['bi-key', 'Hashed passwords', 'Never stored as plain text.'], ['bi-person-lock', 'Role-based access', 'Parents see only their own children.'],
      ['bi-shield-check', 'Forgery protection', 'Every form carries a secret token.'], ['bi-hourglass-split', 'Login protection', 'Repeated wrong passwords are blocked.']] as $i => [$ic, $t, $d]): ?>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>"><div class="trust-item"><i class="bi <?= $ic ?>"></i><h5><?= $t ?></h5><p><?= $d ?></p></div></div>
    <?php endforeach; ?>
  </div></div>
</section>

<!-- 11. Partner hospitals (live) -->
<section class="section">
  <?php section_head('Hospitals on VaxTrack', 'Ready to vaccinate your child'); ?>
  <div class="container text-center" data-aos="fade-up">
    <?php foreach ($hospitals as $h): ?><span class="chip"><i class="fas fa-hospital"></i><?= e($h['name']) ?> <small class="text-muted"><?= e($h['city']) ?></small></span><?php endforeach; ?>
    <?php if (!$hospitals): ?><p class="text-muted">Hospitals will appear here as they register.</p><?php endif; ?>
    <div class="mt-4"><a class="btn btn-outline-vx" href="<?= url('hospitals.php') ?>">Find a hospital <i class="bi bi-arrow-right ms-1"></i></a></div>
  </div>
</section>

<!-- 12. Quick FAQ -->
<section class="faq section light-background">
  <?php section_head('Quick answers', 'The questions we hear most'); ?>
  <div class="container"><div class="row justify-content-center"><div class="col-lg-9" data-aos="fade-up"><div class="faq-container">
    <?php foreach ([['Is VaxTrack free for parents?', 'Yes. Creating an account, adding children and booking are free for parents.'],
                    ['Who approves a booking?', 'The administrator reviews each request, and you are alerted as soon as it is approved or rejected.'],
                    ['Can I use it on my phone?', 'Yes. The whole site adapts to phones, tablets and desktops.']] as [$q, $a]): ?>
      <div class="faq-item"><h3><?= $q ?></h3><div class="faq-content"><p><?= $a ?></p></div><i class="faq-toggle bi bi-chevron-right"></i></div>
    <?php endforeach; ?>
  </div><div class="text-center mt-4"><a href="<?= url('faq.php') ?>">See all questions <i class="bi bi-arrow-right"></i></a></div></div></div></div>
</section>

<!-- 13. Final call to action -->
<section class="cta-big" style="background-image:url('<?= $img ?>hero-carousel/hero-carousel-2.jpg')"><div class="container" data-aos="zoom-in">
  <h2>Protect every milestone.</h2><p>Join VaxTrack today and give your child a complete, always-available vaccination record.</p>
  <a class="btn btn-white me-2" href="<?= url('register.php') ?>">Create free account</a><a class="btn btn-line-white" href="<?= url('contact.php') ?>">Talk to us</a>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>