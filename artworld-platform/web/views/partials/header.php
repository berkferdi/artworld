<?php
/** @var array $settings */
/** @var array $categories */
/** @var array $menuItems */
/** @var string $siteName */
/** @var string $siteTagline */
/** @var string $logo */
$liveEnabled = !empty($settings['live_stream_enabled']);
$bodyClass = $bodyClass ?? '';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$primaryNav = [
    ['title' => 'Ana Sayfa', 'url' => url('/')],
    ['title' => 'Canlı Yayın', 'url' => url('/canli-yayin'), 'live' => true],
    ['title' => 'Videolar', 'url' => url('/videolar')],
    ['title' => 'Programlar', 'url' => url('/programlar')],
    ['title' => 'Foto Galeri', 'url' => url('/foto-galeri')],
    ['title' => 'İletişim', 'url' => url('/iletisim')],
];

$categoryNav = [
    ['title' => 'Gündem', 'url' => url('/gundem'), 'slug' => 'gundem'],
    ['title' => 'Spor', 'url' => url('/spor'), 'slug' => 'spor'],
    ['title' => 'Ekonomi', 'url' => url('/ekonomi'), 'slug' => 'ekonomi'],
    ['title' => 'Kültür Sanat', 'url' => url('/kultur-sanat'), 'slug' => 'kultur-sanat'],
    ['title' => 'Teknoloji', 'url' => url('/teknoloji'), 'slug' => 'teknoloji'],
];

// Merge any extra API menu items not already covered
$known = array_map(static fn($i) => mb_strtolower((string) ($i['title'] ?? '')), array_merge($primaryNav, $categoryNav));
foreach ($menuItems as $item) {
    $t = mb_strtolower(trim((string) ($item['title'] ?? '')));
    if ($t === '' || in_array($t, $known, true)) {
        continue;
    }
    // Skip market ticker leftovers / duplicates
    if (in_array($t, ['yazarlar', 'röportajlar', 'roportajlar', 'arşiv', 'arsiv'], true)) {
        continue;
    }
    $primaryNav[] = [
        'title' => (string) $item['title'],
        'url' => (string) ($item['url'] ?? '#'),
    ];
    $known[] = $t;
}
?>
<header class="site-header">
    <div class="shell header-bar">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e($siteName) ?>">
            <span class="brand__mark">ART WORLD</span>
            <span class="brand__sub">TV</span>
            <?php if ($logo !== ''): ?>
                <img class="brand__logo brand__logo--sr" src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" width="160" height="48">
            <?php endif; ?>
        </a>

        <div class="header-actions">
            <?php if ($liveEnabled): ?>
            <a class="btn btn--live" href="<?= e(url('/canli-yayin')) ?>">
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
            <?php foreach ($primaryNav as $item): ?>
                <?php
                $url = (string) ($item['url'] ?? '#');
                $active = ($url !== '/' && str_starts_with($path, rtrim($url, '/'))) || ($url === '/' && $path === '/');
                $cls = !empty($item['live']) ? 'nav-live' : '';
                if ($active) {
                    $cls .= ($cls !== '' ? ' ' : '') . 'is-active';
                }
                ?>
                <a href="<?= e($url) ?>"<?= $cls !== '' ? ' class="' . e($cls) . '"' : '' ?>><?= e((string) ($item['title'] ?? '')) ?></a>
            <?php endforeach; ?>
            <?php foreach ($categoryNav as $item): ?>
                <?php
                $url = (string) $item['url'];
                $active = str_starts_with($path, $url) || str_contains($path, '/kategori/' . $item['slug']);
                ?>
                <a class="nav-cat<?= $active ? ' is-active' : '' ?>" href="<?= e($url) ?>"><?= e((string) $item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
