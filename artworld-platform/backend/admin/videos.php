<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Core\Security;
use App\Services\UploadService;

$pdo = Database::connection();
$action = get_action();
$id = request_id();
$uploader = new UploadService();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order, name")->fetchAll();
$videoTypes = ['mp4' => 'MP4', 'hls' => 'HLS', 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'external' => 'Harici'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Video silindi.');
        redirect(admin_url('videos.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $videoType = (string) ($_POST['video_type'] ?? 'mp4');
        if (!isset($videoTypes[$videoType])) {
            $videoType = 'mp4';
        }
        $duration = (int) ($_POST['duration_seconds'] ?? 0) ?: null;
        $isFeatured = checkbox('is_featured');
        $status = (string) ($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }
        $publishedAt = null_if_empty((string) ($_POST['published_at'] ?? ''));

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug !== '' ? Security::slugify($slug) : Security::slugify($title);
        $slug = unique_slug('videos', $slug, $editId);

        $thumbnail = null;
        $existingUrl = $videoUrl;
        if ($editId) {
            $cur = $pdo->prepare('SELECT thumbnail, video_url FROM videos WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Video bulunamadı.');
                redirect(admin_url('videos.php'));
            }
            $thumbnail = $existing['thumbnail'];
            if ($videoUrl === '') {
                $existingUrl = $existing['video_url'];
            }
        }

        if (!empty($_FILES['thumbnail']['name'])) {
            $up = $uploader->uploadImage($_FILES['thumbnail'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $thumbnail = $up['thumbnail'] ?? $up['path'];
        }

        if (!empty($_FILES['video_file']['name'])) {
            $up = $uploader->uploadVideo($_FILES['video_file'], 'videos');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $existingUrl = $up['path'];
            $videoType = 'mp4';
        }

        if ($existingUrl === '') {
            flash_set('error', 'Video URL veya dosya zorunludur.');
            redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if ($status === 'published' && !$publishedAt) {
            $publishedAt = gmdate('Y-m-d H:i:s');
        } elseif ($publishedAt) {
            try {
                $publishedAt = (new DateTimeImmutable($publishedAt))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                $publishedAt = gmdate('Y-m-d H:i:s');
            }
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE videos SET category_id=?, title=?, slug=?, description=?, thumbnail=?, video_url=?, video_type=?,
                 duration_seconds=?, is_featured=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([
                $categoryId, $title, $slug, $description, $thumbnail, $existingUrl, $videoType,
                $duration, $isFeatured, $status, $publishedAt, $editId,
            ]);
            flash_set('success', 'Video güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO videos (category_id, title, slug, description, thumbnail, video_url, video_type, duration_seconds, is_featured, status, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([
                $categoryId, $title, $slug, $description, $thumbnail, $existingUrl, $videoType,
                $duration, $isFeatured, $status, $publishedAt,
            ]);
            flash_set('success', 'Video oluşturuldu.');
        }
        redirect(admin_url('videos.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'category_id' => null, 'title' => '', 'slug' => '', 'description' => '',
        'thumbnail' => null, 'video_url' => '', 'video_type' => 'mp4', 'duration_seconds' => null,
        'is_featured' => 0, 'status' => 'draft', 'published_at' => null,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Video bulunamadı.');
            redirect(admin_url('videos.php'));
        }
    }

    $publishedLocal = '';
    if (!empty($item['published_at'])) {
        try {
            $publishedLocal = (new DateTimeImmutable($item['published_at'], new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Istanbul'))
                ->format('Y-m-d\TH:i');
        } catch (Throwable) {
            $publishedLocal = '';
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Video Düzenle' : 'Yeni Video' ?></h1>
            <a href="<?= e(admin_url('videos.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-lg-8">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required data-slug-source="#slug" value="<?= e($item['title']) ?>">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">—</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $item['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Video tipi</label>
                    <select name="video_type" class="form-select">
                        <?php foreach ($videoTypes as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['video_type'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Video URL</label>
                    <input type="text" name="video_url" class="form-control" value="<?= e($item['video_url']) ?>" placeholder="https://... veya /videos/...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dosya yükle (mp4/webm)</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Küçük resim</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['thumbnail'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['thumbnail'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Süre (sn)</label>
                    <input type="number" name="duration_seconds" class="form-control" value="<?= e((string) ($item['duration_seconds'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın tarihi</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= e($publishedLocal) ?>">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= (int) $item['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Öne çıkan</label>
                    </div>
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Video Düzenle' : 'Yeni Video', ob_get_clean());
    exit;
}

$rows = $pdo->query(
    'SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON c.id = v.category_id ORDER BY v.created_at DESC LIMIT 200'
)->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Videolar</h1>
        <a href="<?= e(admin_url('videos.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Başlık</th><th>Tip</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Video yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php if ($row['thumbnail']): ?><img src="<?= e(media_url($row['thumbnail'])) ?>" class="thumb-sm" alt=""><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <a href="<?= e(admin_url('videos.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a>
                        <div class="small text-muted"><?= e($row['category_name'] ?? '') ?></div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= e($row['video_type']) ?></span></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['published_at'] ?: $row['created_at']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('videos.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Video silinsin mi?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render('Videolar', ob_get_clean());
