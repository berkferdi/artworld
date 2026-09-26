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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM authors WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Yazar silindi.');
        redirect(admin_url('authors.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $bio = null_if_empty((string) ($_POST['bio'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            flash_set('error', 'Yazar adı zorunludur.');
            redirect(admin_url('authors.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug === '' ? Security::slugify($name) : Security::slugify($slug);
        $slug = unique_slug('authors', $slug, $editId);

        $photo = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT photo FROM authors WHERE id = ?');
            $cur->execute([$editId]);
            $row = $cur->fetch();
            if (!$row) {
                flash_set('error', 'Yazar bulunamadı.');
                redirect(admin_url('authors.php'));
            }
            $photo = $row['photo'];
        }

        if (!empty($_FILES['photo']['name'])) {
            $up = $uploader->uploadImage($_FILES['photo'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('authors.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $photo = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE authors SET name=?, slug=?, bio=?, photo=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$name, $slug, $bio, $photo, $status, $editId]);
            flash_set('success', 'Yazar güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO authors (name, slug, bio, photo, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$name, $slug, $bio, $photo, $status]);
            flash_set('success', 'Yazar oluşturuldu.');
        }
        redirect(admin_url('authors.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = ['id' => null, 'name' => '', 'slug' => '', 'bio' => '', 'photo' => null, 'status' => 'active'];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM authors WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Yazar bulunamadı.');
            redirect(admin_url('authors.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Yazar Düzenle' : 'Yeni Yazar' ?></h1>
            <a href="<?= e(admin_url('authors.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad *</label>
                    <input type="text" name="name" class="form-control" required data-slug-source="#slug" value="<?= e($item['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Biyografi</label>
                    <textarea name="bio" class="form-control" rows="4"><?= e($item['bio'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fotoğraf</label>
                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['photo'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['photo'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Yazar Düzenle' : 'Yeni Yazar', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM authors ORDER BY name ASC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Yazarlar</h1>
        <a href="<?= e(admin_url('authors.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Foto</th><th>Ad</th><th>Slug</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="empty-state">Yazar bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['photo']): ?>
                            <img src="<?= e(media_url($row['photo'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-muted small"><?= e($row['slug']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('authors.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu yazarı silmek istediğinize emin misiniz?">
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
render('Yazarlar', ob_get_clean());
