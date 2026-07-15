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
$programs = $pdo->query("SELECT id, title FROM programs ORDER BY sort_order, title")->fetchAll();
$videoTypes = ['mp4' => 'MP4', 'hls' => 'HLS', 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'external' => 'Harici'];
$filterProgramId = (int) ($_GET['program_id'] ?? 0) ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM program_episodes WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Bölüm silindi.');
        redirect(admin_url('episodes.php', $filterProgramId ? ['program_id' => $filterProgramId] : []));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $programId = (int) ($_POST['program_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $videoType = (string) ($_POST['video_type'] ?? 'mp4');
        if (!isset($videoTypes[$videoType])) {
            $videoType = 'mp4';
        }
        $episodeNumber = (int) ($_POST['episode_number'] ?? 0) ?: null;
        $duration = (int) ($_POST['duration_seconds'] ?? 0) ?: null;
        $status = (string) ($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }
        $publishedAt = null_if_empty((string) ($_POST['published_at'] ?? ''));

        if ($programId <= 0 || $title === '') {
            flash_set('error', 'Program ve başlık zorunludur.');
            redirect(admin_url('episodes.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug !== '' ? Security::slugify($slug) : Security::slugify($title);
        $slug = unique_slug('program_episodes', $slug, $editId, 'slug', 'program_id = ?', [$programId]);

        $thumbnail = null;
        $existingUrl = $videoUrl;
        if ($editId) {
            $cur = $pdo->prepare('SELECT thumbnail, video_url FROM program_episodes WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Bölüm bulunamadı.');
                redirect(admin_url('episodes.php'));
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
                redirect(admin_url('episodes.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $thumbnail = $up['thumbnail'] ?? $up['path'];
        }

        if (!empty($_FILES['video_file']['name'])) {
            $up = $uploader->uploadVideo($_FILES['video_file'], 'videos');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('episodes.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $existingUrl = $up['path'];
            $videoType = 'mp4';
        }

        if ($existingUrl === '') {
            flash_set('error', 'Video URL veya dosya zorunludur.');
            redirect(admin_url('episodes.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
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
                'UPDATE program_episodes SET program_id=?, title=?, slug=?, description=?, thumbnail=?, video_url=?, video_type=?,
                 episode_number=?, duration_seconds=?, published_at=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([
                $programId, $title, $slug, $description, $thumbnail, $existingUrl, $videoType,
                $episodeNumber, $duration, $publishedAt, $status, $editId,
            ]);
            flash_set('success', 'Bölüm güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO program_episodes (program_id, title, slug, description, thumbnail, video_url, video_type, episode_number, duration_seconds, published_at, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([
                $programId, $title, $slug, $description, $thumbnail, $existingUrl, $videoType,
                $episodeNumber, $duration, $publishedAt, $status,
            ]);
            flash_set('success', 'Bölüm oluşturuldu.');
        }
        redirect(admin_url('episodes.php', ['program_id' => $programId]));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'program_id' => $filterProgramId, 'title' => '', 'slug' => '', 'description' => '',
        'thumbnail' => null, 'video_url' => '', 'video_type' => 'mp4', 'episode_number' => null,
        'duration_seconds' => null, 'published_at' => null, 'status' => 'draft',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM program_episodes WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Bölüm bulunamadı.');
            redirect(admin_url('episodes.php'));
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
            <h1><?= $action === 'edit' ? 'Bölüm Düzenle' : 'Yeni Bölüm' ?></h1>
            <a href="<?= e(admin_url('episodes.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Program *</label>
                    <select name="program_id" class="form-select" required>
                        <option value="">Seçin</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (int) $item['program_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bölüm no</label>
                    <input type="number" name="episode_number" class="form-control" value="<?= e((string) ($item['episode_number'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required data-slug-source="#slug" value="<?= e($item['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Video tipi</label>
                    <select name="video_type" class="form-select">
                        <?php foreach ($videoTypes as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['video_type'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Video URL</label>
                    <input type="text" name="video_url" class="form-control" value="<?= e($item['video_url']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dosya yükle</label>
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
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Bölüm Düzenle' : 'Yeni Bölüm', ob_get_clean());
    exit;
}

$sql = 'SELECT e.*, p.title AS program_title FROM program_episodes e INNER JOIN programs p ON p.id = e.program_id WHERE 1=1';
$params = [];
if ($filterProgramId) {
    $sql .= ' AND e.program_id = ?';
    $params[] = $filterProgramId;
}
$sql .= ' ORDER BY e.program_id, e.episode_number IS NULL, e.episode_number, e.created_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Bölümler</h1>
        <a href="<?= e(admin_url('episodes.php', array_filter(['action' => 'create', 'program_id' => $filterProgramId]))) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
            <select name="program_id" class="form-select form-select-sm">
                <option value="">Tüm programlar</option>
                <?php foreach ($programs as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $filterProgramId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary">Filtrele</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Başlık</th><th>Program</th><th>No</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Bölüm yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php if ($row['thumbnail']): ?><img src="<?= e(media_url($row['thumbnail'])) ?>" class="thumb-sm" alt=""><?php else: ?>—<?php endif; ?></td>
                    <td><a href="<?= e(admin_url('episodes.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a></td>
                    <td class="small"><?= e($row['program_title']) ?></td>
                    <td><?= e((string) ($row['episode_number'] ?? '—')) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('episodes.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bölüm silinsin mi?">
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
render('Bölümler', ob_get_clean());
