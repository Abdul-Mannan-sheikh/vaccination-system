<?php
if (($layout ?? 'public') === 'public') {
    require __DIR__ . '/public_footer.php';
    return;
}
?>
<?php if (($layout ?? 'public') === 'app'): ?>
    </main>
  </div>
</div>
<?php else: ?>
</main>
<footer class="pub-footer">
  <div class="container d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; <?= date('Y') ?> <?= APP_NAME ?> &ndash; Vaccination Management System</span>
    <span>PHP &middot; MySQL &middot; Bootstrap</span>
  </div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>
