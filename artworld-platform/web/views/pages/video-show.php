<?php
/** @var array $video */
/** @var string|null $embed */
/** @var array $related */
$sourceType = (string) ($video['source_type'] ?? (($video['video_type'] ?? '') === 'youtube' ? 'youtube' : 'file_server'));
$playback = (string) ($video['playback_url'] ?? $video['video_url'] ?? '');
$isYoutube = $sourceType === 'youtube' || ($video['video_type'] ?? '') === 'youtube';
$isHls = ($video['video_type'] ?? '') === 'hls' || str_contains($playback, '.m3u8');
?>
<article class="article shell">
    <header class="article__header">
        <?php if (!empty($video['category']['name'])): ?>
            <span class="eyebrow"><?= e((string) $video['category']['name']) ?></span>
        <?php endif; ?>
        <h1><?= e((string) ($video['title'] ?? '')) ?></h1>
        <div class="article__meta">
            <time datetime="<?= e((string) ($video['published_at'] ?? '')) ?>"><?= e(format_date((string) ($video['published_at'] ?? ''))) ?></time>
            <?php if (!empty($video['duration_seconds'])): ?>
                <span><?= e(gmdate('H:i:s', (int) $video['duration_seconds'])) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="player-frame player-frame--wide" id="vod-player"
         data-source="<?= e($sourceType) ?>"
         data-type="<?= e((string) ($video['video_type'] ?? '')) ?>"
         data-src="<?= e($playback) ?>">
        <?php if ($isYoutube && $embed): ?>
            <iframe src="<?= e($embed) ?>" title="<?= e((string) $video['title']) ?>" allowfullscreen loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        <?php elseif ($playback !== ''): ?>
            <video id="vod-video" controls playsinline preload="metadata"
                   poster="<?= e((string) ($video['thumbnail'] ?? '')) ?>"
                   <?php if (!$isHls): ?>src="<?= e($playback) ?>"<?php endif; ?>></video>
        <?php else: ?>
            <div class="player-frame--empty"><p>Video şu anda kullanılamıyor.</p></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($video['description'])): ?>
        <div class="prose"><p><?= nl2br(e((string) $video['description'])) ?></p></div>
    <?php endif; ?>
</article>

<?php if ($isHls && !$isYoutube && $playback !== ''): ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var video = document.getElementById('vod-video');
  var root = document.getElementById('vod-player');
  if (!video || !root) return;
  var src = root.getAttribute('data-src');
  if (!src) return;
  if (window.Hls && Hls.isSupported()) {
    var hls = new Hls();
    hls.loadSource(src);
    hls.attachMedia(video);
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = src;
  } else {
    video.src = src;
  }
});
</script>
<?php endif; ?>

<?php if (!empty($related)): ?>
<section class="section"><div class="shell"><header class="section-head"><h2>Benzer Videolar</h2></header>
<div class="video-grid">
<?php foreach ($related as $item): ?>
<a class="video-card" href="<?= e(video_url((string) $item['slug'])) ?>">
<div class="video-card__media"><?php if (!empty($item['thumbnail'])): ?><img src="<?= e((string) $item['thumbnail']) ?>" alt="" loading="lazy"><?php endif; ?><span class="play-badge">▶</span></div>
<h3><?= e((string) $item['title']) ?></h3>
</a>
<?php endforeach; ?>
</div></div></section>
<?php endif; ?>
