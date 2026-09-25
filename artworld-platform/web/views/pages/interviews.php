<?php /** @var array $interviews */ ?>
<section class="section"><div class="shell">
<header class="section-head"><h1>Röportajlar</h1></header>
<?php if (empty($interviews)): ?><p class="empty">Henüz röportaj yok.</p>
<?php else: ?><div class="news-grid">
<?php foreach ($interviews as $item): ?>
<a class="news-tile" href="<?= e(interview_url((string) $item['slug'])) ?>">
<div class="news-tile__media"><?php if (!empty($item['cover_image'])): ?><img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy"><?php else: ?><div class="media-fallback"></div><?php endif; ?></div>
<div class="news-tile__body"><h3><?= e((string) $item['title']) ?></h3>
<?php if (!empty($item['summary'])): ?><p><?= e(truncate((string) $item['summary'], 120)) ?></p><?php endif; ?>
</div></a>
<?php endforeach; ?>
</div><?php endif; ?>
</div></section>
