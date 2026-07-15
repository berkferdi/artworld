(function () {
  'use strict';

  var sidebar = document.getElementById('adminSidebar');
  var toggle = document.getElementById('sidebarToggle');
  var backdrop = document.getElementById('sidebarBackdrop');

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
  }

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('show');
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      if (sidebar && sidebar.classList.contains('open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      var message = el.getAttribute('data-confirm') || 'Bu işlemi onaylıyor musunuz?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-confirm-click]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      var message = el.getAttribute('data-confirm-click') || 'Bu işlemi onaylıyor musunuz?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  var trMap = {
    ş: 's', Ş: 's', ı: 'i', İ: 'i', ğ: 'g', Ğ: 'g',
    ü: 'u', Ü: 'u', ö: 'o', Ö: 'o', ç: 'c', Ç: 'c'
  };

  function slugify(text) {
    return String(text || '')
      .split('')
      .map(function (ch) { return trMap[ch] || ch; })
      .join('')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .replace(/-{2,}/g, '-');
  }

  document.querySelectorAll('[data-slug-source]').forEach(function (source) {
    var targetSelector = source.getAttribute('data-slug-source');
    var target = document.querySelector(targetSelector);
    if (!target) return;

    var locked = target.value !== '';
    target.addEventListener('input', function () {
      locked = true;
    });

    source.addEventListener('input', function () {
      if (locked && target.value !== '') return;
      target.value = slugify(source.value);
      locked = false;
    });
  });

  document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
    setTimeout(function () {
      try {
        var instance = bootstrap.Alert.getOrCreateInstance(alert);
        instance.close();
      } catch (err) {
        /* ignore */
      }
    }, 6000);
  });
})();
