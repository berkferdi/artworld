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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Reklam silindi.');
        redirect(admin_url('ads.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $name = trim((string) ($_POST['name'] ?? ''));
        $position = trim((string) ($_POST['position'] ?? ''));
        $type = (string) ($_POST['type'] ?? 'image');
        if (!in_array($type, ['image', 'html', 'script'], true)) {
            $type = 'image';
        }
        $content = null_if_empty((string) ($_POST['content'] ?? ''));
        $link = null_if_empty((string) ($_POST['link'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        $toUtc = static function (?string $v): ?string {
            if ($v === null || $v === '') {
                return null;
            }
            try {
                return (new DateTimeImmutable($v))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return null;
            }
        };
        $startAt = $toUtc(null_if_empty((string) ($_POST['start_at'] ?? '')));
        $endAt = $toUtc(null_if_empty((string) ($_POST['end_at'] ?? '')));

        if ($name === '' || $position === '') {
            flash_set('error', 'Ad ve pozisyon zorunludur.');
            redirect(admin_url('ads.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $image = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT image FROM ads WHERE id = ?');
            $cur->execute([$editId]);
            $row = $cur->fetch();
            if (!$row) {
                flash_set('error', 'Reklam bulunamadı.');
                redirect(admin_url('ads.php'));
            }
            $image = $row['image'];
        }

        if (!empty($_FILES['image']['name'])) {
            $up = $uploader->uploadImage($_FILES['image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('ads.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $image = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE ads SET name=?, position=?, type=?, content=?, image=?, link=?, start_at=?, end_at=?, status=?, sort_order=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$name, $position, $type, $content, $image, $link, $startAt, $endAt, $status, $sortOrder, $editId]);
            flash_set('success', 'Reklam güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO ads (name, position, type, content, image, link, start_at, end_at, status, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$name, $position, $type, $content, $image, $link, $startAt, $endAt, $status, $sortOrder]);
            flash_set('success', 'Reklam oluşturuldu.');
        }
        redirect(admin_url('ads.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'name' => '', 'position' => 'sidebar', 'type' => 'image', 'content' => '',
        'image' => null, 'link' => '', 'start_at' => null, 'end_at' => null, 'status' => 'active', 'sort_order' => 0,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM ads WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Reklam bulunamadı.');
            redirect(admin_url('ads.php'));
        }
    }

    $fmtLocal = static function (?string $dt): string {
        if ($dt === null || $dt === '') {
            return '';
        }
        try {
            return (new DateTimeImmutable($dt, new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Istanbul'))
                ->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return '';
        }
    };

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Reklam Düzenle' : 'Yeni Reklam' ?></h1>
            <a href="<?= e(admin_url('ads.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad *</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($item['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pozisyon *</label>
                    <input type="text" name="position" class="form-control" required value="<?= e($item['position']) ?>" placeholder="sidebar, header, footer...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tip</label>
                    <select name="type" class="form-select">
                        <option value="image" <?= $item['type'] === 'image' ? 'selected' : '' ?>>Görsel</option>
                        <option value="html" <?= $item['type'] === 'html' ? 'selected' : '' ?>>HTML</option>
                        <option value="script" <?= $item['type'] === 'script' ? 'selected' : '' ?>>Script</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) $item['sort_order'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Link</label>
                    <input type="text" name="link" class="form-control" value="<?= e($item['link'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Başlangıç</label>
                    <input type="datetime-local" name="start_at" class="form-control" value="<?= e($fmtLocal($item['start_at'] ?? null)) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bitiş</label>
                    <input type="datetime-local" name="end_at" class="form-control" value="<?= e($fmtLocal($item['end_at'] ?? null)) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">İçerik (HTML/Script)</label>
                    <textarea name="content" class="form-control" rows="4"><?= e($item['content'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Görsel</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Reklam Düzenle' : 'Yeni Reklam', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM ads ORDER BY position ASC, sort_order ASC, id DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Reklamlar</h1>
        <a href="<?= e(admin_url('ads.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Görsel</th><th>Ad</th><th>Pozisyon</th><th>Tip</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="6" class="empty-state">Reklam bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="<?= e(media_url($row['image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= e($row['name']) ?></td>
                    <td><code><?= e($row['position']) ?></code></td>
                    <td><?= e($row['type']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('ads.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu reklamı silmek istediğinize emin misiniz?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
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
render('Reklamlar', ob_get_clean());
