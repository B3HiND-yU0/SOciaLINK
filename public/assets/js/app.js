/* SOciaLINK - front-end behaviour (vanilla JS, no build step) */
(function () {
  'use strict';
  var SL = window.SL || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var MAX_BYTES = 4 * 1024 * 1024;
  var TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  function post(url, body) {
    var opts = { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': SL.csrf } };
    if (body) { opts.body = body; }
    return fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return { ok: false, message: 'Unexpected server response.' }; });
    });
  }

  function toast(message, type) {
    var stack = $('.toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    var t = document.createElement('div');
    t.className = 'sl-toast sl-toast-' + (type || 'info');
    var icon = { success: 'check-circle-fill', danger: 'exclamation-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' }[type || 'info'];
    t.innerHTML = '<i class="bi bi-' + icon + '"></i><span></span><button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';
    t.querySelector('span').textContent = message;
    stack.appendChild(t);
    setTimeout(function () { t.remove(); }, 5400);
  }

  /* ---------- theme ---------- */
  document.addEventListener('click', function (e) {
    var tt = e.target.closest('[data-theme-toggle]');
    if (tt) {
      var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-bs-theme', next);
      tt.classList.remove('spin'); void tt.offsetWidth; tt.classList.add('spin');
      try { localStorage.setItem('sl-theme', next); } catch (err) {}
    }
    var tc = e.target.closest('.toast-close');
    if (tc) { tc.closest('.sl-toast').remove(); }
  });

  /* ---------- confirm on destructive forms ---------- */
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    // prevent double submits
    var btn = e.target.querySelector && e.target.querySelector('button[type="submit"]:not([data-keep])');
    if (btn && !e.defaultPrevented) { setTimeout(function () { btn.disabled = true; }, 0); }
  });

  /* ---------- likes ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-like]');
    if (!btn || btn.dataset.busy) { return; }
    btn.dataset.busy = '1';
    post(SL.likeUrl + btn.getAttribute('data-like')).then(function (res) {
      delete btn.dataset.busy;
      if (!res.ok) { toast(res.message || 'Could not update like.', 'danger'); return; }
      btn.classList.toggle('is-liked', res.liked);
      btn.setAttribute('aria-pressed', res.liked ? 'true' : 'false');
      var lc = btn.querySelector('.like-count'); lc.textContent = res.count; lc.classList.remove('bump'); void lc.offsetWidth; lc.classList.add('bump');
      if (res.liked) { btn.classList.remove('pop'); void btn.offsetWidth; btn.classList.add('pop'); burst(btn); }
    }).catch(function () { delete btn.dataset.busy; toast('Network problem. Try again.', 'danger'); });
  });

  /* ---------- follow ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-follow]');
    if (!btn || btn.dataset.busy) { return; }
    btn.dataset.busy = '1';
    post(SL.followUrl + btn.getAttribute('data-follow')).then(function (res) {
      delete btn.dataset.busy;
      if (!res.ok) { toast(res.message || 'Could not update follow.', 'danger'); return; }
      var full = btn.getAttribute('data-follow-label') === 'full';
      btn.textContent = res.following ? 'Following' : 'Follow';
      btn.classList.remove('pop-in'); void btn.offsetWidth; btn.classList.add('pop-in');
      if (full) {
        btn.classList.toggle('btn-ghost', res.following);
        btn.classList.toggle('is-following', res.following);
        btn.classList.toggle('btn-signal', !res.following);
        var c = $('[data-followers]'); if (c) { c.textContent = res.followers; }
      } else {
        btn.classList.toggle('btn-outline-signal', !res.following);
        btn.classList.toggle('btn-ghost', res.following);
      }
    }).catch(function () { delete btn.dataset.busy; toast('Network problem. Try again.', 'danger'); });
  });

  /* ---------- comments ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toggle-comments]');
    if (t) {
      var list = t.parentNode.querySelector('.comments');
      var collapsed = list.classList.toggle('is-collapsed');
      t.textContent = collapsed ? t.getAttribute('data-label') : 'Show fewer comments';
      if (!collapsed && !t.getAttribute('data-label')) { /* label stored below */ }
    }
    var f = e.target.closest('[data-focus-comment]');
    if (f) { var inp = document.getElementById('comment-input-' + f.getAttribute('data-focus-comment')); if (inp) { inp.focus(); } }

    var ed = e.target.closest('[data-comment-edit]');
    if (ed) {
      var c = ed.closest('.comment');
      c.querySelector('[data-comment-view]').classList.add('d-none');
      c.querySelector('[data-comment-form]').classList.remove('d-none');
      c.querySelector('[data-comment-form] input[name="content"]').focus();
    }
    var cancel = e.target.closest('[data-comment-cancel]');
    if (cancel) {
      var cc = cancel.closest('.comment');
      cc.querySelector('[data-comment-form]').classList.add('d-none');
      cc.querySelector('[data-comment-view]').classList.remove('d-none');
    }
  });
  $$('[data-toggle-comments]').forEach(function (b) { b.setAttribute('data-label', b.textContent); });

  /* ---------- edit post modal ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-post-edit]');
    if (!b) { return; }
    var form = $('#editPostForm'); if (!form) { return; }
    form.action = SL.postUpdateUrl + b.getAttribute('data-id');
    $('#editPostContent').value = b.getAttribute('data-content') || '';
    var img = b.getAttribute('data-image');
    var wrap = $('#editPostImageWrap');
    wrap.classList.toggle('d-none', !img);
    if (img) { $('#editPostImage').src = img; }
    $('#editPostRemove').checked = false;
    form.querySelector('[data-image-input]').value = '';
    var submit = form.querySelector('button[type="submit"]'); if (submit) { submit.disabled = false; }
    bootstrap.Modal.getOrCreateInstance($('#editPostModal')).show();
  });

  /* ---------- textarea helpers ---------- */
  function updateCounter(el) {
    var sel = el.getAttribute('data-counter'); if (!sel) { return; }
    var out = $(sel); if (!out) { return; }
    var max = parseInt(el.getAttribute('maxlength'), 10) || 0;
    out.textContent = el.value.length + '/' + max;
    out.classList.toggle('near', max && el.value.length > max * 0.9);
  }
  function autogrow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 280) + 'px'; }
  document.addEventListener('input', function (e) {
    var el = e.target;
    if (el.matches && el.matches('textarea')) {
      if (el.hasAttribute('data-autogrow')) { autogrow(el); }
      updateCounter(el);
      var form = el.closest('[data-composer]'); if (form) { refreshComposer(form); }
    }
  });
  $$('textarea[data-counter]').forEach(updateCounter);

  /* ---------- image inputs: validation + previews ---------- */
  function refreshComposer(form) {
    var text = form.querySelector('textarea').value.trim();
    var file = form.querySelector('input[type="file"]').files.length > 0;
    form.querySelector('[data-composer-submit]').disabled = !(text || file);
  }
  document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches || !input.matches('[data-image-input]')) { return; }
    var file = input.files && input.files[0];
    var scope = input.closest('form');
    var errBox = scope && scope.querySelector('#composerError, [data-error-for="' + input.name + '"]');
    function fail(msg) {
      input.value = '';
      if (errBox) { errBox.textContent = msg; } else { toast(msg, 'danger'); }
      if (scope && scope.hasAttribute('data-composer')) { clearComposerPreview(scope); refreshComposer(scope); }
    }
    if (errBox) { errBox.textContent = ''; }
    if (!file) { if (scope && scope.hasAttribute('data-composer')) { clearComposerPreview(scope); refreshComposer(scope); } return; }
    if (TYPES.indexOf(file.type) === -1) { fail('Only JPG, PNG, GIF or WEBP images are allowed.'); return; }
    if (file.size > MAX_BYTES) { fail('Image is too large. Maximum size is 4 MB.'); return; }

    var url = URL.createObjectURL(file);
    var imgSel = input.getAttribute('data-preview-img');
    if (imgSel) { var im = $(imgSel); im.src = url; im.classList.remove('d-none'); }
    var hideSel = input.getAttribute('data-preview-hide');
    if (hideSel) { $(hideSel).classList.add('d-none'); }
    var bgSel = input.getAttribute('data-preview-bg');
    if (bgSel) { $(bgSel).style.backgroundImage = 'url(' + url + ')'; }
    var boxSel = input.getAttribute('data-preview-target');
    if (boxSel) { var box = $(boxSel); box.querySelector('img').src = url; box.classList.remove('d-none'); }
    if (scope && scope.hasAttribute('data-composer')) { refreshComposer(scope); }
  });
  function clearComposerPreview(form) {
    var box = form.querySelector('[data-preview-box]');
    if (box) { box.classList.add('d-none'); box.querySelector('img').removeAttribute('src'); }
  }
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-preview-clear]');
    if (!c) { return; }
    var form = c.closest('form');
    form.querySelector('input[type="file"]').value = '';
    clearComposerPreview(form);
    refreshComposer(form);
  });

  /* ---------- password helpers ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toggle-password]');
    if (!t) { return; }
    var input = $(t.getAttribute('data-toggle-password'));
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    t.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  function scorePassword(v) {
    var s = 0;
    if (v.length >= 8) { s++; }
    if (/[A-Za-z]/.test(v) && /\d/.test(v)) { s++; }
    if (v.length >= 12) { s++; }
    if (/[^A-Za-z0-9]/.test(v) && /[A-Z]/.test(v)) { s++; }
    return v.length === 0 ? 0 : Math.max(1, s);
  }

  /* ---------- client-side validation (server re-validates everything) ---------- */
  function setError(form, name, msg) {
    var box = form.querySelector('[data-error-for="' + name + '"]');
    var input = form.querySelector('[name="' + name + '"]');
    if (box) { box.textContent = msg || ''; }
    if (input && input.classList) { input.classList.toggle('is-invalid', !!msg); }
    return !msg;
  }
  var USER_RE = /^[A-Za-z0-9_]{3,20}$/;

  var reg = $('[data-register-form]');
  if (reg) {
    var pw = $('#password', reg), strength = $('[data-strength]', reg), label = $('[data-strength-label]', reg);
    var words = ['At least 8 characters with a letter and a number.', 'Too weak', 'Okay', 'Good', 'Strong'];
    pw.addEventListener('input', function () {
      var lvl = scorePassword(pw.value);
      strength.setAttribute('data-level', lvl);
      label.textContent = words[lvl];
    });
    reg.addEventListener('submit', function (e) {
      var f = reg.elements, ok = true, name = f.full_name.value.trim();
      ok = setError(reg, 'full_name', name.length < 2 || name.length > 60 ? 'Enter your full name (2–60 characters).' : '') && ok;
      ok = setError(reg, 'username', !USER_RE.test(f.username.value.trim()) ? 'Username must be 3–20 characters: letters, numbers or underscore.' : '') && ok;
      var p = f.password.value;
      ok = setError(reg, 'password', (p.length < 8 || !/[A-Za-z]/.test(p) || !/\d/.test(p)) ? 'Password needs at least 8 characters with a letter and a number.' : '') && ok;
      ok = setError(reg, 'password_confirm', f.password_confirm.value !== p ? 'Passwords do not match.' : '') && ok;
      if (!ok) { e.preventDefault(); e.stopImmediatePropagation(); var bad = reg.querySelector('.is-invalid'); if (bad) { bad.focus(); } }
    }, true);
  }

  var login = $('[data-login-form]');
  if (login) {
    login.addEventListener('submit', function (e) {
      var f = login.elements, ok = true;
      ok = setError(login, 'username', f.username.value.trim() === '' ? 'Enter your username.' : '') && ok;
      ok = setError(login, 'password', f.password.value === '' ? 'Enter your password.' : '') && ok;
      if (!ok) { e.preventDefault(); e.stopImmediatePropagation(); }
    }, true);
  }

  var prof = $('[data-profile-form]');
  if (prof) {
    prof.addEventListener('submit', function (e) {
      var name = prof.elements.full_name.value.trim();
      var ok = setError(prof, 'full_name', name.length < 2 || name.length > 60 ? 'Enter your full name (2–60 characters).' : '');
      if (!ok) { e.preventDefault(); e.stopImmediatePropagation(); prof.elements.full_name.focus(); }
    }, true);
  }

  /* ---------- load more posts ---------- */
  var more = $('#loadMore'), list = $('#feedList');
  if (more && list) {
    more.addEventListener('click', function () {
      var page = parseInt(list.getAttribute('data-next-page'), 10) || 2;
      more.disabled = true; more.textContent = 'Loading…';
      fetch(SL.moreUrl + '&page=' + page, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.ok) { throw new Error(); }
          list.insertAdjacentHTML('beforeend', res.html);
          revealAll(list);
          $$('[data-toggle-comments]', list).forEach(function (b) { if (!b.getAttribute('data-label')) { b.setAttribute('data-label', b.textContent); } });
          list.setAttribute('data-next-page', page + 1);
          if (res.hasMore) { more.disabled = false; more.textContent = 'Load more posts'; } else { more.parentNode.remove(); }
        })
        .catch(function () { more.disabled = false; more.textContent = 'Load more posts'; toast('Could not load more posts.', 'danger'); });
    });
  }

  /* ---------- restore buttons when returning via the back button ---------- */
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { $$('form button[type="submit"]:disabled').forEach(function (b) { if (!b.hasAttribute('data-composer-submit')) { b.disabled = false; } }); }
  });

  /* ---------- deep link to composer ---------- */
  if (location.hash === '#composer') {
    var ta = $('#composer textarea');
    if (ta) { ta.focus({ preventScroll: false }); }
  }


    /* ==================== motion + polish ==================== */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // button ripple
  document.addEventListener('pointerdown', function (e) {
    var b = e.target.closest('.btn, .icon-btn');
    if (!b || b.disabled || reduce) { return; }
    var r = b.getBoundingClientRect(), size = Math.max(r.width, r.height) * 2;
    var s = document.createElement('span');
    s.className = 'ripple';
    s.style.width = s.style.height = size + 'px';
    s.style.left = (e.clientX - r.left - size / 2) + 'px';
    s.style.top = (e.clientY - r.top - size / 2) + 'px';
    b.appendChild(s);
    setTimeout(function () { s.remove(); }, 700);
  });

  // heart burst on like
  function burst(btn) {
    if (reduce) { return; }
    for (var i = 0; i < 9; i++) {
      var p = document.createElement('span');
      var ang = (Math.PI * 2 * i) / 9 + Math.random() * 0.5, dist = 26 + Math.random() * 24;
      p.className = 'burst';
      p.style.setProperty('--dx', Math.cos(ang) * dist + 'px');
      p.style.setProperty('--dy', Math.sin(ang) * dist + 'px');
      btn.appendChild(p);
      (function (n) { setTimeout(function () { n.remove(); }, 800); })(p);
    }
  }

  // scroll reveal
  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    var n = 0;
    entries.forEach(function (en) {
      if (en.isIntersecting) {
        en.target.style.setProperty('--d', (n++ * 70) + 'ms');
        en.target.classList.add('in');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' }) : null;
  function revealAll(root) {
    $$('.post-card:not(.reveal), .person-result:not(.reveal), .empty-state:not(.reveal)', root || document).forEach(function (el) {
      if (!io) { return; }
      el.classList.add('reveal');
      io.observe(el);
    });
  }
  revealAll();

  // animated counters
  function countUp(el) {
    var end = parseInt(el.textContent, 10);
    if (isNaN(end) || end < 1 || reduce) { return; }
    var t0 = null, dur = 900;
    el.textContent = '0';
    (function step(t) {
      if (!t0) { t0 = t; }
      var p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3)));
      if (p < 1) { requestAnimationFrame(step); } else { el.textContent = end; }
    })(performance.now());
  }
  $$('.mini-stats b, .profile-stats b').forEach(countUp);

  // scroll progress bar + back-to-top
  if (document.body.classList.contains('is-app')) {
    var bar = document.createElement('div'); bar.className = 'scroll-progress'; document.body.appendChild(bar);
    var fab = document.createElement('button');
    fab.type = 'button'; fab.className = 'fab-top'; fab.setAttribute('aria-label', 'Back to top');
    fab.innerHTML = '<i class="bi bi-arrow-up"></i>';
    fab.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });
    document.body.appendChild(fab);
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) { return; }
      ticking = true;
      requestAnimationFrame(function () {
        var h = document.documentElement.scrollHeight - window.innerHeight;
        bar.style.transform = 'scaleX(' + (h > 0 ? Math.min(window.scrollY / h, 1) : 0) + ')';
        fab.classList.toggle('show', window.scrollY > 600);
        ticking = false;
      });
    }, { passive: true });
  }

})();
