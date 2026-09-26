(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  const copyBtn = document.querySelector('[data-share-copy]');
  if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(window.location.href);
        copyBtn.textContent = 'Kopyalandı';
        setTimeout(() => { copyBtn.textContent = 'Bağlantıyı kopyala'; }, 1600);
      } catch (_) {
        copyBtn.textContent = 'Kopyalanamadı';
      }
    });
  }

  const breakingInner = document.querySelector('[data-breaking-inner]');
  if (breakingInner && breakingInner.children.length) {
    breakingInner.innerHTML = breakingInner.innerHTML + breakingInner.innerHTML;
  }

  const ticker = document.getElementById('market-ticker');
  if (ticker && ticker.children.length) {
    ticker.innerHTML = ticker.innerHTML + ticker.innerHTML;
  }

  // Independent news carousels
  document.querySelectorAll('[data-news-carousel]').forEach((root) => {
    const slides = Array.from(root.querySelectorAll('.news-carousel__slide'));
    const dots = Array.from(root.querySelectorAll('[data-c-dot]'));
    const prev = root.querySelector('[data-c-prev]');
    const next = root.querySelector('[data-c-next]');
    let index = 0;
    let timer = null;
    const go = (i) => {
      if (!slides.length) return;
      index = (i + slides.length) % slides.length;
      slides.forEach((s, n) => {
        const on = n === index;
        s.classList.toggle('is-active', on);
        s.setAttribute('aria-hidden', on ? 'false' : 'true');
        if (on) s.removeAttribute('tabindex');
        else s.setAttribute('tabindex', '-1');
      });
      dots.forEach((d, n) => d.classList.toggle('is-active', n === index));
    };
    const stop = () => { if (timer) { clearInterval(timer); timer = null; } };
    const start = () => {
      stop();
      if (slides.length < 2) return;
      timer = setInterval(() => go(index + 1), 5500);
    };
    prev?.addEventListener('click', (e) => { e.preventDefault(); go(index - 1); start(); });
    next?.addEventListener('click', (e) => { e.preventDefault(); go(index + 1); start(); });
    dots.forEach((d) => d.addEventListener('click', () => {
      go(parseInt(d.getAttribute('data-c-dot') || '0', 10));
      start();
    }));
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    let touchX = null;
    root.addEventListener('touchstart', (e) => {
      touchX = e.changedTouches[0]?.clientX ?? null;
      stop();
    }, { passive: true });
    root.addEventListener('touchend', (e) => {
      if (touchX == null) return;
      const dx = (e.changedTouches[0]?.clientX ?? touchX) - touchX;
      if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1));
      touchX = null;
      start();
    }, { passive: true });
    go(0);
    start();
  });

  // Numbered manşet
  const manset = document.querySelector('[data-manset]');
  if (manset) {
    const stage = manset.querySelector('[data-manset-stage]');
    const img = manset.querySelector('[data-manset-img]');
    const title = manset.querySelector('[data-manset-title]');
    const summary = manset.querySelector('[data-manset-summary]');
    const cat = manset.querySelector('[data-manset-cat]');
    const items = Array.from(document.querySelectorAll('[data-manset-item], [data-manset-page]'));
    let index = 0;
    let timer = null;

    const apply = (el) => {
      if (!el) return;
      const href = el.getAttribute('data-href') || '#';
      const src = el.getAttribute('data-img') || '';
      if (stage) stage.setAttribute('href', href);
      if (img && src) img.setAttribute('src', src);
      if (title) title.textContent = el.getAttribute('data-title') || '';
      if (summary) summary.textContent = el.getAttribute('data-summary') || '';
      if (cat) cat.textContent = el.getAttribute('data-cat') || '';
      document.querySelectorAll('[data-manset-item]').forEach((b) => {
        b.classList.toggle('is-active', b === el || b.getAttribute('data-index') === el.getAttribute('data-index') || b.getAttribute('data-index') === el.getAttribute('data-manset-page'));
      });
      document.querySelectorAll('[data-manset-page]').forEach((b) => {
        const i = b.getAttribute('data-manset-page');
        const active = i === el.getAttribute('data-manset-page') || i === el.getAttribute('data-index');
        b.classList.toggle('is-active', active);
      });
      index = parseInt(el.getAttribute('data-index') || el.getAttribute('data-manset-page') || '0', 10) || 0;
    };

    items.forEach((el) => el.addEventListener('click', () => {
      apply(el);
      stop();
      start();
    }));

    const pages = Array.from(document.querySelectorAll('[data-manset-page]'));
    const stop = () => { if (timer) { clearInterval(timer); timer = null; } };
    const start = () => {
      stop();
      if (pages.length < 2) return;
      timer = setInterval(() => {
        const next = (index + 1) % pages.length;
        apply(pages[next]);
      }, 6000);
    };
    start();
  }
})();
