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
        $pdo->prepare('DELETE FROM banners WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Banner silindi.');
        redirect(admin_url('banners.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $targetType = (string) ($_POST['target_type'] ?? 'none');
        if (!in_array($targetType, ['news', 'video', 'program', 'url', 'none'], true)) {
            $targetType = 'none';
        }
        $targetId = (int) ($_POST['target_id'] ?? 0) ?: null;
        $targetUrl = null_if_empty((string) ($_POST['target_url'] ?? ''));
        $position = trim((string) ($_POST['position'] ?? 'home_hero')) ?: 'home_hero';
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $startsAt = null_if_empty((string) ($_POST['starts_at'] ?? ''));
        $expiresAt = null_if_empty((string) ($_POST['expires_at'] ?? ''));

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('banners.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $toUtc = static function (?string $v): ?string {
            if (!$v) {
                return null;
            }
            try {
                return (new DateTimeImmutable($v))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return null;
            }
        };
        $startsAt = $toUtc($startsAt);
        $expiresAt = $toUtc($expiresAt);

        $image = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT image FROM banners WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Banner bulunamadı.');
                redirect(admin_url('banners.php'));
            }
            $image = $existing['image'];
        }

        if (!empty($_FILES['image']['name'])) {
            $up = $uploader->uploadImage($_FILES['image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('banners.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $image = $up['path'];
        }

        if ($image === null || $image === '') {
            flash_set('error', 'Banner görseli zorunludur.');
            redirect(admin_url('banners.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE banners SET title=?, image=?, target_type=?, target_id=?, target_url=?, position=?, sort_order=?, status=?, starts_at=?, expires_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $image, $targetType, $targetId, $targetUrl, $position, $sortOrder, $status, $startsAt, $expiresAt, $editId]);
            flash_set('success', 'Banner güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO banners (title, image, target_type, target_id, target_url, position, sort_order, status, starts_at, expires_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$title, $image, $targetType, $targetId, $targetUrl, $position, $sortOrder, $status, $startsAt, $expiresAt]);
            flash_set('success', 'Banner oluşturuldu.');
        }
        redirect(admin_url('banners.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'image' => null, 'target_type' => 'none', 'target_id' => null,
        'target_url' => '', 'position' => 'home_hero', 'sort_order' => 0, 'status' => 'active',
        'starts_at' => null, 'expires_at' => null,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM banners WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Banner bulunamadı.');
            redirect(admin_url('banners.php'));
        }
    }

    $fmtLocal = static function (?string $v): string {
        if (!$v) {
            return '';
        }
        try {
            return (new DateTimeImmutable($v, new DateTimeZone('UTC')))
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
            <h1><?= $action === 'edit' ? 'Banner Düzenle' : 'Yeni Banner' ?></h1>
            <a href="<?= e(admin_url('banners.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
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
                    <label class="form-label">Pozisyon</label>
                    <input type="text" name="position" class="form-control" value="<?= e($item['position']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Görsel <?= $item['id'] ? '' : '*' ?></label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $item['id'] ? '' : 'required' ?>>
                    <?php if (!empty($item['image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['image'])) ?>" alt="" style="max-width:220px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hedef tipi</label>
                    <select name="target_type" class="form-select">
                        <?php foreach (['none' => 'Yok', 'news' => 'Haber', 'video' => 'Video', 'program' => 'Program', 'url' => 'URL'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['target_type'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hedef ID</label>
                    <input type="number" name="target_id" class="form-control" value="<?= e((string) ($item['target_id'] ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hedef URL</label>
                    <input type="text" name="target_url" class="form-control" value="<?= e($item['target_url'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) $item['sort_order'] ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Başlangıç</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="<?= e($fmtLocal($item['starts_at'] ?? null)) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bitiş</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="<?= e($fmtLocal($item['expires_at'] ?? null)) ?>">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Banner Düzenle' : 'Yeni Banner', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM banners ORDER BY position, sort_order, id DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Bannerlar</h1>
        <a href="<?= e(admin_url('banners.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Görsel</th><th>Başlık</th><th>Pozisyon</th><th>Hedef</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Banner yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><img src="<?= e(media_url($row['image'])) ?>" class="thumb-sm" alt=""></td>
                    <td><?= e($row['title']) ?></td>
                    <td class="small"><?= e($row['position']) ?> · #<?= (int) $row['sort_order'] ?></td>
                    <td class="small text-muted"><?= e($row['target_type']) ?><?= $row['target_id'] ? ' #' . (int) $row['target_id'] : '' ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('banners.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Banner silinsin mi?">
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
render('Bannerlar', ob_get_clean());
