/**
 * SOciaLINK - Motion & Micro-interactions (motion.js)
 * Rich button feedback, ripples, magnetic spotlight, confetti bursts & scroll effects.
 */
(function () {
  'use strict';

  var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var BTN_SEL = '.btn-signal, .btn-outline-signal, .btn-ghost, .btn-animated, .bottom-nav-new';

  /* ------------------------------------------------------------------------
     1. BUTTON MAGNETIC PULL & CURSOR SPOTLIGHT (Desktop Mouse)
     ------------------------------------------------------------------------ */
  if (fine && !reduce) {
    document.addEventListener('pointermove', function (e) {
      var b = e.target.closest ? e.target.closest(BTN_SEL) : null;
      if (!b || b.disabled) { return; }
      var r = b.getBoundingClientRect();
      if (!r.width || !r.height) { return; }

      var x = e.clientX - r.left;
      var y = e.clientY - r.top;
      b.style.setProperty('--gx', x + 'px');
      b.style.setProperty('--gy', y + 'px');

      // Subtle magnetic attraction
      var mx = (((x / r.width) - 0.5) * 8).toFixed(1);
      var my = (((y / r.height) - 0.5) * 6).toFixed(1);
      b.style.setProperty('--mx', mx + 'px');
      b.style.setProperty('--my', my + 'px');
    });

    document.addEventListener('pointerout', function (e) {
      var b = e.target.closest ? e.target.closest(BTN_SEL) : null;
      if (!b || (e.relatedTarget && b.contains(e.relatedTarget))) { return; }
      b.style.setProperty('--mx', '0px');
      b.style.setProperty('--my', '0px');
    });
  }

  /* ------------------------------------------------------------------------
     2. TACTILE RIPPLE EFFECT ON BUTTONS
     ------------------------------------------------------------------------ */
  document.addEventListener('pointerdown', function (e) {
    if (reduce) { return; }
    var btn = e.target.closest('.btn, .icon-btn, .bottom-nav a');
    if (!btn || btn.disabled || btn.classList.contains('is-loading')) { return; }

    var rect = btn.getBoundingClientRect();
    var size = Math.max(rect.width, rect.height) * 2.2;
    var ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

    btn.appendChild(ripple);
    setTimeout(function () { ripple.remove(); }, 650);
  });

  /* ------------------------------------------------------------------------
     3. BUTTON LOADING STATE (Spinner on Form Submit)
     ------------------------------------------------------------------------ */
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) { return; }
    var form = e.target;
    var btn = e.submitter || form.querySelector('button[type="submit"].btn');
    if (!btn || !btn.classList.contains('btn') || btn.classList.contains('is-loading')) { return; }

    // Preserve button width so it doesn't jump
    var w = btn.offsetWidth;
    if (w > 0) { btn.style.minWidth = w + 'px'; }

    var spinner = document.createElement('span');
    spinner.className = 'btn-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    btn.appendChild(spinner);
    btn.classList.add('is-loading');
  });

  // Clear spinners if user navigates back using browser cache
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) { return; }
    document.querySelectorAll('.btn.is-loading').forEach(function (b) {
      b.classList.remove('is-loading');
      b.style.minWidth = '';
      var sp = b.querySelector('.btn-spinner');
      if (sp) { sp.remove(); }
    });
  });

  /* ------------------------------------------------------------------------
     4. HEART CONFETTI BURST FOR LIKE BUTTONS
     ------------------------------------------------------------------------ */
  function burst(btn) {
    if (reduce) { return; }
    var nParticles = 10;
    for (var i = 0; i < nParticles; i++) {
      var p = document.createElement('span');
      var angle = (Math.PI * 2 * i) / nParticles + (Math.random() * 0.4 - 0.2);
      var distance = 26 + Math.random() * 26;
      p.className = 'burst';
      p.style.setProperty('--dx', Math.cos(angle) * distance + 'px');
      p.style.setProperty('--dy', Math.sin(angle) * distance + 'px');
      btn.appendChild(p);

      (function (particle) {
        setTimeout(function () { particle.remove(); }, 750);
      })(p);
    }
  }

  // Hook into window.SL or click listener for likes
  window.SL = window.SL || {};
  window.SL.burst = burst;

  // Intercept like button clicks to ensure burst is triggered
  document.addEventListener('click', function (e) {
    var likeBtn = e.target.closest('[data-like]');
    if (!likeBtn) { return; }
    // When clicked and becomes liked (observed right after class toggle)
    setTimeout(function () {
      if (likeBtn.classList.contains('is-liked')) {
        burst(likeBtn);
      }
    }, 100);
  });

  /* ------------------------------------------------------------------------
     5. STAGGERED SCROLL REVEAL (Cards & Lists)
     ------------------------------------------------------------------------ */
  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    var delayIndex = 0;
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.style.setProperty('--d', (delayIndex++ * 60) + 'ms');
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.06, rootMargin: '0px 0px -20px 0px' }) : null;

  function revealElements(root) {
    var targets = (root || document).querySelectorAll('.post-card:not(.reveal), .person-result:not(.reveal), .empty-state:not(.reveal)');
    Array.prototype.forEach.call(targets, function (el) {
      if (!io || reduce) {
        el.classList.add('reveal', 'in');
        return;
      }
      el.classList.add('reveal');
      io.observe(el);
    });
  }
  revealElements();
  window.SL.revealElements = revealElements;

  /* ------------------------------------------------------------------------
     6. SMOOTH NUMERICAL STAT COUNTER
     ------------------------------------------------------------------------ */
  function animateCounter(el) {
    var target = parseInt(el.textContent, 10);
    if (isNaN(target) || target <= 0 || reduce) { return; }
    var start = 0;
    var duration = 850;
    var startTime = null;
    el.textContent = '0';

    function step(timestamp) {
      if (!startTime) { startTime = timestamp; }
      var progress = Math.min((timestamp - startTime) / duration, 1);
      // Ease out cubic
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(target * eased);
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = target;
      }
    }
    requestAnimationFrame(step);
  }
  document.querySelectorAll('.mini-stats b, .profile-stats b').forEach(animateCounter);

  /* ------------------------------------------------------------------------
     7. SCROLL PROGRESS BAR & BACK TO TOP BUTTON
     ------------------------------------------------------------------------ */
  if (document.body.classList.contains('is-app')) {
    // Scroll progress line
    var bar = document.createElement('div');
    bar.className = 'scroll-progress';
    document.body.appendChild(bar);

    // Floating action button for top
    var fab = document.createElement('button');
    fab.type = 'button';
    fab.className = 'fab-top';
    fab.setAttribute('aria-label', 'Back to top');
    fab.setAttribute('title', 'Back to top');
    fab.innerHTML = '<i class="bi bi-arrow-up"></i>';
    fab.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
    document.body.appendChild(fab);

    var isTicking = false;
    window.addEventListener('scroll', function () {
      if (isTicking) { return; }
      isTicking = true;
      requestAnimationFrame(function () {
        var scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        var ratio = scrollHeight > 0 ? Math.min(window.scrollY / scrollHeight, 1) : 0;
        bar.style.transform = 'scaleX(' + ratio + ')';
        fab.classList.toggle('show', window.scrollY > 450);
        isTicking = false;
      });
    }, { passive: true });
  }

  /* ------------------------------------------------------------------------
     8. THEME TOGGLE ROTATION ANIMATION
     ------------------------------------------------------------------------ */
  document.addEventListener('click', function (e) {
    var themeToggle = e.target.closest('[data-theme-toggle]');
    if (themeToggle) {
      themeToggle.classList.remove('spin');
      void themeToggle.offsetWidth; // Force reflow
      themeToggle.classList.add('spin');
    }
  });

})();
