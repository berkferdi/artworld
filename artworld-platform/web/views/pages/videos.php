<?php /** @var array $videos */ /** @var array $meta */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h1>Videolar</h1></header>
        <?php if (empty($videos)): ?>
            <p class="empty">Henüz video yok.</p>
        <?php else: ?>
            <div class="video-grid">
                <?php foreach ($videos as $video): ?>
                    <a class="video-card" href="<?= e(video_url((string) $video['slug'])) ?>">
                        <div class="video-card__media">
                            <?php if (!empty($video['thumbnail'])): ?>
                                <img src="<?= e((string) $video['thumbnail']) ?>" alt="" loading="lazy" width="480" height="270">
                            <?php endif; ?>
                            <span class="play-badge" aria-hidden="true">▶</span>
                        </div>
                        <h3><?= e((string) $video['title']) ?></h3>
                        <?php if (!empty($video['duration_seconds'])): ?>
                            <span class="meta"><?= e((string) gmdate('H:i:s', (int) $video['duration_seconds'])) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($meta['total_pages']) && (int) $meta['total_pages'] > 1): ?>
            <nav class="pager">
                <?php $page = (int) ($meta['page'] ?? 1); $total = (int) $meta['total_pages']; ?>
                <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">Önceki</a><?php endif; ?>
                <span><?= $page ?> / <?= $total ?></span>
                <?php if ($page < $total): ?><a href="?page=<?= $page + 1 ?>">Sonraki</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
