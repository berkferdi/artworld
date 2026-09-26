<?php
/** @var array $item */
$href = news_url((string) ($item['slug'] ?? ''));
$img = (string) ($item['cover_image'] ?? '');
$cat = (string) ($item['category']['name'] ?? '');
?>
<article class="news-tile">
    <a class="news-tile__media" href="<?= e($href) ?>">
        <?php if ($img !== ''): ?>
            <img src="<?= e($img) ?>" alt="" loading="lazy" width="640" height="360">
        <?php else: ?>
            <div class="media-fallback"></div>
        <?php endif; ?>
    </a>
    <div class="news-tile__body">
        <?php if ($cat !== ''): ?>
            <span class="eyebrow"><?= e($cat) ?></span>
        <?php endif; ?>
        <h3><a href="<?= e($href) ?>"><?= e((string) ($item['title'] ?? '')) ?></a></h3>
        <?php if (!empty($item['summary'])): ?>
            <p><?= e(truncate((string) $item['summary'], 120)) ?></p>
        <?php endif; ?>
        <time datetime="<?= e((string) ($item['published_at'] ?? '')) ?>"><?= e(format_date((string) ($item['published_at'] ?? ''))) ?></time>
    </div>
</article>
