// Theme switcher (saved in a cookie so PHP can render the same theme on the next page)
(function () {
  var root = document.documentElement;
  function setTheme(t) {
    root.dataset.theme = t;
    root.dataset.bsTheme = t === 'dark' ? 'dark' : 'light';
    document.cookie = 'vx_theme=' + t + ';path=/;max-age=31536000;SameSite=Lax';
    document.querySelectorAll('[data-set-theme]').forEach(function (b) {
      b.classList.toggle('on', b.dataset.setTheme === t);
    });
    document.dispatchEvent(new Event('vx-theme'));
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-set-theme]');
    if (b) setTheme(b.dataset.setTheme);
    if (e.target.closest('[data-toggle-side]')) document.body.classList.toggle('side-open');
  });
  window.vxVar = function (n) { return getComputedStyle(root).getPropertyValue(n).trim(); };
})();


// Phone: close the side menu when tapping outside it, on a menu link, or pressing Escape
(function () {
  document.addEventListener('click', function (e) {
    if (!document.body.classList.contains('side-open')) return;
    if (e.target.closest('[data-toggle-side]')) return;
    if (e.target.closest('.sidebar') && !e.target.closest('.side-nav a')) return;
    document.body.classList.remove('side-open');
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.body.classList.remove('side-open');
  });
})();