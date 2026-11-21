<?php
/**
 * Section builders for the public (MediCio) pages.
 * Each sec_* function prints one complete full-width section, so a page is just a list of calls.
 */

function pimg(string $f): string { return url('assets/medicio/img/' . $f); }
function ic(string $i): string { return (str_starts_with($i, 'fa-') ? 'fas ' : 'bi ') . $i; }
function sec_bg(): string { static $n = 0; return $n++ % 2 ? 'light-background' : ''; }   // alternates white / tinted
function aos(int $i, string $kind = 'mix'): string
{
    $sets = ['mix' => ['fade-up', 'zoom-in', 'fade-up', 'flip-up'], 'up' => ['fade-up'], 'zoom' => ['zoom-in'], 'flip' => ['flip-left', 'flip-up']];
    $a = $sets[$kind] ?? $sets['mix'];
    return 'data-aos="' . $a[$i % count($a)] . '" data-aos-delay="' . (($i % 4) * 100) . '"';
}

/** Animated teal banner at the top of an inner page, with a breadcrumb. */
function page_banner(string $title, string $sub = ''): void
{ ?>
  <div class="page-title">
    <div class="heading"><span class="bn-shape s1"></span><span class="bn-shape s2"></span><span class="bn-shape s3"></span>
      <div class="container"><div class="row justify-content-center text-center"><div class="col-lg-8">
        <h1 class="rise"><?= e($title) ?></h1><?php if ($sub !== ''): ?><p class="rise" style="animation-delay:.15s"><?= e($sub) ?></p><?php endif; ?>
      </div></div></div></div>
    <nav class="breadcrumbs"><div class="container"><ol>
      <li><a href="<?= url('index.php') ?>">Home</a></li><li class="current"><?= e($title) ?></li>
    </ol></div></nav>
  </div>
<?php }

function section_head(string $title, string $sub = ''): void
{ ?>
  <div class="container section-title" data-aos="fade-up"><h2><?= e($title) ?></h2><?php if ($sub !== ''): ?><p><?= e($sub) ?></p><?php endif; ?></div>
<?php }

/** Grid of icon cards. $items = [[icon, title, text, optional link], ...]. Style: mini | pain | service. */
function sec_cards(string $title, string $sub, array $items, int $cols = 3, string $style = 'mini', string $anim = 'mix', string $id = ''): void
{
    $col = [2 => 'col-md-6', 3 => 'col-md-4', 4 => 'col-6 col-lg-3'][$cols] ?? 'col-md-4'; ?>
<section <?= $id ? 'id="' . e($id) . '"' : '' ?> class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="row g-4">
  <?php foreach ($items as $i => $it): [$icon, $t, $d] = $it; $href = $it[3] ?? null; ?>
    <div class="<?= $col ?>" <?= aos($i, $anim) ?>>
      <?php if ($style === 'pain'): ?><div class="pain-card"><div class="ic"><i class="<?= ic($icon) ?>"></i></div><h4><?= e($t) ?></h4><p class="mb-0 text-muted"><?= e($d) ?></p></div>
      <?php elseif ($style === 'service'): ?><div class="services"><div class="service-item position-relative"><div class="icon"><i class="<?= ic($icon) ?>"></i></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div></div>
      <?php else: if ($href): ?><a class="topic" href="<?= e($href) ?>"><?php endif; ?>
        <div class="mini-card"><i class="<?= ic($icon) ?>"></i><h5><?= e($t) ?></h5><p><?= e($d) ?></p></div>
      <?php if ($href): ?></a><?php endif; endif; ?>
    </div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Picture on one side, text and bullets on the other. $btn = [label, page]. */
function sec_split(string $tag, string $title, string $text, array $bullets, string $img, bool $reverse = false, ?array $btn = null): void
{ ?>
<section class="section <?= sec_bg() ?>"><div class="container"><div class="row align-items-center gy-4 <?= $reverse ? 'flex-lg-row-reverse' : '' ?>">
  <div class="col-lg-6" data-aos="<?= $reverse ? 'fade-left' : 'fade-right' ?>"><img class="show-img" src="<?= pimg($img) ?>" alt="" loading="lazy"></div>
  <div class="col-lg-6 show-copy" data-aos="<?= $reverse ? 'fade-right' : 'fade-left' ?>"><span class="show-tag"><?= e($tag) ?></span><h3><?= e($title) ?></h3>
    <p class="text-muted"><?= e($text) ?></p>
    <ul class="check-list"><?php foreach ($bullets as $b): ?><li><i class="bi bi-check2-circle"></i><span><?= e($b) ?></span></li><?php endforeach; ?></ul>
    <?php if ($btn): ?><a class="btn btn-vx mt-3" href="<?= url($btn[1]) ?>"><?= e($btn[0]) ?></a><?php endif; ?></div>
</div></div></section>
<?php }

/** Numbered step cards. $items = [[title, text], ...]. */
function sec_steps(string $title, string $sub, array $items): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="row g-4 pt-3">
  <?php foreach ($items as $i => [$t, $d]): ?>
    <div class="col-md-6 col-lg-4" <?= aos($i, 'mix') ?>><div class="step-card"><div class="step-num"><?= $i + 1 ?></div><h4><?= e($t) ?></h4><p class="mb-0 text-muted"><?= e($d) ?></p></div></div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Zig-zag vertical timeline. $items = [[label, title, text], ...]. */
function sec_timeline(string $title, string $sub, array $items): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="vtl">
  <?php foreach ($items as $i => [$l, $t, $d]): ?>
    <div class="vtl-item" data-aos="<?= $i % 2 ? 'fade-left' : 'fade-right' ?>"><span class="vtl-dot"></span>
      <div class="vtl-card"><small><?= e($l) ?></small><h5><?= e($t) ?></h5><p><?= e($d) ?></p></div></div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Red "old way" column next to teal "new way" column. $old / $new = [heading, [points]]. */
function sec_compare(string $title, string $sub, array $old, array $new): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="row g-4">
    <div class="col-lg-6" data-aos="fade-right"><div class="compare-col old"><h4><i class="bi bi-x-circle me-1 text-danger"></i><?= e($old[0]) ?></h4><ul>
      <?php foreach ($old[1] as $p): ?><li><i class="bi bi-dash-circle"></i><?= e($p) ?></li><?php endforeach; ?></ul></div></div>
    <div class="col-lg-6" data-aos="fade-left"><div class="compare-col new"><h4><i class="bi bi-check-circle me-1" style="color:var(--accent-color)"></i><?= e($new[0]) ?></h4><ul>
      <?php foreach ($new[1] as $p): ?><li><i class="bi bi-check2-circle"></i><?= e($p) ?></li><?php endforeach; ?></ul></div></div>
  </div></div></section>
<?php }

/** Dark band with animated counters. $items = [[icon, number, label], ...]. */
function sec_stats(array $items): void
{ ?>
<section class="stat-band"><div class="container"><div class="row gy-4">
  <?php foreach ($items as $i => [$icon, $n, $label]): ?>
    <div class="col-6 col-lg-3" data-aos="zoom-in" data-aos-delay="<?= $i * 100 ?>"><div class="big-stat"><i class="<?= ic($icon) ?>"></i>
      <span class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= (int) $n ?>" data-purecounter-duration="2"></span><p><?= e($label) ?></p></div></div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Dark band with glass cards. */
function sec_dark(string $title, string $sub, array $items): void
{ ?>
<section class="dark-band"><?php section_head($title, $sub); ?>
  <div class="container"><div class="row g-4">
  <?php foreach ($items as $i => [$icon, $t, $d]): ?>
    <div class="col-6 col-lg-3" <?= aos($i, 'up') ?>><div class="trust-item"><i class="<?= ic($icon) ?>"></i><h5><?= e($t) ?></h5><p><?= e($d) ?></p></div></div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Accordion of questions. $items = [[question, answer], ...]. */
function sec_faq(string $title, string $sub, array $items, string $id = ''): void
{ ?>
<section <?= $id ? 'id="' . e($id) . '"' : '' ?> class="faq section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="row justify-content-center"><div class="col-lg-9" data-aos="fade-up"><div class="faq-container">
  <?php foreach ($items as [$q, $a]): ?>
    <div class="faq-item"><h3><?= e($q) ?></h3><div class="faq-content"><p><?= e($a) ?></p></div><i class="faq-toggle bi bi-chevron-right"></i></div>
  <?php endforeach; ?></div></div></div></div></section>
<?php }

/** Scrolling strip of words. */
function sec_marquee(array $words): void
{ ?>
<div class="marquee" aria-hidden="true"><div class="marquee-track">
  <?php for ($r = 0; $r < 2; $r++) foreach ($words as $w): ?><span><i class="bi bi-capsule"></i><?= e($w) ?></span><?php endforeach; ?>
</div></div>
<?php }

/** Pill tabs. $tabs = [[label, [points]], ...]. $id must be unique on the page. */
function sec_tabs(string $id, string $title, string $sub, array $tabs): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container" data-aos="fade-up">
    <ul class="nav nav-pills pill-tabs justify-content-center mb-4" role="tablist">
      <?php foreach ($tabs as $i => [$label]): ?><li class="nav-item"><button class="nav-link <?= $i ? '' : 'active' ?>" data-bs-toggle="pill" data-bs-target="#<?= $id . $i ?>" type="button"><?= e($label) ?></button></li><?php endforeach; ?>
    </ul>
    <div class="tab-content"><div class="row justify-content-center"><div class="col-lg-8">
      <?php foreach ($tabs as $i => [$label, $items]): ?>
        <div class="tab-pane fade <?= $i ? '' : 'show active' ?>" id="<?= $id . $i ?>"><div class="role-card"><h4><?= e($label) ?></h4>
          <ul class="check-list mt-2"><?php foreach ($items as $it): ?><li><i class="bi bi-check2-circle"></i><span><?= e($it) ?></span></li><?php endforeach; ?></ul></div></div>
      <?php endforeach; ?>
    </div></div></div>
  </div></section>
<?php }

/** Dotted flow of steps. $items = [[icon, label], ...]. */
function sec_flow(string $title, string $sub, array $items): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="flow">
  <?php foreach ($items as $i => [$icon, $t]): ?>
    <div class="flow-item" data-aos="fade-up" data-aos-delay="<?= $i * 150 ?>"><div class="flow-dot"><i class="<?= ic($icon) ?>"></i></div><h5><?= e($t) ?></h5><p class="text-muted small mb-0">Step <?= $i + 1 ?></p></div>
  <?php endforeach; ?></div></div></section>
<?php }

/** Rounded chips. $items = list of strings. */
function sec_chips(string $title, string $sub, array $items): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container text-center" data-aos="fade-up"><?php foreach ($items as $c): ?><span class="chip"><i class="bi bi-check2-circle"></i><?= e($c) ?></span><?php endforeach; ?></div></section>
<?php }

/** Horizontally scrolling milestone cards. $rows = [['age_days' => , 'n' => ], ...]. */
function sec_miles(string $title, string $sub, array $rows): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head($title, $sub); ?>
  <div class="container"><div class="miles" data-aos="fade-up">
  <?php foreach ($rows as $m): ?><div class="mile"><b><?= (int) $m['n'] ?></b><span>vaccine<?= $m['n'] > 1 ? 's' : '' ?></span><h6><?= age_label((int) $m['age_days']) ?></h6></div><?php endforeach; ?>
  </div></div></section>
<?php }

/** Big statement on a moving gradient. */
function sec_callout(string $icon, string $text): void
{ ?>
<section class="callout"><div class="container" data-aos="zoom-in"><i class="<?= ic($icon) ?>"></i><h3><?= e($text) ?></h3></div></section>
<?php }

/** Three illustrative dashboard previews. */
function sec_dashes(): void
{ ?>
<section class="section <?= sec_bg() ?>"><?php section_head('One platform, three dashboards', 'Each role sees exactly what it needs'); ?>
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
  </div><p class="text-center text-muted small mt-3 mb-0">Illustrative dashboard previews</p></div></section>
<?php }

/** Big final call to action over a photo. $b1 / $b2 = [label, page]. */
function sec_cta(string $title, string $text, array $b1, ?array $b2 = null, string $img = 'hero-carousel/hero-carousel-2.jpg'): void
{ ?>
<section class="cta-big" style="background-image:url('<?= pimg($img) ?>')"><div class="container" data-aos="zoom-in">
  <h2><?= e($title) ?></h2><p><?= e($text) ?></p>
  <a class="btn btn-white me-2" href="<?= url($b1[1]) ?>"><?= e($b1[0]) ?></a><?php if ($b2): ?><a class="btn btn-line-white" href="<?= url($b2[1]) ?>"><?= e($b2[0]) ?></a><?php endif; ?>
</div></section>
<?php }