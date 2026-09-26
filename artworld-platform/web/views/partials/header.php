<?php
/** @var array $settings */
/** @var array $categories */
/** @var array $menuItems */
/** @var string $siteName */
/** @var string $siteTagline */
/** @var string $logo */
$liveEnabled = !empty($settings['live_stream_enabled']);
$bodyClass = $bodyClass ?? '';
$isHome = str_contains((string) $bodyClass, 'page-home');
?>
<?php if (!$isHome): ?>
<div class="ticker" data-ticker>
    <div class="ticker__label">PİYASA</div>
    <div class="ticker__track">
        <div class="ticker__inner" id="market-ticker">
            <span>BIST 100 — güncel veri yakında</span>
            <span>ALTIN — servis entegrasyonu bekleniyor</span>
            <span>USD/TRY — servis entegrasyonu bekleniyor</span>
            <span>EUR/TRY — servis entegrasyonu bekleniyor</span>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="shell header-bar">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?>">
            <?php if ($logo !== ''): ?>
                <img class="brand__logo" src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" width="160" height="48">
            <?php else: ?>
                <span class="brand__mark">ART WORLD</span>
                <span class="brand__sub">TV</span>
            <?php endif; ?>
        </a>

        <div class="header-actions">
            <?php if ($liveEnabled): ?>
            <a class="btn btn--live" href="<?= e(url('/canli')) ?>">
                <span class="live-dot" aria-hidden="true"></span>
                Canlı Yayın
            </a>
            <?php endif; ?>
            <form class="search-form" action="<?= e(url('/arama')) ?>" method="get" role="search">
                <label class="sr-only" for="q">Ara</label>
                <input id="q" type="search" name="q" placeholder="Haber ara…" minlength="2" required>
                <button type="submit" aria-label="Ara">Ara</button>
            </form>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-nav-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <nav class="primary-nav" id="primary-nav" data-nav>
        <div class="shell nav-row">
            <?php foreach ($menuItems as $item): ?>
                <a href="<?= e((string) ($item['url'] ?? '#')) ?>"><?= e((string) ($item['title'] ?? '')) ?></a>
            <?php endforeach; ?>
            <?php
            $shown = [];
            foreach ($menuItems as $mi) {
                $shown[] = strtolower((string) ($mi['title'] ?? ''));
            }
            $navCats = 0;
            foreach ($categories as $cat) {
                $name = trim((string) ($cat['name'] ?? ''));
                if ($name === '' || str_contains($name, '/') || mb_strlen($name) > 28) {
                    continue;
                }
                if (in_array(strtolower($name), $shown, true)) {
                    continue;
                }
                echo '<a class="nav-cat" href="' . e(category_url((string) $cat['slug'])) . '">' . e($name) . '</a>';
                $navCats++;
                if ($navCats >= 6) {
                    break;
                }
            }
            ?>
        </div>
    </nav>
</header>
