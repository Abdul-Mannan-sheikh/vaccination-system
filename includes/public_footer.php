<?php /** Public footer (MediCio template, restyled for VaxTrack). The small template credit below must stay (free-licence requirement). */
$base = url('assets/medicio/'); ?>
  </main>
  <footer class="vx-footer">
    <div class="container vx-foot-top">
      <div class="vx-foot-cta" data-aos="fade-up">
        <div><h4>Never miss a vaccine again.</h4><p>Create a free account and add your first child in under two minutes.</p></div>
        <a class="btn btn-white" href="<?= url('register.php') ?>">Get started free</a>
      </div>
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6">
          <a class="vx-brand" href="<?= url('index.php') ?>"><i class="bi bi-shield-plus"></i><?= APP_NAME ?></a>
          <p class="mt-3">A vaccination management system that connects parents, hospitals and administrators, so every child receives every dose on time.</p>
          <div class="vx-social">
            <a href="https://www.facebook.com/" aria-label="Facebook"><i class="bi bi-facebook"></i></a><a href="https://x.com/?lang=en" aria-label="X"><i class="bi bi-twitter-x"></i></a>
            <a href="https://www.linkedin.com/feed/" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a><a href="https://github.com/" aria-label="GitHub"><i class="bi bi-github"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-6"><h5>Explore</h5>
          <ul><li><a href="<?= url('about.php') ?>">About us</a></li><li><a href="<?= url('how-it-works.php') ?>">How it works</a></li>
            <li><a href="<?= url('vaccines.php') ?>">Vaccines</a></li><li><a href="<?= url('hospitals.php') ?>">Hospitals</a></li></ul></div>
        <div class="col-lg-2 col-6"><h5>Support</h5>
          <ul><li><a href="<?= url('faq.php') ?>">FAQ</a></li><li><a href="<?= url('contact.php') ?>">Contact</a></li>
            <li><a href="<?= url('forgot.php') ?>">Forgot password</a></li><li><a href="<?= url('login.php') ?>">Log in</a></li></ul></div>
        <div class="col-lg-2 col-6"><h5>Join us</h5>
          <ul><li><a href="<?= url('register.php') ?>">For parents</a></li><li><a href="<?= url('register.php') ?>">For hospitals</a></li>
            <li><a href="<?= url('login.php') ?>">Admin login</a></li></ul></div>
        <div class="col-lg-2 col-6"><h5>Contact</h5>
          <ul class="vx-contact"><li><i class="bi bi-geo-alt"></i><span>Karachi, Sindh, Pakistan</span></li>
            <li><i class="bi bi-envelope"></i><span>support@vaxtrack.local</span></li><li><i class="bi bi-clock"></i><span>Online, 24/7</span></li></ul></div>
      </div>
    </div>
    <div class="vx-foot-bottom"><div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <span>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</span>
      <span class="vx-credit">Design based on MediCio by <a href="https://bootstrapmade.com/">BootstrapMade</a></span>
    </div></div>
  </footer>
  <div id="scrollbar"></div>
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <div id="preloader"></div>
  <script src="<?= $base ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $base ?>vendor/aos/aos.js"></script>
  <script src="<?= $base ?>vendor/glightbox/js/glightbox.min.js"></script>
  <script src="<?= $base ?>vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="<?= $base ?>vendor/swiper/swiper-bundle.min.js"></script>
  <script src="<?= $base ?>js/main.js"></script>
  <script>
  // Thin progress line at the top that grows as you scroll
  (function () {
    var bar = document.getElementById('scrollbar');
    function update() { var h = document.documentElement; bar.style.width = (h.scrollTop / ((h.scrollHeight - h.clientHeight) || 1) * 100) + '%'; }
    window.addEventListener('scroll', update, { passive: true }); update();
  })();
  </script>
  <?= $pageScripts ?? '' ?>
</body>
</html>