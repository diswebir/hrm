/* =============================================================
   HRM — اسکریپت‌های رابط کاربری (بدون وابستگی خارجی)
   ============================================================= */
(function () {
  'use strict';

  // ---------- تم ----------
  var root = document.documentElement;
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-toggle-theme]');
    if (t) {
      var cur = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', cur);
      try { localStorage.setItem('hrm_theme', cur); } catch (e) {}
      fetch('?' , {method:'GET'}).catch(function(){}); // no-op keepsalive
      // ذخیره در کوکی برای سرور
      document.cookie = 'hrm_theme=' + cur + ';path=/;max-age=31536000;samesite=Lax';
    }
  });

  // ---------- سایدبار موبایل ----------
  var burger = document.querySelector('[data-burger]');
  if (burger) burger.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
  document.addEventListener('click', function (ev) {
    if (document.body.classList.contains('sidebar-open') &&
        !ev.target.closest('.sidebar') && !ev.target.closest('[data-burger]')) {
      document.body.classList.remove('sidebar-open');
    }
  });

  // ---------- منوها ----------
  document.addEventListener('click', function (ev) {
    var trig = ev.target.closest('[data-dd]');
    document.querySelectorAll('.dropdown.on').forEach(function (d) {
      if (!trig || d !== document.getElementById(trig.getAttribute('data-dd'))) d.classList.remove('on');
    });
    if (trig) {
      ev.preventDefault();
      var box = document.getElementById(trig.getAttribute('data-dd'));
      if (box) box.classList.toggle('on');
    }
  });

  // ---------- تاستر ----------
  setTimeout(function () {
    document.querySelectorAll('.toast').forEach(function (t) {
      t.style.transition = 'opacity .4s, transform .4s';
      t.style.opacity = '0';
      t.style.transform = 'translateX(-20px)';
      setTimeout(function () { t.remove(); }, 420);
    });
  }, 4500);

  // ---------- مودال ----------
  document.addEventListener('click', function (ev) {
    var open = ev.target.closest('[data-modal]');
    if (open) {
      ev.preventDefault();
      var m = document.getElementById(open.getAttribute('data-modal'));
      if (m) { m.classList.add('on'); document.body.style.overflow = 'hidden'; }
      return;
    }
    if (ev.target.classList.contains('modal-back') || ev.target.closest('[data-close-modal]')) {
      ev.target.closest('.modal-back') && ev.target.closest('.modal-back').classList.remove('on');
      document.querySelectorAll('.modal-back.on').forEach(function (m) {
        if (ev.target === m || ev.target.closest('[data-close-modal]')) m.classList.remove('on');
      });
      document.body.style.overflow = '';
    }
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
      document.querySelectorAll('.modal-back.on').forEach(function (m) { m.classList.remove('on'); });
      document.body.style.overflow = '';
    }
  });

  // ---------- تأیید حذف ----------
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f.matches('[data-confirm]')) {
      var msg = f.getAttribute('data-confirm') || 'آیا مطمئن هستید؟';
      if (!confirm(msg)) ev.preventDefault();
    }
  });

  // ---------- جستجوی سراسری ----------
  var sInput = document.getElementById('globalSearch');
  var sBox = document.getElementById('searchResults');
  var sTimer;
  if (sInput && sBox) {
    sInput.addEventListener('input', function () {
      clearTimeout(sTimer);
      var q = sInput.value.trim();
      if (q.length < 2) { sBox.classList.remove('on'); sBox.innerHTML = ''; return; }
      sTimer = setTimeout(function () {
        fetch((window.HRM_BASE || '') + '/search?q=' + encodeURIComponent(q), {
          headers: { 'X-Requested-With': 'fetch' }
        }).then(function (r) { return r.json(); }).then(function (data) {
          if (!data || !data.items) return;
          if (!data.items.length) {
            sBox.innerHTML = '<div style="padding:14px;color:var(--muted);font-size:13px">نتیجه‌ای یافت نشد</div>';
          } else {
            sBox.innerHTML = data.items.map(function (it) {
              return '<a href="' + it.url + '"><div class="t">' + it.title + '</div><div class="d">' + it.desc + '</div></a>';
            }).join('');
          }
          sBox.classList.add('on');
        }).catch(function () {});
      }, 250);
    });
    document.addEventListener('click', function (ev) {
      if (!ev.target.closest('.search-box')) sBox.classList.remove('on');
    });
  }

  // ---------- انتخاب همه چک‌باکس‌ها ----------
  document.addEventListener('change', function (ev) {
    if (ev.target.matches('[data-check-all]')) {
      var name = ev.target.getAttribute('data-check-all');
      document.querySelectorAll('[data-check="' + name + '"]').forEach(function (c) {
        c.checked = ev.target.checked;
      });
    }
  });

  // ---------- دکمه چاپ ----------
  document.addEventListener('click', function (ev) {
    var p = ev.target.closest('[data-print]');
    if (p) { ev.preventDefault(); window.print(); }
  });

  // ---------- به‌روزرسانی خودکار شمارنده اعلان‌ها هر ۶۰ ثانیه ----------
  setInterval(function () {
    var el = document.querySelector('.icon-btn .dot');
    if (!el || !window.HRM_BASE) return;
    fetch(window.HRM_BASE + '/notifications?count=1', { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && typeof d.count === 'number') {
          if (d.count === 0) el.remove();
          else el.textContent = d.count > 99 ? '99+' : d.count;
        }
      }).catch(function () {});
  }, 60000);
})();
