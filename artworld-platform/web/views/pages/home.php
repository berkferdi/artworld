<?php
/** @var array $breaking */
/** @var array $hero */
/** @var array $latest */
/** @var array $featured */
/** @var array $videos */
/** @var array $programs */
/** @var array|null $live */
/** @var array $mostRead */
/** @var array $selected */

$heroMain = $hero[0] ?? null;
$heroSide = array_slice($hero, 1, 4);
?>

<?php if (!empty($breaking)): ?>
<section class="breaking">
    <div class="shell breaking__row">
        <span class="breaking__badge">SON DAKİKA</span>
        <div class="breaking__items">
            <?php foreach (array_slice($breaking, 0, 8) as $b): ?>
                <?php
                $href = !empty($b['news_slug']) ? news_url((string) $b['news_slug']) : ((string) ($b['target_url'] ?? '#'));
                $text = (string) ($b['title'] ?? '');
                ?>
                <a href="<?= e($href) ?>"><?= e($text) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="hero-stage">
    <div class="shell hero-grid">
        <div class="hero-news">
            <?php if ($heroMain): ?>
                <a class="hero-feature" href="<?= e(news_url((string) $heroMain['slug'])) ?>">
                    <?php if (!empty($heroMain['cover_image'])): ?>
                        <img src="<?= e((string) $heroMain['cover_image']) ?>" alt="" fetchpriority="high" width="1200" height="675">
                    <?php endif; ?>
                    <div class="hero-feature__copy">
                        <?php if (!empty($heroMain['category']['name'])): ?>
                            <span class="eyebrow"><?= e((string) $heroMain['category']['name']) ?></span>
                        <?php endif; ?>
                        <h1><?= e((string) $heroMain['title']) ?></h1>
                        <?php if (!empty($heroMain['summary'])): ?>
                            <p><?= e(truncate((string) $heroMain['summary'], 160)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endif; ?>

            <?php if ($heroSide !== []): ?>
                <div class="hero-rail">
                    <?php foreach ($heroSide as $item): ?>
                        <a class="hero-rail__item" href="<?= e(news_url((string) $item['slug'])) ?>">
                            <?php if (!empty($item['cover_image'])): ?>
                                <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="200" height="120">
                            <?php endif; ?>
                            <span><?= e((string) $item['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="hero-live">
            <div class="live-panel">
                <div class="live-panel__head">
                    <span class="live-dot"></span>
                    <strong>CANLI ART WORLD TV</strong>
                </div>
                <?php if (!empty($live['stream_url'])): ?>
                    <div class="player-frame" data-live-player data-src="<?= e((string) $live['stream_url']) ?>" data-type="<?= e((string) ($live['stream_type'] ?? 'hls')) ?>">
                        <?php if (!empty($live['poster_image'])): ?>
                            <img src="<?= e((string) $live['poster_image']) ?>" alt="" class="player-poster">
                        <?php endif; ?>
                        <a class="btn btn--primary player-cta" href="<?= e(url('/canli')) ?>">Yayını İzle</a>
                    </div>
                    <p class="live-panel__title"><?= e((string) ($live['title'] ?? 'Canlı Yayın')) ?></p>
                <?php else: ?>
                    <div class="player-frame player-frame--empty">
                        <p>Canlı yayın şu an aktif değil.</p>
                        <a class="btn btn--ghost" href="<?= e(url('/canli')) ?>">Canlı sayfası</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<?php \Web\Core\View::partial('partials/news-grid', ['title' => 'Son Haberler', 'items' => array_slice($latest, 0, 8)]); ?>

<?php if (!empty($selected)): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head">
            <h2>Sizin İçin Seçtiklerimiz</h2>
        </header>
        <div class="selected-row">
            <?php foreach (array_slice($selected, 0, 4) as $item): ?>
                <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($videos)): ?>
<section class="section">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Videolar</h2>
            <a href="<?= e(url('/video')) ?>">Tümü</a>
        </header>
        <div class="video-grid">
            <?php foreach (array_slice($videos, 0, 6) as $video): ?>
                <a class="video-card" href="<?= e(video_url((string) $video['slug'])) ?>">
                    <div class="video-card__media">
                        <?php if (!empty($video['thumbnail'])): ?>
                            <img src="<?= e((string) $video['thumbnail']) ?>" alt="" loading="lazy" width="480" height="270">
                        <?php endif; ?>
                        <span class="play-badge">▶</span>
                    </div>
                    <h3><?= e((string) $video['title']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($mostRead)): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head"><h2>Çok Okunanlar</h2></header>
        <ol class="ranked-list">
            <?php foreach (array_slice($mostRead, 0, 8) as $i => $item): ?>
                <li>
                    <span class="rank"><?= $i + 1 ?></span>
                    <a href="<?= e(news_url((string) $item['slug'])) ?>"><?= e((string) $item['title']) ?></a>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($programs)): ?>
<section class="section">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Art World TV Programları</h2>
            <a href="<?= e(url('/programlar')) ?>">Tümü</a>
        </header>
        <div class="program-grid">
            <?php foreach (array_slice($programs, 0, 6) as $program): ?>
                <a class="program-card" href="<?= e(program_url((string) $program['slug'])) ?>">
                    <?php if (!empty($program['cover_image'])): ?>
                        <img src="<?= e((string) $program['cover_image']) ?>" alt="" loading="lazy" width="320" height="180">
                    <?php endif; ?>
                    <div>
                        <h3><?= e((string) $program['title']) ?></h3>
                        <?php if (!empty($program['presenter'])): ?>
                            <p><?= e((string) $program['presenter']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($program['broadcast_day'])): ?>
                            <span class="meta"><?= e((string) $program['broadcast_day']) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
