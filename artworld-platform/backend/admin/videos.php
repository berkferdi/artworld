<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Core\Security;
use App\Services\FileServerService;
use App\Services\UploadService;

$pdo = Database::connection();
$action = get_action();
$id = request_id();
$uploader = new UploadService();
$fileServer = new FileServerService();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order, name")->fetchAll();

$hasSourceType = false;
try {
    $hasSourceType = (bool) $pdo->query("SHOW COLUMNS FROM videos LIKE 'source_type'")->fetch();
} catch (Throwable) {
    $hasSourceType = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $delId = (int) ($_POST['id'] ?? 0);
        $row = null;
        if ($delId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
            $stmt->execute([$delId]);
            $row = $stmt->fetch() ?: null;
        }
        if ($row) {
            $sourceType = FileServerService::resolveSourceType(
                $row['source_type'] ?? null,
                $row['video_type'] ?? null,
                $row['video_url'] ?? null
            );
            if ($sourceType === 'file_server') {
                $fileServer->deleteStoredFile($row['file_path'] ?? $row['video_url'] ?? null);
            }
            $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$delId]);
            flash_set('success', 'Video silindi.');
        } else {
            flash_set('error', 'Video bulunamadı.');
        }
        redirect(admin_url('videos.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $sourceType = (string) ($_POST['source_type'] ?? 'file_server');
        if (!in_array($sourceType, ['file_server', 'youtube', 'external'], true)) {
            $sourceType = 'file_server';
        }
        $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
        $externalUrl = trim((string) ($_POST['video_url'] ?? ''));
        $duration = (int) ($_POST['duration_seconds'] ?? 0) ?: null;
        $isFeatured = checkbox('is_featured');
        $status = (string) ($_POST['status'] ?? 'draft');
        $flashNote = null;
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
        $videoUrl = '';
        $videoType = 'mp4';
        $filePath = null;
        $mimeType = null;
        $fileSize = null;
        $oldFilePath = null;

        if ($editId) {
            $cur = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Video bulunamadı.');
                redirect(admin_url('videos.php'));
            }
            $thumbnail = $existing['thumbnail'];
            $videoUrl = (string) $existing['video_url'];
            $videoType = (string) $existing['video_type'];
            $filePath = $existing['file_path'] ?? null;
            $mimeType = $existing['mime_type'] ?? null;
            $fileSize = isset($existing['file_size']) ? (int) $existing['file_size'] : null;
            $oldFilePath = $filePath ?: ((str_starts_with($videoUrl, '/')) ? $videoUrl : null);
        }

        if (!empty($_FILES['thumbnail']['name'])) {
            $up = $uploader->uploadImage($_FILES['thumbnail'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $thumbnail = $up['thumbnail'] ?? $up['path'];
        }

        if ($sourceType === 'youtube') {
            if ($youtubeUrl === '' && $editId) {
                $youtubeUrl = $videoUrl;
            }
            if ($youtubeUrl === '' || !preg_match('~youtu\.?be~i', $youtubeUrl)) {
                flash_set('error', 'Geçerli bir YouTube URL girin.');
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $videoUrl = $youtubeUrl;
            $videoType = 'youtube';
            $filePath = null;
            $mimeType = null;
            $fileSize = null;
        } elseif ($sourceType === 'external') {
            if ($externalUrl === '' && $editId) {
                // keep existing
            } elseif ($externalUrl === '') {
                flash_set('error', 'Harici video URL zorunludur.');
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            } else {
                $videoUrl = $externalUrl;
            }
            $videoType = str_contains($videoUrl, '.m3u8') ? 'hls' : 'external';
            $filePath = null;
        } else {
            // file_server
            if (!empty($_FILES['video_file']['name'])) {
                $up = $fileServer->storeUploadedVideo($_FILES['video_file']);
                if (!($up['success'] ?? false)) {
                    flash_set('error', $up['message'] ?? 'Video yüklenemedi.');
                    redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
                }
                if ($oldFilePath && $oldFilePath !== ($up['file_path'] ?? null)) {
                    $fileServer->deleteStoredFile($oldFilePath);
                }
                $filePath = $up['file_path'] ?? $up['path'];
                $videoUrl = $up['path'] ?? $filePath;
                $videoType = $up['video_type'] ?? 'mp4';
                $mimeType = $up['mime'] ?? 'video/mp4';
                $fileSize = $up['size'] ?? null;
        $flashNote = null;
                if (!empty($up['message']) && str_contains((string) $up['message'], 'başarısız')) {
                    $flashNote = 'Video kaydedildi. Not: ' . $up['message'];
                }
            } elseif ($videoUrl === '') {
                flash_set('error', 'File Server için video dosyası yükleyin.');
                redirect(admin_url('videos.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $videoType = FileServerService::videoTypeForSource('file_server', $videoType);
        }

        if ($videoUrl === '') {
            flash_set('error', 'Video kaynağı zorunludur.');
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

        if ($hasSourceType) {
            if ($editId) {
                $pdo->prepare(
                    'UPDATE videos SET category_id=?, title=?, slug=?, description=?, thumbnail=?, video_url=?, video_type=?,
                     source_type=?, file_path=?, mime_type=?, file_size=?, duration_seconds=?, is_featured=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
                )->execute([
                    $categoryId, $title, $slug, $description, $thumbnail, $videoUrl, $videoType,
                    $sourceType, $filePath, $mimeType, $fileSize, $duration, $isFeatured, $status, $publishedAt, $editId,
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO videos (category_id, title, slug, description, thumbnail, video_url, video_type, source_type, file_path, mime_type, file_size, duration_seconds, is_featured, status, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([
                    $categoryId, $title, $slug, $description, $thumbnail, $videoUrl, $videoType,
                    $sourceType, $filePath, $mimeType, $fileSize, $duration, $isFeatured, $status, $publishedAt,
                ]);
            }
        } else {
            if ($editId) {
                $pdo->prepare(
                    'UPDATE videos SET category_id=?, title=?, slug=?, description=?, thumbnail=?, video_url=?, video_type=?,
                     duration_seconds=?, is_featured=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
                )->execute([
                    $categoryId, $title, $slug, $description, $thumbnail, $videoUrl, $videoType,
                    $duration, $isFeatured, $status, $publishedAt, $editId,
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO videos (category_id, title, slug, description, thumbnail, video_url, video_type, duration_seconds, is_featured, status, published_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([
                    $categoryId, $title, $slug, $description, $thumbnail, $videoUrl, $videoType,
                    $duration, $isFeatured, $status, $publishedAt,
                ]);
            }
        }

        flash_set('success', $flashNote ?? ($editId ? 'Video güncellendi.' : 'Video oluşturuldu.'));
        redirect(admin_url('videos.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'category_id' => null, 'title' => '', 'slug' => '', 'description' => '',
        'thumbnail' => null, 'video_url' => '', 'video_type' => 'mp4', 'source_type' => 'file_server',
        'duration_seconds' => null, 'is_featured' => 0, 'status' => 'draft', 'published_at' => null,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Video bulunamadı.');
            redirect(admin_url('videos.php'));
        }
        if (empty($item['source_type'])) {
            $item['source_type'] = FileServerService::resolveSourceType(null, $item['video_type'] ?? null, $item['video_url'] ?? null);
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

    $probe = $fileServer->probe();
    $sourceType = (string) ($item['source_type'] ?? 'file_server');

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Video Düzenle' : 'Yeni Video' ?></h1>
            <a href="<?= e(admin_url('videos.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <div class="alert <?= $probe['reachable'] ? 'alert-success' : 'alert-warning' ?> py-2">
            Depolama (<?= e($fileServer->mode()) ?>): <?= e($probe['detail']) ?>
            <?php if (!$hasSourceType): ?>
                <div class="small mt-1">Uyarı: `source_type` sütunu yok. Migration 003 henüz uygulanmamış — video_type ile geriye uyumlu kayıt yapılacak.</div>
            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data" id="video-form">
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
                    <label class="form-label">Video kaynağı *</label>
                    <select name="source_type" id="source_type" class="form-select">
                        <option value="file_server" <?= $sourceType === 'file_server' ? 'selected' : '' ?>>Kendi File Serverım</option>
                        <option value="youtube" <?= $sourceType === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                        <option value="external" <?= $sourceType === 'external' ? 'selected' : '' ?>>Harici URL</option>
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

                <div class="col-12 source-panel" data-source="file_server">
                    <label class="form-label">Video dosyası (MP4 önerilir)</label>
                    <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
                    <?php if ($sourceType === 'file_server' && !empty($item['video_url'])): ?>
                        <div class="form-text">Mevcut: <?= e((string) $item['video_url']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 source-panel" data-source="youtube">
                    <label class="form-label">YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= $sourceType === 'youtube' ? e($item['video_url']) : '' ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <div class="col-12 source-panel" data-source="external">
                    <label class="form-label">Harici video URL</label>
                    <input type="text" name="video_url" class="form-control" value="<?= $sourceType === 'external' ? e($item['video_url']) : '' ?>" placeholder="https://...">
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
    <script>
    (function () {
      var select = document.getElementById('source_type');
      function sync() {
        var v = select.value;
        document.querySelectorAll('.source-panel').forEach(function (el) {
          el.style.display = el.getAttribute('data-source') === v ? '' : 'none';
        });
      }
      select.addEventListener('change', sync);
      sync();
    })();
    </script>
    <?php
    render($action === 'edit' ? 'Video Düzenle' : 'Yeni Video', ob_get_clean());
    exit;
}

$filter = (string) ($_GET['source'] ?? 'all');
$sql = 'SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON c.id = v.category_id';
$params = [];
if ($hasSourceType && in_array($filter, ['file_server', 'youtube', 'external'], true)) {
    $sql .= ' WHERE v.source_type = ?';
    $params[] = $filter;
} elseif (!$hasSourceType && $filter === 'youtube') {
    $sql .= " WHERE v.video_type = 'youtube'";
} elseif (!$hasSourceType && $filter === 'file_server') {
    $sql .= " WHERE v.video_type IN ('mp4','hls')";
}
$sql .= ' ORDER BY v.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = ['all' => 0, 'file_server' => 0, 'youtube' => 0, 'external' => 0];
try {
    $counts['all'] = (int) $pdo->query('SELECT COUNT(*) c FROM videos')->fetch()['c'];
    if ($hasSourceType) {
        foreach (['file_server', 'youtube', 'external'] as $k) {
            $st = $pdo->prepare('SELECT COUNT(*) c FROM videos WHERE source_type = ?');
            $st->execute([$k]);
            $counts[$k] = (int) $st->fetch()['c'];
        }
    }
} catch (Throwable) {
}

ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Videolar</h1>
        <a href="<?= e(admin_url('videos.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="mb-3 d-flex flex-wrap gap-2">
        <?php foreach (['all' => 'Tümü', 'file_server' => 'File Server', 'youtube' => 'YouTube', 'external' => 'Harici'] as $k => $label): ?>
            <a class="btn btn-sm <?= $filter === $k ? 'btn-accent' : 'btn-outline-secondary' ?>" href="<?= e(admin_url('videos.php', $k === 'all' ? [] : ['source' => $k])) ?>">
                <?= e($label) ?><?php if ($counts[$k]): ?> (<?= (int) $counts[$k] ?>)<?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Başlık</th><th>Kaynak</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Video yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <?php
                $src = FileServerService::resolveSourceType($row['source_type'] ?? null, $row['video_type'] ?? null, $row['video_url'] ?? null);
                ?>
                <tr>
                    <td><?php if ($row['thumbnail']): ?><img src="<?= e(media_url($row['thumbnail'])) ?>" class="thumb-sm" alt=""><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <a href="<?= e(admin_url('videos.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a>
                        <div class="small text-muted"><?= e($row['category_name'] ?? '') ?></div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= e($src) ?></span></td>
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
