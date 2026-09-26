<?php
/** @var array $breaking */
/** @var array|null $live */
/** @var array $slider1 */
/** @var array $slider2 */
/** @var array|null $featuredMain */
/** @var array $featuredSide */
/** @var array $manset */
/** @var array $primaryBlocks */
/** @var array $secondaryBlocks */
/** @var array $mostRead */
/** @var array $selected */
/** @var array $latest */
/** @var array $videos */

$slider1 = $slider1 ?? [];
$slider2 = $slider2 ?? [];
$featuredMain = $featuredMain ?? null;
$featuredSide = $featuredSide ?? [];
$manset = $manset ?? [];
$primaryBlocks = $primaryBlocks ?? [];
$secondaryBlocks = $secondaryBlocks ?? [];
$mostRead = $mostRead ?? [];
$selected = $selected ?? [];
$latest = $latest ?? [];
$videos = $videos ?? [];
$breakingItems = array_slice($breaking ?? [], 0, 12);

$renderCarousel = static function (array $slides, string $id): void {
    if ($slides === []) {
        return;
    }
    ?>
    <div class="news-carousel" data-news-carousel id="<?= e($id) ?>" aria-roledescription="carousel">
        <div class="news-carousel__viewport">
            <?php foreach ($slides as $i => $slide): ?>
                <a class="news-carousel__slide<?= $i === 0 ? ' is-active' : '' ?>"
                   href="<?= e((string) ($slide['href'] ?? '#')) ?>"
                   data-cslide="<?= $i ?>"
                   <?= $i === 0 ? '' : 'tabindex="-1" aria-hidden="true"' ?>>
                    <img src="<?= e((string) $slide['image']) ?>"
                         alt=""
                         width="1600" height="640"
                         <?= $i === 0 ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' ?>>
                    <div class="news-carousel__shade" aria-hidden="true"></div>
                    <div class="news-carousel__copy">
                        <?php if (!empty($slide['category'])): ?>
                            <span class="eyebrow"><?= e((string) $slide['category']) ?></span>
                        <?php endif; ?>
                        <h3><?= e((string) ($slide['title'] ?? '')) ?></h3>
                        <?php if (!empty($slide['summary'])): ?>
                            <p><?= e(truncate((string) $slide['summary'], 140)) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (count($slides) > 1): ?>
                <div class="news-carousel__nav">
                    <button type="button" data-c-prev aria-label="Önceki">‹</button>
                    <button type="button" data-c-next aria-label="Sonraki">›</button>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($slides) > 1): ?>
            <div class="news-carousel__dots" data-c-dots>
                <?php foreach ($slides as $i => $_): ?>
                    <button type="button" data-c-dot="<?= $i ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" aria-label="Slayt <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
};

$renderCatColumn = static function (array $block): void {
    $items = is_array($block['items'] ?? null) ? $block['items'] : [];
    $slug = (string) ($block['slug'] ?? '');
    $name = (string) ($block['name'] ?? '');
    $main = $items[0] ?? null;
    $rest = array_slice($items, 1, 4);
    ?>
    <div class="cat-col">
        <header class="section-head section-head--row">
            <h2><?= e($name) ?></h2>
            <a href="<?= e(url('/' . $slug)) ?>">Tümünü Gör</a>
        </header>
        <?php if (!$main): ?>
            <p class="empty">Bu kategoride henüz haber yok.</p>
        <?php else: ?>
            <a class="cat-col__main" href="<?= e(news_url((string) ($main['slug'] ?? ''))) ?>">
                <div class="cat-col__media">
                    <?php if (!empty($main['cover_image'])): ?>
                        <img src="<?= e((string) $main['cover_image']) ?>" alt="" loading="lazy" width="640" height="400">
                    <?php else: ?>
                        <div class="media-fallback"></div>
                    <?php endif; ?>
                </div>
                <div class="cat-col__body">
                    <span class="badge"><?= e($name) ?></span>
                    <h3><?= e((string) ($main['title'] ?? '')) ?></h3>
                    <?php if (!empty($main['published_at'])): ?>
                        <time datetime="<?= e((string) $main['published_at']) ?>"><?= e(format_date((string) $main['published_at'])) ?></time>
                    <?php endif; ?>
                </div>
            </a>
            <div class="cat-col__list">
                <?php foreach ($rest as $item): ?>
                    <a class="cat-list-item" href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>">
                        <?php if (!empty($item['cover_image'])): ?>
                            <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="88" height="64">
                        <?php else: ?>
                            <div class="media-fallback" style="width:88px;height:64px;border-radius:4px"></div>
                        <?php endif; ?>
                        <div>
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
    <?php
};

$renderPickCol = static function (string $title, array $items): void {
    $items = array_slice($items, 0, 7);
    $main = $items[0] ?? null;
    $rest = array_slice($items, 1, 6);
    ?>
    <div class="pick-col">
        <header class="section-head"><h2><?= e($title) ?></h2></header>
        <?php if ($main): ?>
            <a class="pick-col__main" href="<?= e(news_url((string) ($main['slug'] ?? ''))) ?>">
                <div class="pick-col__media">
                    <?php if (!empty($main['cover_image'])): ?>
                        <img src="<?= e((string) $main['cover_image']) ?>" alt="" loading="lazy" width="640" height="360">
                    <?php else: ?>
                        <div class="media-fallback"></div>
                    <?php endif; ?>
                </div>
                <h3><?= e((string) ($main['title'] ?? '')) ?></h3>
                <?php if (!empty($main['published_at'])): ?>
                    <time datetime="<?= e((string) $main['published_at']) ?>"><?= e(format_date((string) $main['published_at'])) ?></time>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <ul class="pick-col__list">
            <?php foreach ($rest as $item): ?>
                <li>
                    <a href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>">
                        <?php if (!empty($item['cover_image'])): ?>
                            <img src="<?= e((string) $item['cover_image']) ?>" alt="" loading="lazy" width="72" height="52">
                        <?php endif; ?>
                        <span>
                            <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
                            <?php if (!empty($item['published_at'])): ?>
                                <time datetime="<?= e((string) $item['published_at']) ?>"><?= e(format_date((string) $item['published_at'])) ?></time>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
};
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
<?php endif; ?>

<section class="live-section live-section--top" id="canli-yayin">
    <div class="shell">
        <div class="live-section__head">
            <div class="live-section__brand">
                <span class="live-dot" aria-hidden="true" style="background:var(--danger);box-shadow:0 0 0 0 rgba(225,29,46,.55)"></span>
                <h2>Canlı Art World TV</h2>
            </div>
            <p class="live-section__sub"><?= e((string) ($live['title'] ?? 'Art World Canlı Yayın')) ?></p>
        </div>
        <?php if (!empty($live['stream_url'])): ?>
            <div class="player-frame player-frame--home player-frame--compact"
                 id="home-live-player"
                 data-live-player
                 data-src="<?= e((string) $live['stream_url']) ?>"
                 data-type="<?= e((string) ($live['stream_type'] ?? 'hls')) ?>"
                 data-poster="<?= e((string) ($live['poster_image'] ?? '')) ?>">
                <video id="home-live-video" playsinline muted controls poster="<?= e((string) ($live['poster_image'] ?? '')) ?>"></video>
                <div class="player-overlay" data-unmute-overlay>
                    <button type="button" class="btn btn--primary" data-unmute>Sesi Aç</button>
                    <a class="btn btn--ghost" href="<?= e(url('/canli-yayin')) ?>">Tam Ekran</a>
                </div>
            </div>
        <?php else: ?>
            <div class="player-frame player-frame--home player-frame--compact player-frame--empty">
                <p>Canlı yayın şu anda kullanılamıyor.</p>
                <a class="btn btn--ghost" href="<?= e(url('/canli-yayin')) ?>">Canlı sayfasını aç</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--carousels">
    <div class="shell">
        <?php $renderCarousel($slider1, 'carousel-1'); ?>
        <?php $renderCarousel($slider2, 'carousel-2'); ?>
    </div>
</section>

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

<?php if ($manset !== []): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head"><h2>Manşet Haberler</h2></header>
        <div class="manset" data-manset>
            <a class="manset__stage" data-manset-stage href="<?= e(news_url((string) ($manset[0]['slug'] ?? ''))) ?>">
                <?php if (!empty($manset[0]['cover_image'])): ?>
                    <img data-manset-img src="<?= e((string) $manset[0]['cover_image']) ?>" alt="" width="1100" height="620" loading="eager">
                <?php endif; ?>
                <div class="manset__copy">
                    <span class="eyebrow" data-manset-cat><?= e((string) ($manset[0]['category']['name'] ?? '')) ?></span>
                    <h3 data-manset-title><?= e((string) ($manset[0]['title'] ?? '')) ?></h3>
                    <p data-manset-summary><?= e(truncate((string) ($manset[0]['summary'] ?? ''), 160)) ?></p>
                </div>
            </a>
            <div class="manset__side">
                <ol class="manset__list" data-manset-list>
                    <?php foreach (array_slice($manset, 0, 8) as $i => $item): ?>
                        <li>
                            <button type="button"
                                    class="manset__item<?= $i === 0 ? ' is-active' : '' ?>"
                                    data-manset-item
                                    data-index="<?= $i ?>"
                                    data-href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>"
                                    data-img="<?= e((string) ($item['cover_image'] ?? '')) ?>"
                                    data-title="<?= e((string) ($item['title'] ?? '')) ?>"
                                    data-summary="<?= e(truncate((string) ($item['summary'] ?? ''), 160)) ?>"
                                    data-cat="<?= e((string) ($item['category']['name'] ?? '')) ?>">
                                <span class="manset__num"><?= $i + 1 ?></span>
                                <span class="manset__item-title"><?= e((string) ($item['title'] ?? '')) ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <div class="manset__pager" data-manset-pager aria-label="Manşet numaraları">
            <?php foreach ($manset as $i => $item): ?>
                <button type="button"
                        class="<?= $i === 0 ? 'is-active' : '' ?>"
                        data-manset-page="<?= $i ?>"
                        data-href="<?= e(news_url((string) ($item['slug'] ?? ''))) ?>"
                        data-img="<?= e((string) ($item['cover_image'] ?? '')) ?>"
                        data-title="<?= e((string) ($item['title'] ?? '')) ?>"
                        data-summary="<?= e(truncate((string) ($item['summary'] ?? ''), 160)) ?>"
                        data-cat="<?= e((string) ($item['category']['name'] ?? '')) ?>"><?= $i + 1 ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="shell">
        <div class="cat-trio">
            <?php foreach ($primaryBlocks as $block): ?>
                <?php $renderCatColumn($block); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt">
    <div class="shell">
        <div class="picks-trio">
            <?php $renderPickCol('Çok Okunanlar', $mostRead); ?>
            <?php $renderPickCol('Sizin İçin Seçtiklerimiz', $selected); ?>
            <?php $renderPickCol('Son Eklenenler', $latest); ?>
        </div>
    </div>
</section>

<?php if ($secondaryBlocks !== []): ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h2>Diğer Kategoriler</h2></header>
        <div class="cat-grid">
            <?php foreach ($secondaryBlocks as $block): ?>
                <?php $renderCatColumn($block); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($videos !== []): ?>
<section class="section section--alt">
    <div class="shell">
        <header class="section-head section-head--row">
            <h2>Videolar</h2>
            <a href="<?= e(url('/videolar')) ?>">Tüm Videolar</a>
        </header>
        <?php
        $videoMain = $videos[0] ?? null;
        $videoSide = array_slice($videos, 1, 3);
        ?>
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
