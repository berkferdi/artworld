<?php /** @var array|null $live */ ?>
<section class="section live-page">
    <div class="shell">
        <header class="section-head">
            <h1>Canlı Yayın</h1>
            <p><?= e((string) ($live['title'] ?? 'Art World TV')) ?></p>
        </header>
        <div class="player-frame player-frame--cinema" id="live-player-root"
             data-src="<?= e((string) ($live['stream_url'] ?? '')) ?>"
             data-type="<?= e((string) ($live['stream_type'] ?? 'hls')) ?>">
            <?php if (!empty($live['stream_url'])): ?>
                <video id="live-video" controls playsinline poster="<?= e((string) ($live['poster_image'] ?? '')) ?>"></video>
            <?php else: ?>
                <div class="player-frame--empty"><p>Canlı yayın şu an aktif değil.</p></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($live['description'])): ?>
            <div class="prose live-desc"><p><?= nl2br(e((string) $live['description'])) ?></p></div>
        <?php endif; ?>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('live-player-root');
  var video = document.getElementById('live-video');
  if (!root || !video) return;
  var src = root.getAttribute('data-src');
  var type = root.getAttribute('data-type') || 'hls';
  if (!src) return;
  if (type === 'hls' && window.Hls && Hls.isSupported()) {
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
