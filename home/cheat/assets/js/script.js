/* Liquid Glass Shop — minimal, dependency-free interactions. */
(function () {
  'use strict';

  /* Read CSRF token from any csrf input on the page (forms include it). */
  function csrf() {
    var el = document.querySelector('input[name="csrf"]');
    return el ? el.value : '';
  }

  var BASE = (function () {
    // Derive base path from the stylesheet link so subfolders work.
    var link = document.querySelector('link[href*="assets/css/style.css"]');
    if (!link) return '';
    return link.getAttribute('href').replace(/assets\/css\/style\.css.*$/, '').replace(/\/$/, '');
  })();

  function api(action, data) {
    var body = new URLSearchParams(Object.assign({ action: action, csrf: csrf() }, data));
    return fetch(BASE + '/cart_action.php', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    }).then(function (r) { return r.json(); });
  }

  function setCount(n) {
    var badge = document.getElementById('cartCount');
    if (badge) { badge.textContent = n; badge.animate([{ transform: 'scale(1.4)' }, { transform: 'scale(1)' }], { duration: 250 }); }
  }

  /* ---------- Sticky nav shadow ---------- */
  var nav = document.getElementById('nav');
  if (nav) {
    var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------- Reveal on scroll ---------- */
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.08 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Add to cart (AJAX) ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    var id = btn.getAttribute('data-id');
    var qty = 1;
    var src = btn.getAttribute('data-qty-source');
    if (src) { var input = document.querySelector(src); if (input) qty = parseInt(input.value, 10) || 1; }
    btn.disabled = true;
    api('add', { id: id, qty: qty }).then(function (res) {
      if (res && res.ok) {
        setCount(res.count);
        btn.classList.add('added');
        var original = btn.innerHTML;
        if (btn.classList.contains('btn')) { btn.innerHTML = 'Ditambahkan ✓'; }
        setTimeout(function () { btn.classList.remove('added'); if (btn.classList.contains('btn')) btn.innerHTML = original; btn.disabled = false; }, 1200);
      } else { btn.disabled = false; }
    }).catch(function () { btn.disabled = false; });
  });

  /* ---------- Quantity stepper (product page) ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-step]');
    if (!b) return;
    var input = b.parentElement.querySelector('input');
    if (!input) return;
    var val = parseInt(input.value, 10) || 1;
    var max = parseInt(input.max, 10) || 9999;
    val += parseInt(b.getAttribute('data-step'), 10);
    input.value = Math.min(max, Math.max(1, val));
  });

  /* ---------- Cart page: qty update / remove (AJAX + reload totals) ---------- */
  document.addEventListener('click', function (e) {
    var step = e.target.closest('[data-cart-step]');
    if (step) {
      var wrap = step.closest('.stepper');
      var input = wrap.querySelector('[data-cart-qty]');
      var val = (parseInt(input.value, 10) || 1) + parseInt(step.getAttribute('data-cart-step'), 10);
      input.value = Math.max(1, val);
      updateCartRow(input);
    }
    var rem = e.target.closest('[data-cart-remove]');
    if (rem) {
      var id = rem.getAttribute('data-cart-remove');
      api('remove', { id: id }).then(function () { location.reload(); });
    }
  });
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-cart-qty]')) updateCartRow(e.target);
  });
  function updateCartRow(input) {
    var id = input.getAttribute('data-cart-qty');
    var qty = Math.max(1, parseInt(input.value, 10) || 1);
    api('update', { id: id, qty: qty }).then(function () { location.reload(); });
  }

  /* ---------- Product thumbnails ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-thumb]');
    if (!t) return;
    var main = document.getElementById('mainImg');
    if (main) main.src = t.getAttribute('data-thumb');
    document.querySelectorAll('.thumb').forEach(function (x) { x.classList.remove('active'); });
    t.classList.add('active');
  });

  /* ---------- Lightbox ---------- */
  var lb = document.getElementById('lightbox');
  if (lb) {
    var lbImg = lb.querySelector('img');
    document.addEventListener('click', function (e) {
      var img = e.target.closest('[data-lightbox]');
      if (img) { lbImg.src = img.src; lb.hidden = false; document.body.style.overflow = 'hidden'; }
      if (e.target.closest('.lightbox-close') || e.target === lb) { lb.hidden = true; document.body.style.overflow = ''; }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !lb.hidden) { lb.hidden = true; document.body.style.overflow = ''; } });
  }

  /* ---------- Checkout: light client validation ---------- */
  var checkout = document.getElementById('checkoutForm');
  if (checkout) {
    checkout.addEventListener('submit', function (e) {
      var ok = true;
      checkout.querySelectorAll('[required]').forEach(function (f) {
        if (!String(f.value).trim()) { ok = false; f.style.borderColor = '#ff3b30'; }
        else { f.style.borderColor = ''; }
      });
      if (!ok) { e.preventDefault(); }
    });
  }
})();
