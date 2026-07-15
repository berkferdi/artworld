<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Services\UploadService;

$pdo = Database::connection();
$action = get_action();
$id = request_id();
$uploader = new UploadService();
$streamTypes = ['hls' => 'HLS', 'youtube' => 'YouTube', 'external' => 'Harici'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM live_streams WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Canlı yayın silindi.');
        redirect(admin_url('live.php'));
    }

    if ($postAction === 'activate') {
        $activateId = (int) ($_POST['id'] ?? 0);
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE live_streams SET is_active = 0, updated_at = UTC_TIMESTAMP()');
            $pdo->prepare('UPDATE live_streams SET is_active = 1, updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$activateId]);
            $pdo->commit();
            flash_set('success', 'Canlı yayın aktifleştirildi. Diğer yayınlar pasife alındı.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Aktifleştirme başarısız.');
        }
        redirect(admin_url('live.php'));
    }

    if ($postAction === 'deactivate') {
        $pdo->prepare('UPDATE live_streams SET is_active = 0, updated_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Canlı yayın pasife alındı.');
        redirect(admin_url('live.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $streamUrl = trim((string) ($_POST['stream_url'] ?? ''));
        $streamType = (string) ($_POST['stream_type'] ?? 'hls');
        if (!isset($streamTypes[$streamType])) {
            $streamType = 'hls';
        }
        $isActive = checkbox('is_active');

        if ($title === '' || $streamUrl === '') {
            flash_set('error', 'Başlık ve yayın URL zorunludur.');
            redirect(admin_url('live.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $poster = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT poster_image FROM live_streams WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Kayıt bulunamadı.');
                redirect(admin_url('live.php'));
            }
            $poster = $existing['poster_image'];
        }

        if (!empty($_FILES['poster_image']['name'])) {
            $up = $uploader->uploadImage($_FILES['poster_image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('live.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $poster = $up['path'];
        }

        $pdo->beginTransaction();
        try {
            if ($isActive) {
                $pdo->exec('UPDATE live_streams SET is_active = 0, updated_at = UTC_TIMESTAMP()');
            }

            if ($editId) {
                $pdo->prepare(
                    'UPDATE live_streams SET title=?, description=?, stream_url=?, stream_type=?, poster_image=?, is_active=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
                )->execute([$title, $description, $streamUrl, $streamType, $poster, $isActive, $editId]);
                flash_set('success', 'Canlı yayın güncellendi.');
            } else {
                $pdo->prepare(
                    'INSERT INTO live_streams (title, description, stream_url, stream_type, poster_image, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$title, $description, $streamUrl, $streamType, $poster, $isActive]);
                flash_set('success', 'Canlı yayın oluşturuldu.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Kayıt başarısız.');
        }
        redirect(admin_url('live.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'description' => '', 'stream_url' => '',
        'stream_type' => 'hls', 'poster_image' => null, 'is_active' => 0,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM live_streams WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Kayıt bulunamadı.');
            redirect(admin_url('live.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Canlı Yayın Düzenle' : 'Yeni Canlı Yayın' ?></h1>
            <a href="<?= e(admin_url('live.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($item['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın tipi</label>
                    <select name="stream_type" class="form-select">
                        <?php foreach ($streamTypes as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['stream_type'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Yayın URL *</label>
                    <input type="text" name="stream_url" class="form-control" required value="<?= e($item['stream_url']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Poster</label>
                    <input type="file" name="poster_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['poster_image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['poster_image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= (int) $item['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Aktif yap (diğer yayınlar otomatik pasife alınır)</label>
                    </div>
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Canlı Yayın Düzenle' : 'Yeni Canlı Yayın', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM live_streams ORDER BY is_active DESC, updated_at DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Canlı Yayın</h1>
        <a href="<?= e(admin_url('live.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Başlık</th><th>Tip</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="5" class="empty-state">Canlı yayın kaydı yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php if ($row['poster_image']): ?><img src="<?= e(media_url($row['poster_image'])) ?>" class="thumb-sm" alt=""><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <a href="<?= e(admin_url('live.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a>
                        <div class="small text-muted text-truncate" style="max-width:320px;"><?= e($row['stream_url']) ?></div>
                    </td>
                    <td><span class="badge text-bg-light border"><?= e($row['stream_type']) ?></span></td>
                    <td>
                        <?php if ((int) $row['is_active']): ?>
                            <span class="badge text-bg-danger"><i class="bi bi-broadcast"></i> Aktif</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <?php if ((int) $row['is_active']): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary">Pasifleştir</button>
                                </form>
                            <?php else: ?>
                                <form method="post" data-confirm="Bu yayın aktif yapılsın mı? Diğerleri pasife alınır.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Aktifleştir</button>
                                </form>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('live.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Silinsin mi?">
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
render('Canlı Yayın', ob_get_clean());
