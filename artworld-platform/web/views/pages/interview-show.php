<?php /** @var array $interview */ ?>
<article class="article shell">
<header class="article__header">
<h1><?= e((string) ($interview['title'] ?? '')) ?></h1>
<?php if (!empty($interview['author'])): ?><span><?= e((string) $interview['author']) ?></span><?php endif; ?>
<time datetime="<?= e((string) ($interview['published_at'] ?? '')) ?>"><?= e(format_date((string) ($interview['published_at'] ?? ''))) ?></time>
</header>
<?php if (!empty($interview['cover_image'])): ?><figure class="article__cover"><img src="<?= e((string) $interview['cover_image']) ?>" alt=""></figure><?php endif; ?>
<?php if (!empty($interview['summary'])): ?><p class="article__lead"><?= e((string) $interview['summary']) ?></p><?php endif; ?>
<div class="article__body prose"><?= $interview['content'] ?? '' ?></div>
</article>
