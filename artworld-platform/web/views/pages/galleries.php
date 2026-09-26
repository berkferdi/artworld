<?php /** @var array $galleries */ ?>
<section class="section"><div class="shell">
<header class="section-head"><h1>Foto Galeri</h1></header>
<?php if (empty($galleries)): ?><p class="empty">Henüz galeri yok.</p>
<?php else: ?><div class="news-grid">
<?php foreach ($galleries as $g): ?>
<a class="news-tile" href="<?= e(gallery_url((string) $g['slug'])) ?>">
<div class="news-tile__media"><?php if (!empty($g['cover_image'])): ?><img src="<?= e((string) $g['cover_image']) ?>" alt="" loading="lazy"><?php else: ?><div class="media-fallback"></div><?php endif; ?></div>
<div class="news-tile__body"><h3><?= e((string) $g['title']) ?></h3></div>
</a>
<?php endforeach; ?>
</div><?php endif; ?>
</div></section>
