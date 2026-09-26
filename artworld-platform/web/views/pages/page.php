<?php /** @var array $page */ ?>
<article class="article shell">
<header class="article__header"><h1><?= e((string) ($page['title'] ?? '')) ?></h1></header>
<div class="article__body prose"><?= $page['content'] ?? '' ?></div>
</article>
