<?php /** @var array $video */ /** @var string|null $embed */ /** @var array $related */ ?>
<article class="article shell">
    <header class="article__header">
        <h1><?= e((string) ($video['title'] ?? '')) ?></h1>
        <time datetime="<?= e((string) ($video['published_at'] ?? '')) ?>"><?= e(format_date((string) ($video['published_at'] ?? ''))) ?></time>
    </header>
    <div class="player-frame player-frame--wide">
        <?php if ($embed): ?>
            <iframe src="<?= e($embed) ?>" title="<?= e((string) $video['title']) ?>" allowfullscreen loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        <?php elseif (!empty($video['video_url'])): ?>
            <video controls poster="<?= e((string) ($video['thumbnail'] ?? '')) ?>" src="<?= e((string) $video['video_url']) ?>"></video>
        <?php endif; ?>
    </div>
    <?php if (!empty($video['description'])): ?>
        <div class="prose"><p><?= nl2br(e((string) $video['description'])) ?></p></div>
    <?php endif; ?>
</article>
<?php if (!empty($related)): ?>
<section class="section"><div class="shell"><header class="section-head"><h2>Benzer Videolar</h2></header>
<div class="video-grid">
<?php foreach ($related as $item): ?>
<a class="video-card" href="<?= e(video_url((string) $item['slug'])) ?>">
<div class="video-card__media"><?php if (!empty($item['thumbnail'])): ?><img src="<?= e((string) $item['thumbnail']) ?>" alt="" loading="lazy"><?php endif; ?><span class="play-badge">▶</span></div>
<h3><?= e((string) $item['title']) ?></h3>
</a>
<?php endforeach; ?>
</div></div></section>
<?php endif; ?>
