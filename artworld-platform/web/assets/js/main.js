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

  // Duplicate ticker content for seamless loop
  const ticker = document.getElementById('market-ticker');
  if (ticker && ticker.children.length) {
    ticker.innerHTML = ticker.innerHTML + ticker.innerHTML;
  }
})();
