<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;

$pdo = Database::connection();

$newsTotal = (int) $pdo->query('SELECT COUNT(*) AS c FROM news')->fetch()['c'];
$newsPublished = (int) $pdo->query("SELECT COUNT(*) AS c FROM news WHERE status = 'published'")->fetch()['c'];
$videosCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM videos')->fetch()['c'];
$programsCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM programs')->fetch()['c'];
$episodesCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM program_episodes')->fetch()['c'];

$live = $pdo->query('SELECT id, title, stream_type, is_active FROM live_streams WHERE is_active = 1 LIMIT 1')->fetch() ?: null;

$newsViews7 = (int) $pdo->query(
    'SELECT COUNT(*) AS c FROM news_views WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY)'
)->fetch()['c'];
$videoViews7 = (int) $pdo->query(
    'SELECT COUNT(*) AS c FROM video_views WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY)'
)->fetch()['c'];

$chartLabels = [];
$newsChart = [];
$videoChart = [];
for ($i = 6; $i >= 0; $i--) {
    $day = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("-{$i} days")->format('Y-m-d');
    $chartLabels[] = (new DateTimeImmutable($day))->format('d.m');

    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM news_views WHERE DATE(created_at) = ?');
    $stmt->execute([$day]);
    $newsChart[] = (int) $stmt->fetch()['c'];

    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM video_views WHERE DATE(created_at) = ?');
    $stmt->execute([$day]);
    $videoChart[] = (int) $stmt->fetch()['c'];
}

$recentNews = $pdo->query(
    'SELECT id, title, status, published_at, created_at FROM news ORDER BY created_at DESC LIMIT 5'
)->fetchAll();
$recentVideos = $pdo->query(
    'SELECT id, title, status, published_at, created_at FROM videos ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

ob_start();
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Haberler</div>
            <div class="stat-value"><?= $newsTotal ?></div>
            <div class="stat-sub"><?= $newsPublished ?> yayında</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Videolar</div>
            <div class="stat-value"><?= $videosCount ?></div>
            <div class="stat-sub"><?= $programsCount ?> program · <?= $episodesCount ?> bölüm</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">7 Gün Görüntülenme</div>
            <div class="stat-value"><?= $newsViews7 + $videoViews7 ?></div>
            <div class="stat-sub"><?= $newsViews7 ?> haber · <?= $videoViews7 ?> video</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Canlı Yayın</div>
            <div class="stat-value" style="font-size:1.1rem;">
                <?php if ($live): ?>
                    <span class="text-danger"><i class="bi bi-broadcast"></i> Aktif</span>
                <?php else: ?>
                    <span class="text-muted">Kapalı</span>
                <?php endif; ?>
            </div>
            <div class="stat-sub"><?= $live ? e($live['title']) : 'Aktif yayın yok' ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <h2>Son 7 Gün İzlenme</h2>
            </div>
            <div class="chart-wrap">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <h2>Hızlı İşlemler</h2>
            </div>
            <div class="d-grid gap-2">
                <a class="btn btn-accent" href="<?= e(admin_url('news.php', ['action' => 'create'])) ?>"><i class="bi bi-plus-lg"></i> Yeni Haber</a>
                <a class="btn btn-outline-secondary" href="<?= e(admin_url('videos.php', ['action' => 'create'])) ?>"><i class="bi bi-plus-lg"></i> Yeni Video</a>
                <a class="btn btn-outline-secondary" href="<?= e(admin_url('live.php')) ?>"><i class="bi bi-broadcast"></i> Canlı Yayın Yönetimi</a>
                <a class="btn btn-outline-secondary" href="<?= e(admin_url('notifications.php', ['action' => 'create'])) ?>"><i class="bi bi-bell"></i> Bildirim Gönder</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header">
                <h2>Son Haberler</h2>
                <a href="<?= e(admin_url('news.php')) ?>" class="small">Tümü</a>
            </div>
            <?php if ($recentNews === []): ?>
                <div class="empty-state">Henüz haber yok.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentNews as $n): ?>
                            <tr>
                                <td><a href="<?= e(admin_url('news.php', ['action' => 'edit', 'id' => $n['id']])) ?>"><?= e($n['title']) ?></a></td>
                                <td><?= status_badge($n['status']) ?></td>
                                <td class="text-muted small"><?= format_dt($n['published_at'] ?: $n['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header">
                <h2>Son Videolar</h2>
                <a href="<?= e(admin_url('videos.php')) ?>" class="small">Tümü</a>
            </div>
            <?php if ($recentVideos === []): ?>
                <div class="empty-state">Henüz video yok.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentVideos as $v): ?>
                            <tr>
                                <td><a href="<?= e(admin_url('videos.php', ['action' => 'edit', 'id' => $v['id']])) ?>"><?= e($v['title']) ?></a></td>
                                <td><?= status_badge($v['status']) ?></td>
                                <td class="text-muted small"><?= format_dt($v['published_at'] ?: $v['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var ctx = document.getElementById('viewsChart');
  if (!ctx || typeof Chart === 'undefined') return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [
        {
          label: 'Haber',
          data: <?= json_encode($newsChart) ?>,
          borderColor: '#C8102E',
          backgroundColor: 'rgba(200,16,46,0.12)',
          tension: 0.3,
          fill: true
        },
        {
          label: 'Video',
          data: <?= json_encode($videoChart) ?>,
          borderColor: '#1f2937',
          backgroundColor: 'rgba(31,41,55,0.08)',
          tension: 0.3,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
})();
</script>
<?php
$content = ob_get_clean();
render('Panel', $content);
