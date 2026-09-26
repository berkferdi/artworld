<?php
/** @var array $news */
/** @var array $related */
/** @var array $mostRead */
$gallery = is_array($news['gallery'] ?? null) ? $news['gallery'] : [];
$mostRead = $mostRead ?? [];
?>
<div class="shell article-layout">
    <article class="article">
        <header class="article__header">
            <?php if (!empty($news['category']['name'])): ?>
                <a class="eyebrow" href="<?= e(url('/' . (string) ($news['category']['slug'] ?? ''))) ?>"><?= e((string) $news['category']['name']) ?></a>
            <?php endif; ?>
            <h1><?= e((string) ($news['title'] ?? '')) ?></h1>
            <?php if (!empty($news['summary'])): ?>
                <p class="article__lead"><?= e((string) $news['summary']) ?></p>
            <?php endif; ?>
            <div class="article__meta">
                <?php if (!empty($news['author'])): ?>
                    <span><?= e((string) $news['author']) ?></span>
                <?php endif; ?>
                <time datetime="<?= e((string) ($news['published_at'] ?? '')) ?>"><?= e(format_date((string) ($news['published_at'] ?? ''))) ?></time>
                <?php if (!empty($news['source_url'])): ?>
                    <span>Kaynak:
                        <a href="<?= e((string) $news['source_url']) ?>" target="_blank" rel="noopener noreferrer">
                            <?= e((string) ($news['source_name'] ?? 'Art World')) ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
        </header>

        <div class="article__share" data-share>
            <button type="button" data-share-copy>Bağlantıyı kopyala</button>
            <a href="https://twitter.com/intent/tweet?url=<?= e(rawurlencode(absolute_url(news_url((string) $news['slug'])))) ?>&text=<?= e(rawurlencode((string) $news['title'])) ?>" target="_blank" rel="noopener">X</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode(absolute_url(news_url((string) $news['slug'])))) ?>" target="_blank" rel="noopener">Facebook</a>
        </div>

        <?php if (!empty($news['cover_image'])): ?>
            <figure class="article__cover">
                <img src="<?= e((string) $news['cover_image']) ?>" alt="" width="1200" height="675">
            </figure>
        <?php endif; ?>

        <div class="article__body prose">
            <?= $news['content'] ?? '' ?>
        </div>

        <?php if ($gallery !== []): ?>
            <div class="article__gallery">
                <?php foreach ($gallery as $img): ?>
                    <figure>
                        <img src="<?= e((string) ($img['image_url'] ?? $img['image'] ?? '')) ?>" alt="<?= e((string) ($img['caption'] ?? '')) ?>" loading="lazy" width="640" height="400">
                        <?php if (!empty($img['caption'])): ?>
                            <figcaption><?= e((string) $img['caption']) ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <aside class="article-aside">
        <h3>Çok Okunanlar</h3>
        <?php if (empty($mostRead)): ?>
            <p class="empty">—</p>
        <?php else: ?>
            <ol class="ranked-list">
                <?php foreach (array_slice($mostRead, 0, 5) as $i => $item): ?>
                    <li class="<?= empty($item['cover_image']) ? 'no-thumb' : '' ?>">
                        <span class="rank"><?= $i + 1 ?></span>
                        <?php if (!empty($item['cover_image'])): ?>
                            <a href="<?= e(news_url((string) $item['slug'])) ?>">
                                <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="72" height="52">
                            </a>
                        <?php endif; ?>
                        <a href="<?= e(news_url((string) $item['slug'])) ?>"><?= e((string) $item['title']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </aside>
</div>

<?php if (!empty($related)): ?>
    <?php \Web\Core\View::partial('partials/news-grid', ['title' => 'İlgili Haberler', 'items' => $related]); ?>
<?php endif; ?>
