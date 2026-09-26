<?php /** @var string $q */ /** @var array $results */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head">
            <h1>Arama</h1>
            <form class="search-form search-form--lg" action="<?= e(url('/arama')) ?>" method="get">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Haber, video, program…" minlength="2" required>
                <button type="submit">Ara</button>
            </form>
        </header>

        <?php if (mb_strlen($q) < 2): ?>
            <p class="empty">Aramak için en az 2 karakter girin.</p>
        <?php else: ?>
            <?php foreach (['news' => 'Haberler', 'videos' => 'Videolar', 'programs' => 'Programlar', 'galleries' => 'Galeriler', 'interviews' => 'Röportajlar'] as $key => $label): ?>
                <?php $items = is_array($results[$key] ?? null) ? $results[$key] : []; ?>
                <?php if ($items === []) continue; ?>
                <div class="search-block">
                    <h2><?= e($label) ?></h2>
                    <ul class="plain-list">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $href = match ($key) {
                                'news' => news_url((string) ($item['slug'] ?? '')),
                                'videos' => video_url((string) ($item['slug'] ?? '')),
                                'programs' => program_url((string) ($item['slug'] ?? '')),
                                'galleries' => gallery_url((string) ($item['slug'] ?? '')),
                                'interviews' => interview_url((string) ($item['slug'] ?? '')),
                                default => '#',
                            };
                            ?>
                            <li><a href="<?= e($href) ?>"><?= e((string) ($item['title'] ?? $item['name'] ?? '')) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
