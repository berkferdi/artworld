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

  // Breaking ticker: duplicate for seamless loop
  const breakingInner = document.querySelector('[data-breaking-inner]');
  if (breakingInner && breakingInner.children.length) {
    breakingInner.innerHTML = breakingInner.innerHTML + breakingInner.innerHTML;
  }

  // Market ticker (if present)
  const ticker = document.getElementById('market-ticker');
  if (ticker && ticker.children.length) {
    ticker.innerHTML = ticker.innerHTML + ticker.innerHTML;
  }

  // Hero slider
  const slider = document.querySelector('[data-hero-slider]');
  if (slider) {
    const slides = Array.from(slider.querySelectorAll('.hero-slide'));
    const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));
    const prev = slider.querySelector('[data-hero-prev]');
    const next = slider.querySelector('[data-hero-next]');
    let index = 0;
    let timer = null;
    const intervalMs = 5500;

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

    const start = () => {
      stop();
      if (slides.length < 2) return;
      timer = window.setInterval(() => go(index + 1), intervalMs);
    };
    const stop = () => {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    };

    prev?.addEventListener('click', (e) => { e.preventDefault(); go(index - 1); start(); });
    next?.addEventListener('click', (e) => { e.preventDefault(); go(index + 1); start(); });
    dots.forEach((d) => {
      d.addEventListener('click', () => {
        go(parseInt(d.getAttribute('data-hero-dot') || '0', 10));
        start();
      });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);

    // Touch swipe
    let touchX = null;
    slider.addEventListener('touchstart', (e) => {
      touchX = e.changedTouches[0]?.clientX ?? null;
      stop();
    }, { passive: true });
    slider.addEventListener('touchend', (e) => {
      if (touchX == null) return;
      const dx = (e.changedTouches[0]?.clientX ?? touchX) - touchX;
      if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1));
      touchX = null;
      start();
    }, { passive: true });

    go(0);
    start();
  }
})();
