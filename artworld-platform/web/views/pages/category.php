<?php /** @var array $category */ /** @var array $news */ /** @var array $meta */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head">
            <h1><?= e((string) ($category['name'] ?? 'Kategori')) ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p><?= e((string) $category['description']) ?></p>
            <?php endif; ?>
        </header>
        <?php if (empty($news)): ?>
            <p class="empty">Bu kategoride henüz haber yok.</p>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($news as $item): ?>
                    <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
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
