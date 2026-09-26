<?php /** @var array $gallery */ /** @var array $images */ ?>
<article class="article shell">
<header class="article__header"><h1><?= e((string) ($gallery['title'] ?? '')) ?></h1>
<?php if (!empty($gallery['description'])): ?><p><?= e((string) $gallery['description']) ?></p><?php endif; ?>
</header>
<div class="article__gallery">
<?php foreach ($images as $img): ?>
<figure><img src="<?= e((string) ($img['image'] ?? '')) ?>" alt="<?= e((string) ($img['caption'] ?? '')) ?>" loading="lazy">
<?php if (!empty($img['caption'])): ?><figcaption><?= e((string) $img['caption']) ?></figcaption><?php endif; ?>
</figure>
<?php endforeach; ?>
</div>
</article>
