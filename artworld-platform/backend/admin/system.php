<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Config;

$extensions = get_loaded_extensions();
sort($extensions, SORT_STRING | SORT_FLAG_CASE);

$uploadPath = dirname(__DIR__) . '/' . trim((string) Config::get('UPLOAD_PATH', 'uploads'), '/');
$diskTotal = @disk_total_space($uploadPath) ?: @disk_total_space('/') ?: 0;
$diskFree = @disk_free_space($uploadPath) ?: @disk_free_space('/') ?: 0;
$diskUsed = max(0, $diskTotal - $diskFree);

$formatBytes = static function (float|int $bytes): string {
    $bytes = (float) $bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return number_format($bytes, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
};

$requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'fileinfo', 'gd', 'openssl'];
$extStatus = [];
foreach ($requiredExt as $ext) {
    $extStatus[$ext] = extension_loaded($ext);
}

ob_start();
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>PHP Ortamı</h2></div>
            <table class="table table-sm mb-0">
                <tr><th>PHP sürümü</th><td><?= e(PHP_VERSION) ?></td></tr>
                <tr><th>SAPI</th><td><?= e(PHP_SAPI) ?></td></tr>
                <tr><th>Zaman dilimi</th><td><?= e(date_default_timezone_get()) ?></td></tr>
                <tr><th>APP_ENV</th><td><?= e((string) Config::get('APP_ENV', '')) ?></td></tr>
                <tr><th>APP_DEBUG</th><td><?= Config::isDebug() ? 'true' : 'false' ?></td></tr>
                <tr><th>Sunucu</th><td><?= e((string) ($_SERVER['SERVER_SOFTWARE'] ?? '—')) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Yükleme Limitleri</h2></div>
            <table class="table table-sm mb-0">
                <tr><th>upload_max_filesize</th><td><?= e((string) ini_get('upload_max_filesize')) ?></td></tr>
                <tr><th>post_max_size</th><td><?= e((string) ini_get('post_max_size')) ?></td></tr>
                <tr><th>max_execution_time</th><td><?= e((string) ini_get('max_execution_time')) ?> sn</td></tr>
                <tr><th>memory_limit</th><td><?= e((string) ini_get('memory_limit')) ?></td></tr>
                <tr><th>UPLOAD_MAX_IMAGE_MB</th><td><?= e((string) Config::get('UPLOAD_MAX_IMAGE_MB', '5')) ?></td></tr>
                <tr><th>UPLOAD_MAX_VIDEO_MB</th><td><?= e((string) Config::get('UPLOAD_MAX_VIDEO_MB', '200')) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Disk</h2></div>
            <table class="table table-sm mb-0">
                <tr><th>Upload yolu</th><td><code><?= e($uploadPath) ?></code></td></tr>
                <tr><th>Toplam</th><td><?= e($formatBytes($diskTotal)) ?></td></tr>
                <tr><th>Kullanılan</th><td><?= e($formatBytes($diskUsed)) ?></td></tr>
                <tr><th>Boş</th><td><?= e($formatBytes($diskFree)) ?></td></tr>
            </table>
            <?php if ($diskTotal > 0):
                $pct = round(($diskUsed / $diskTotal) * 100, 1);
                ?>
                <div class="progress mt-3" style="height:8px;">
                    <div class="progress-bar bg-danger" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="small text-muted mt-1">%{<?= e((string) $pct) ?> dolu</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h2>Gerekli Eklentiler</h2></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($extStatus as $ext => $ok): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <?= e($ext) ?>
                        <?php if ($ok): ?>
                            <span class="badge text-bg-success">Yüklü</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger">Eksik</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="panel">
            <div class="panel-header"><h2>Yüklü PHP Eklentileri (<?= count($extensions) ?>)</h2></div>
            <div class="small text-muted" style="column-count:3;column-gap:1.5rem;">
                <?php foreach ($extensions as $ext): ?>
                    <div><?= e($ext) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
render('Sistem Bilgisi', ob_get_clean());
