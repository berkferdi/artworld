<?php /** @var array $videos */ /** @var array $meta */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h1>Videolar</h1></header>
        <div class="video-grid">
            <?php foreach ($videos as $video): ?>
                <a class="video-card" href="<?= e(video_url((string) $video['slug'])) ?>">
                    <div class="video-card__media">
                        <?php if (!empty($video['thumbnail'])): ?>
                            <img src="<?= e((string) $video['thumbnail']) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <span class="play-badge">▶</span>
                    </div>
                    <h3><?= e((string) $video['title']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
