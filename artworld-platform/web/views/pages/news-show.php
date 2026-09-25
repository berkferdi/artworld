<?php
/** @var array $news */
/** @var array $related */
$gallery = is_array($news['gallery'] ?? null) ? $news['gallery'] : [];
?>
<article class="article shell">
    <header class="article__header">
        <?php if (!empty($news['category']['name'])): ?>
            <a class="eyebrow" href="<?= e(category_url((string) ($news['category']['slug'] ?? ''))) ?>"><?= e((string) $news['category']['name']) ?></a>
        <?php endif; ?>
        <h1><?= e((string) ($news['title'] ?? '')) ?></h1>
        <div class="article__meta">
            <?php if (!empty($news['author'])): ?>
                <span><?= e((string) $news['author']) ?></span>
            <?php endif; ?>
            <time datetime="<?= e((string) ($news['published_at'] ?? '')) ?>"><?= e(format_date((string) ($news['published_at'] ?? ''))) ?></time>
        </div>
    </header>

    <?php if (!empty($news['cover_image'])): ?>
        <figure class="article__cover">
            <img src="<?= e((string) $news['cover_image']) ?>" alt="" width="1200" height="675">
        </figure>
    <?php endif; ?>

    <?php if (!empty($news['summary'])): ?>
        <p class="article__lead"><?= e((string) $news['summary']) ?></p>
    <?php endif; ?>

    <div class="article__share" data-share>
        <button type="button" data-share-copy>Bağlantıyı kopyala</button>
        <a href="https://twitter.com/intent/tweet?url=<?= e(rawurlencode(absolute_url(news_url((string) $news['slug'])))) ?>&text=<?= e(rawurlencode((string) $news['title'])) ?>" target="_blank" rel="noopener">X</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode(absolute_url(news_url((string) $news['slug'])))) ?>" target="_blank" rel="noopener">Facebook</a>
    </div>

    <div class="article__body prose">
        <?= $news['content'] ?? '' ?>
    </div>

    <?php if ($gallery !== []): ?>
        <div class="article__gallery">
            <?php foreach ($gallery as $img): ?>
                <figure>
                    <img src="<?= e((string) ($img['image_url'] ?? $img['image'] ?? '')) ?>" alt="<?= e((string) ($img['caption'] ?? '')) ?>" loading="lazy">
                    <?php if (!empty($img['caption'])): ?>
                        <figcaption><?= e((string) $img['caption']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>

<?php if (!empty($related)): ?>
    <?php \Web\Core\View::partial('partials/news-grid', ['title' => 'İlgili Haberler', 'items' => $related]); ?>
<?php endif; ?>
