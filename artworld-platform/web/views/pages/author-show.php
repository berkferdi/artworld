<?php /** @var array $author */ /** @var array $news */ ?>
<article class="article shell">
<header class="article__header">
<?php if (!empty($author['photo'])): ?><img class="author-photo" src="<?= e((string) $author['photo']) ?>" alt="" width="120" height="120"><?php endif; ?>
<h1><?= e((string) ($author['name'] ?? '')) ?></h1>
<?php if (!empty($author['bio'])): ?><p><?= nl2br(e((string) $author['bio'])) ?></p><?php endif; ?>
</header>
</article>
<?php if (!empty($news)): \Web\Core\View::partial('partials/news-grid', ['title' => 'Yazıları', 'items' => $news]); endif; ?>
