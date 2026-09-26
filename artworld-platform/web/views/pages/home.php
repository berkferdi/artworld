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
/** @var array $galleries */
/** @var array $authors */
/** @var array $interviews */

$heroMain = $hero[0] ?? null;
$heroSide = array_slice($hero, 1, 4);
$galleries = $galleries ?? [];
$authors = $authors ?? [];
$interviews = $interviews ?? [];
?>

<section class="live-hero" id="canli-yayin">
    <div class="shell">
        <div class="live-hero__head">
            <div class="live-hero__brand">
                <span class="live-dot" aria-hidden="true"></span>
                <h1>CANLI ART WORLD TV</h1>
            </div>
            <p class="live-hero__sub"><?= e((string) ($live['title'] ?? 'Art World Canlı Yayın')) ?></p>
        </div>

        <?php if (!empty($live['stream_url'])): ?>
            <div class="player-frame player-frame--home"
                 id="home-live-player"
                 data-live-player
                 data-src="<?= e((string) $live['stream_url']) ?>"
                 data-type="<?= e((string) ($live['stream_type'] ?? 'hls')) ?>"
                 data-poster="<?= e((string) ($live['poster_image'] ?? '')) ?>">
                <video id="home-live-video"
                       playsinline
                       muted
                       controls
                       poster="<?= e((string) ($live['poster_image'] ?? '')) ?>"></video>
                <div class="player-overlay" data-unmute-overlay>
                    <button type="button" class="btn btn--primary" data-unmute>Sesi Aç</button>
                    <a class="btn btn--ghost" href="<?= e(url('/canli')) ?>">Tam Ekran</a>
                </div>
            </div>
        <?php else: ?>
            <div class="player-frame player-frame--home player-frame--empty">
                <p>Canlı yayın şu anda kullanılamıyor.</p>
                <a class="btn btn--ghost" href="<?= e(url('/canli')) ?>">Canlı sayfasını aç</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="ticker ticker--below-live" data-ticker>
    <div class="ticker__label">PİYASA</div>
    <div class="ticker__track">
        <div class="ticker__inner" id="market-ticker">
            <span>BIST 100 — güncel veri yakında</span>
            <span>ALTIN — servis entegrasyonu bekleniyor</span>
            <span>USD/TRY — servis entegrasyonu bekleniyor</span>
            <span>EUR/TRY — servis entegrasyonu bekleniyor</span>
        </div>
    </div>
</div>

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

<?php if ($heroMain || $heroSide !== []): ?>
<section class="section featured-news-stage">
    <div class="shell">
        <header class="section-head"><h2>Öne Çıkan Haberler</h2></header>
        <div class="featured-layout">
            <?php if ($heroMain): ?>
                <a class="hero-feature hero-feature--inline" href="<?= e(news_url((string) $heroMain['slug'])) ?>">
                    <?php if (!empty($heroMain['cover_image'])): ?>
                        <img src="<?= e((string) $heroMain['cover_image']) ?>" alt="" fetchpriority="high" width="1200" height="675" loading="eager">
                    <?php endif; ?>
                    <div class="hero-feature__copy">
                        <?php if (!empty($heroMain['category']['name'])): ?>
                            <span class="eyebrow"><?= e((string) $heroMain['category']['name']) ?></span>
                        <?php endif; ?>
                        <h3><?= e((string) $heroMain['title']) ?></h3>
                        <?php if (!empty($heroMain['summary'])): ?>
                            <p><?= e(truncate((string) $heroMain['summary'], 160)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endif; ?>
            <?php if ($heroSide !== []): ?>
                <div class="hero-rail hero-rail--featured">
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
    </div>
</section>
<?php endif; ?>

<?php \Web\Core\View::partial('partials/news-grid', ['title' => 'Son Haberler', 'items' => array_slice($latest, 0, 8)]); ?>

<?php if (!empty($selected)): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head"><h2>Sizin İçin Seçtiklerimiz</h2></header>
        <div class="selected-row">
            <?php foreach (array_slice($selected, 0, 4) as $item): ?>
                <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($programs)): ?>
<section class="section">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Programlar</h2>
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

<?php if (!empty($videos)): ?>
<section class="section section--alt">
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
                    <?php if (!empty($video['duration_seconds'])): ?>
                        <span class="meta"><?= e((string) gmdate('H:i:s', (int) $video['duration_seconds'])) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($mostRead)): ?>
<section class="section">
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

<?php if (!empty($galleries)): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Foto Galeri</h2>
            <a href="<?= e(url('/foto-galeri')) ?>">Tümü</a>
        </header>
        <div class="news-grid">
            <?php foreach (array_slice($galleries, 0, 4) as $g): ?>
                <a class="news-tile" href="<?= e(gallery_url((string) $g['slug'])) ?>">
                    <div class="news-tile__media">
                        <?php if (!empty($g['cover_image'])): ?>
                            <img src="<?= e((string) $g['cover_image']) ?>" alt="" loading="lazy">
                        <?php else: ?><div class="media-fallback"></div><?php endif; ?>
                    </div>
                    <div class="news-tile__body"><h3><?= e((string) $g['title']) ?></h3></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($authors) || !empty($interviews)): ?>
<section class="section">
    <div class="shell dual-cols">
        <?php if (!empty($authors)): ?>
            <div>
                <header class="section-head section-head--row">
                    <h2>Yazarlar</h2>
                    <a href="<?= e(url('/yazarlar')) ?>">Tümü</a>
                </header>
                <ul class="plain-list">
                    <?php foreach (array_slice($authors, 0, 5) as $a): ?>
                        <li><a href="<?= e(author_url((string) $a['slug'])) ?>"><?= e((string) $a['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($interviews)): ?>
            <div>
                <header class="section-head section-head--row">
                    <h2>Röportajlar</h2>
                    <a href="<?= e(url('/roportajlar')) ?>">Tümü</a>
                </header>
                <ul class="plain-list">
                    <?php foreach (array_slice($interviews, 0, 5) as $iv): ?>
                        <li><a href="<?= e(interview_url((string) $iv['slug'])) ?>"><?= e((string) $iv['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="section section--alt">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Arşiv</h2>
            <a href="<?= e(url('/arsiv')) ?>">Tüm arşiv</a>
        </header>
        <p class="empty">Tarih ve kategoriye göre geçmiş içeriklere arşivden ulaşabilirsiniz.</p>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('home-live-player');
  var video = document.getElementById('home-live-video');
  if (!root || !video) return;
  var src = root.getAttribute('data-src');
  var type = root.getAttribute('data-type') || 'hls';
  if (!src) return;

  function tryPlay() {
    var p = video.play();
    if (p && typeof p.catch === 'function') {
      p.catch(function () { /* autoplay blocked — muted retry */ video.muted = true; video.play().catch(function(){}); });
    }
  }

  if (type === 'hls' && window.Hls && Hls.isSupported()) {
    var hls = new Hls({ enableWorker: true });
    hls.loadSource(src);
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, tryPlay);
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = src;
    video.addEventListener('loadedmetadata', tryPlay);
  } else {
    video.src = src;
    tryPlay();
  }

  var unmuteBtn = root.querySelector('[data-unmute]');
  var overlay = root.querySelector('[data-unmute-overlay]');
  if (unmuteBtn) {
    unmuteBtn.addEventListener('click', function () {
      video.muted = false;
      video.volume = 1;
      video.play().catch(function () {});
      if (overlay) overlay.classList.add('is-hidden');
    });
  }
  video.addEventListener('volumechange', function () {
    if (!video.muted && overlay) overlay.classList.add('is-hidden');
  });
});
</script>
