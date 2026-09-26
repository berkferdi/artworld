<?php /** @var array $category */ /** @var array $news */ /** @var array $meta */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head">
            <h1><?= e((string) ($category['name'] ?? 'Kategori')) ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p style="color:var(--muted);margin:0.5rem 0 0"><?= e((string) $category['description']) ?></p>
            <?php endif; ?>
        </header>
        <?php if (empty($news)): ?>
            <p class="empty">Bu kategoride henüz haber yok.</p>
        <?php else: ?>
            <?php
            $main = $news[0];
            $rest = array_slice($news, 1);
            ?>
            <div class="featured-layout" style="margin-bottom:1.5rem">
                <a class="hero-feature" href="<?= e(news_url((string) ($main['slug'] ?? ''))) ?>">
                    <?php if (!empty($main['cover_image'])): ?>
                        <img src="<?= e((string) $main['cover_image']) ?>" alt="" loading="eager" width="1200" height="675">
                    <?php endif; ?>
                    <div class="hero-feature__copy">
                        <span class="eyebrow"><?= e((string) ($category['name'] ?? '')) ?></span>
                        <h3><?= e((string) ($main['title'] ?? '')) ?></h3>
                        <?php if (!empty($main['summary'])): ?>
                            <p><?= e(truncate((string) $main['summary'], 140)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php if ($rest !== []): ?>
                    <div class="featured-side">
                        <?php foreach (array_slice($rest, 0, 3) as $item): ?>
                            <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($rest) > 3): ?>
                <div class="news-grid">
                    <?php foreach (array_slice($rest, 3) as $item): ?>
                        <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
