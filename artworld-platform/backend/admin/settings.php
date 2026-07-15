<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Services\SettingsService;
use App\Services\UploadService;

$pdo = Database::connection();
$settingsService = new SettingsService();
$uploader = new UploadService();

$editable = [
    'app_name' => ['label' => 'Uygulama adı', 'type' => 'string'],
    'app_logo' => ['label' => 'Logo yolu', 'type' => 'url'],
    'primary_color' => ['label' => 'Ana renk', 'type' => 'color'],
    'secondary_color' => ['label' => 'İkincil renk', 'type' => 'color'],
    'breaking_news_enabled' => ['label' => 'Son dakika aktif', 'type' => 'boolean'],
    'live_stream_enabled' => ['label' => 'Canlı yayın aktif', 'type' => 'boolean'],
    'maintenance_mode' => ['label' => 'Bakım modu', 'type' => 'boolean'],
    'contact_email' => ['label' => 'İletişim e-posta', 'type' => 'string'],
    'contact_phone' => ['label' => 'İletişim telefon', 'type' => 'string'],
    'website_url' => ['label' => 'Web sitesi', 'type' => 'url'],
    'facebook_url' => ['label' => 'Facebook', 'type' => 'url'],
    'instagram_url' => ['label' => 'Instagram', 'type' => 'url'],
    'youtube_url' => ['label' => 'YouTube', 'type' => 'url'],
    'x_url' => ['label' => 'X (Twitter)', 'type' => 'url'],
    'about_text' => ['label' => 'Hakkında', 'type' => 'string'],
    'max_upload_image_mb' => ['label' => 'Max görsel (MB)', 'type' => 'number'],
    'max_upload_video_mb' => ['label' => 'Max video (MB)', 'type' => 'number'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach ($editable as $key => $meta) {
        $type = $meta['type'];
        if ($type === 'boolean') {
            $settingsService->set($key, isset($_POST[$key]) ? '1' : '0', 'boolean');
            continue;
        }

        if ($key === 'app_logo' && !empty($_FILES['app_logo_file']['name'])) {
            $up = $uploader->uploadImage($_FILES['app_logo_file'], 'images');
            if ($up['success']) {
                $settingsService->set('app_logo', $up['path'], 'url');
            } else {
                flash_set('error', $up['message']);
                redirect(admin_url('settings.php'));
            }
            continue;
        }

        $value = (string) ($_POST[$key] ?? '');
        $settingsService->set($key, $value, $type);
    }

    flash_set('success', 'Ayarlar kaydedildi.');
    redirect(admin_url('settings.php'));
}

$rows = $pdo->query('SELECT setting_key, setting_value, setting_type FROM app_settings')->fetchAll();
$current = [];
foreach ($rows as $row) {
    $current[$row['setting_key']] = $row;
}

ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Uygulama Ayarları</h1>
    </div>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
            <?php foreach ($editable as $key => $meta):
                $val = $current[$key]['setting_value'] ?? '';
                $type = $meta['type'];
                ?>
                <div class="col-md-6">
                    <label class="form-label"><?= e($meta['label']) ?></label>
                    <?php if ($type === 'boolean'): ?>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="<?= e($key) ?>" id="<?= e($key) ?>" value="1"
                                <?= filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= e($key) ?>">Aktif</label>
                        </div>
                    <?php elseif ($key === 'about_text'): ?>
                        <textarea name="<?= e($key) ?>" class="form-control" rows="3"><?= e((string) $val) ?></textarea>
                    <?php elseif ($type === 'color'): ?>
                        <input type="color" name="<?= e($key) ?>" class="form-control form-control-color" value="<?= e((string) ($val ?: '#C8102E')) ?>">
                    <?php elseif ($key === 'app_logo'): ?>
                        <input type="text" name="<?= e($key) ?>" class="form-control mb-2" value="<?= e((string) $val) ?>">
                        <input type="file" name="app_logo_file" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <?php if ($val): ?>
                            <div class="mt-2"><img src="<?= e(media_url((string) $val)) ?>" alt="" style="max-height:48px;"></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="<?= $type === 'number' ? 'number' : 'text' ?>" name="<?= e($key) ?>" class="form-control" value="<?= e((string) $val) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-accent">Kaydet</button>
        </div>
    </form>
</div>
<?php
render('Ayarlar', ob_get_clean());
