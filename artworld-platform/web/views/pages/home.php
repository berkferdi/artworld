<?php
/** @var array $breaking */
/** @var array $slider */
/** @var array $rail */
/** @var array|null $featuredMain */
/** @var array $featuredSide */
/** @var array $categoryBlocks */
/** @var array $latest */
/** @var array $videos */
/** @var array $programs */
/** @var array|null $live */
/** @var array $mostRead */
/** @var array $galleries */
/** @var array $authors */
/** @var array $interviews */

$slider = $slider ?? [];
$rail = $rail ?? [];
$featuredMain = $featuredMain ?? null;
$featuredSide = $featuredSide ?? [];
$categoryBlocks = $categoryBlocks ?? [];
$galleries = $galleries ?? [];
$authors = $authors ?? [];
$interviews = $interviews ?? [];
$mostRead = $mostRead ?? [];
$videos = $videos ?? [];
$live = $live ?? null;

$videoMain = $videos[0] ?? null;
$videoSide = array_slice($videos, 1, 3);

$breakingItems = array_slice($breaking ?? [], 0, 10);
?>

<?php if ($breakingItems !== []): ?>
<div class="breaking-ticker" data-breaking-ticker aria-label="Son dakika haberleri">
    <span class="breaking-ticker__badge">Son Dakika</span>
    <div class="breaking-ticker__track">
        <div class="breaking-ticker__inner" data-breaking-inner>
            <?php foreach ($breakingItems as $b): ?>
                <?php
                $href = !empty($b['news_slug'])
                    ? news_url((string) $b['news_slug'])
                    : ((string) ($b['target_url'] ?? '#'));
                $text = (string) ($b['title'] ?? '');
                if ($text === '') {
                    continue;
                }
                ?>
                <a href="<?= e($href) ?>"><?= e($text) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
<div class="breaking-ticker breaking-ticker--empty" aria-hidden="true">
    <span class="breaking-ticker__badge">Son Dakika</span>
    <div class="breaking-ticker__track"></div>
</div>
<?php endif; ?>

<?php if ($slider !== []): ?>
<section class="hero-slider" data-hero-slider aria-roledescription="carousel" aria-label="Manşet haberler">
    <div class="hero-slider__viewport">
        <?php foreach ($slider as $i => $item): ?>
            <a class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
               href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>"
               data-slide="<?= $i ?>"
               <?= $i === 0 ? '' : 'tabindex="-1" aria-hidden="true"' ?>>
                <?php if (!empty($item['cover_image'])): ?>
                    <img src="<?= e((string) $item['cover_image']) ?>"
                         alt=""
                         width="1600" height="686"
                         <?= $i === 0 ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' ?>>
                <?php endif; ?>
                <div class="hero-slide__shade" aria-hidden="true"></div>
                <div class="hero-slide__copy">
                    <?php if (!empty($item['category']['name'])): ?>
                        <span class="eyebrow"><?= e((string) $item['category']['name']) ?></span>
                    <?php endif; ?>
                    <h2><?= e((string) ($item['title'] ?? '')) ?></h2>
                    <?php if (!empty($item['summary'])): ?>
                        <p><?= e(truncate((string) $item['summary'], 160)) ?></p>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        <?php if (count($slider) > 1): ?>
            <div class="hero-slider__nav">
                <button type="button" data-hero-prev aria-label="Önceki haber">‹</button>
                <button type="button" data-hero-next aria-label="Sonraki haber">›</button>
            </div>
        <?php endif; ?>
    </div>
    <?php if (count($slider) > 1): ?>
        <div class="hero-slider__dots" data-hero-dots>
            <?php foreach ($slider as $i => $_): ?>
                <button type="button" data-hero-dot="<?= $i ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Slayt <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($rail !== []): ?>
<section class="headline-rail" aria-label="Manşet bandı">
    <div class="shell">
        <div class="headline-rail__track" data-headline-rail>
            <?php foreach ($rail as $item): ?>
                <a class="headline-card" href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>">
                    <?php if (!empty($item['cover_image'])): ?>
                        <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="96" height="68">
                    <?php else: ?>
                        <div class="media-fallback" style="width:96px;height:68px;border-radius:4px"></div>
                    <?php endif; ?>
                    <span><?= e((string) ($item['title'] ?? '')) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($featuredMain || $featuredSide !== []): ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h2>Öne Çıkan Haberler</h2></header>
        <div class="featured-layout">
            <?php if ($featuredMain): ?>
                <a class="hero-feature" href="<?= e(news_url((string) $featuredMain['slug'])) ?>">
                    <?php if (!empty($featuredMain['cover_image'])): ?>
                        <img src="<?= e((string) $featuredMain['cover_image']) ?>" alt="" loading="lazy" width="1200" height="675">
                    <?php endif; ?>
                    <div class="hero-feature__copy">
                        <?php if (!empty($featuredMain['category']['name'])): ?>
                            <span class="eyebrow"><?= e((string) $featuredMain['category']['name']) ?></span>
                        <?php endif; ?>
                        <h3><?= e((string) $featuredMain['title']) ?></h3>
                        <?php if (!empty($featuredMain['summary'])): ?>
                            <p><?= e(truncate((string) $featuredMain['summary'], 140)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endif; ?>
            <?php if ($featuredSide !== []): ?>
                <div class="featured-side">
                    <?php foreach ($featuredSide as $item): ?>
                        <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php foreach ($categoryBlocks as $block): ?>
    <?php
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
    if ($items === []) {
        continue;
    }
    $main = $items[0];
    $rest = array_slice($items, 1, 4);
    $slug = (string) ($block['slug'] ?? '');
    $name = (string) ($block['name'] ?? '');
    ?>
<section class="section<?= ($slug === 'spor' || $slug === 'kultur-sanat') ? ' section--alt' : '' ?>">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2><?= e($name) ?></h2>
            <a href="<?= e(url('/' . $slug)) ?>">Tümünü Gör</a>
        </header>
        <div class="cat-block">
            <a class="cat-block__main" href="<?= e(news_url((string) ($main['slug'] ?? ''))) ?>">
                <?php if (!empty($main['cover_image'])): ?>
                    <img src="<?= e((string) $main['cover_image']) ?>" alt="" loading="lazy" width="800" height="500">
                <?php endif; ?>
                <div class="copy">
                    <span class="eyebrow"><?= e($name) ?></span>
                    <h3><?= e((string) ($main['title'] ?? '')) ?></h3>
                    <?php if (!empty($main['published_at'])): ?>
                        <time datetime="<?= e((string) $main['published_at']) ?>"><?= e(format_date((string) $main['published_at'])) ?></time>
                    <?php endif; ?>
                </div>
            </a>
            <?php if ($rest !== []): ?>
                <div class="cat-block__list">
                    <?php foreach ($rest as $item): ?>
                        <a class="cat-list-item" href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>">
                            <?php if (!empty($item['cover_image'])): ?>
                                <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="88" height="64">
                            <?php else: ?>
                                <div class="media-fallback" style="width:88px;height:64px;border-radius:4px"></div>
                            <?php endif; ?>
                            <div>
                                <span class="badge"><?= e($name) ?></span>
                                <h4><?= e((string) ($item['title'] ?? '')) ?></h4>
                                <?php if (!empty($item['published_at'])): ?>
                                    <time datetime="<?= e((string) $item['published_at']) ?>"><?= e(format_date((string) $item['published_at'])) ?></time>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="section section--alt">
    <div class="shell home-split">
        <div>
            <header class="section-head section-head--row">
                <h2>Son Eklenenler</h2>
                <a href="<?= e(url('/arsiv')) ?>">Arşiv</a>
            </header>
            <?php if (empty($latest)): ?>
                <p class="empty">Henüz haber yok.</p>
            <?php else: ?>
                <div class="news-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <?php foreach (array_slice($latest, 0, 6) as $item): ?>
                        <?php \Web\Core\View::partial('partials/news-tile', ['item' => $item]); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <aside>
            <header class="section-head"><h2>Çok Okunanlar</h2></header>
            <?php if (empty($mostRead)): ?>
                <p class="empty">Veri yok.</p>
            <?php else: ?>
                <ol class="ranked-list">
                    <?php foreach (array_slice($mostRead, 0, 5) as $i => $item): ?>
                        <li>
                            <span class="rank"><?= $i + 1 ?></span>
                            <?php if (!empty($item['cover_image'])): ?>
                                <a href="<?= e(news_url((string) $item['slug'])) ?>">
                                    <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="72" height="52">
                                </a>
                            <?php else: ?>
                                <div class="media-fallback" style="width:72px;height:52px;border-radius:4px"></div>
                            <?php endif; ?>
                            <a href="<?= e(news_url((string) $item['slug'])) ?>"><?= e((string) $item['title']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php if ($videoMain || $videoSide !== []): ?>
<section class="section">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Videolar</h2>
            <a href="<?= e(url('/videolar')) ?>">Tüm Videolar</a>
        </header>
        <div class="video-spotlight">
            <?php if ($videoMain): ?>
                <a class="video-card" href="<?= e(video_url((string) $videoMain['slug'])) ?>">
                    <div class="video-card__media">
                        <?php if (!empty($videoMain['thumbnail'])): ?>
                            <img src="<?= e((string) $videoMain['thumbnail']) ?>" alt="" loading="lazy" width="800" height="450">
                        <?php endif; ?>
                        <span class="play-badge" aria-hidden="true">▶</span>
                    </div>
                    <h3><?= e((string) $videoMain['title']) ?></h3>
                    <?php if (!empty($videoMain['duration_seconds'])): ?>
                        <span class="meta"><?= e((string) gmdate('H:i:s', (int) $videoMain['duration_seconds'])) ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            <?php if ($videoSide !== []): ?>
                <div class="video-side">
                    <?php foreach ($videoSide as $video): ?>
                        <a class="video-card" href="<?= e(video_url((string) $video['slug'])) ?>">
                            <div class="video-card__media">
                                <?php if (!empty($video['thumbnail'])): ?>
                                    <img src="<?= e((string) $video['thumbnail']) ?>" alt="" loading="lazy" width="400" height="225">
                                <?php endif; ?>
                                <span class="play-badge" aria-hidden="true">▶</span>
                            </div>
                            <h3><?= e((string) $video['title']) ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="live-section" id="canli-yayin">
    <div class="shell">
        <div class="live-section__head">
            <div class="live-section__brand">
                <span class="live-dot" aria-hidden="true" style="background:var(--danger);box-shadow:0 0 0 0 rgba(225,29,46,.55)"></span>
                <h2>Canlı Art World TV</h2>
            </div>
            <p class="live-section__sub"><?= e((string) ($live['title'] ?? 'Art World Canlı Yayın')) ?></p>
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
                    <a class="btn btn--ghost" href="<?= e(url('/canli-yayin')) ?>">Tam Ekran</a>
                </div>
            </div>
        <?php else: ?>
            <div class="player-frame player-frame--home player-frame--empty">
                <p>Canlı yayın şu anda kullanılamıyor.</p>
                <a class="btn btn--ghost" href="<?= e(url('/canli-yayin')) ?>">Canlı sayfasını aç</a>
            </div>
        <?php endif; ?>
    </div>
</section>

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
                        <?php if (!empty($program['summary'])): ?>
                            <p><?= e(truncate((string) $program['summary'], 90)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
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
        <div class="gallery-grid">
            <?php foreach (array_slice($galleries, 0, 4) as $g): ?>
                <a class="news-tile" href="<?= e(gallery_url((string) $g['slug'])) ?>">
                    <div class="news-tile__media">
                        <?php if (!empty($g['cover_image'])): ?>
                            <img src="<?= e((string) $g['cover_image']) ?>" alt="" loading="lazy" width="480" height="300">
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
      p.catch(function () { video.muted = true; video.play().catch(function(){}); });
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
