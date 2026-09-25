<?php /** @var array $program */ /** @var array $episodes */ ?>
<article class="article shell">
    <header class="article__header">
        <h1><?= e((string) ($program['title'] ?? '')) ?></h1>
        <?php if (!empty($program['presenter'])): ?><p class="meta">Sunucu: <?= e((string) $program['presenter']) ?></p><?php endif; ?>
        <?php if (!empty($program['broadcast_day'])): ?><p class="meta"><?= e((string) $program['broadcast_day']) ?><?php if (!empty($program['broadcast_time'])): ?> · <?= e((string) $program['broadcast_time']) ?><?php endif; ?></p><?php endif; ?>
    </header>
    <?php if (!empty($program['cover_image'])): ?>
        <figure class="article__cover"><img src="<?= e((string) $program['cover_image']) ?>" alt=""></figure>
    <?php endif; ?>
    <?php if (!empty($program['description'])): ?>
        <div class="prose"><p><?= nl2br(e((string) $program['description'])) ?></p></div>
    <?php endif; ?>
    <?php if (!empty($episodes)): ?>
        <h2>Bölümler</h2>
        <ul class="plain-list">
            <?php foreach ($episodes as $ep): ?>
                <li>
                    <strong><?= e((string) ($ep['title'] ?? '')) ?></strong>
                    <?php if (!empty($ep['published_at'])): ?> — <?= e(format_date((string) $ep['published_at'])) ?><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
