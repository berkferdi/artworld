<?php /** @var array $authors */ ?>
<section class="section"><div class="shell">
<header class="section-head"><h1>Yazarlar</h1></header>
<?php if (empty($authors)): ?><p class="empty">Henüz yazar kaydı yok.</p>
<?php else: ?><div class="program-grid">
<?php foreach ($authors as $a): ?>
<a class="program-card" href="<?= e(author_url((string) $a['slug'])) ?>">
<?php if (!empty($a['photo'])): ?><img src="<?= e((string) $a['photo']) ?>" alt="" loading="lazy"><?php endif; ?>
<div><h3><?= e((string) $a['name']) ?></h3><p><?= e(truncate((string) ($a['bio'] ?? ''), 100)) ?></p></div>
</a>
<?php endforeach; ?>
</div><?php endif; ?>
</div></section>
