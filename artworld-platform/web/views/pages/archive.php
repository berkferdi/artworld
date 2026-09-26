<?php /** @var array $news */ /** @var string $category */ /** @var string $date */ /** @var array $categories */ ?>
<section class="section"><div class="shell">
<header class="section-head"><h1>Arşiv</h1>
<form class="archive-filters" method="get">
<label>Kategori
<select name="category">
<option value="">Tümü</option>
<?php foreach ($categories as $cat): ?>
<option value="<?= e((string) $cat['slug']) ?>" <?= $category === ($cat['slug'] ?? '') ? 'selected' : '' ?>><?= e((string) $cat['name']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Tarih<input type="date" name="date" value="<?= e($date) ?>"></label>
<button class="btn btn--ghost" type="submit">Filtrele</button>
</form>
</header>
<?php if (empty($news)): ?><p class="empty">Sonuç bulunamadı.</p>
<?php else: ?><div class="news-grid">
<?php foreach ($news as $item): \Web\Core\View::partial('partials/news-tile', ['item' => $item]); endforeach; ?>
</div><?php endif; ?>
</div></section>
