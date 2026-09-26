<?php
/** @var list<array> $items */
/** @var string|null $title */
if (empty($items)) {
    return;
}
?>
<section class="section">
    <div class="shell">
        <?php if (!empty($title)): ?>
            <header class="section-head">
                <h2><?= e($title) ?></h2>
            </header>
        <?php endif; ?>
        <div class="news-grid">
            <?php foreach ($items as $item): ?>
                <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
