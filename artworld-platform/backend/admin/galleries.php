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
        $pdo->prepare('DELETE FROM galleries WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Galeri silindi.');
        redirect(admin_url('galleries.php'));
    }

    if ($postAction === 'add_image') {
        $galleryId = (int) ($_POST['gallery_id'] ?? 0);
        $caption = null_if_empty((string) ($_POST['caption'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($galleryId <= 0 || empty($_FILES['image']['name'])) {
            flash_set('error', 'Galeri ve görsel zorunludur.');
            redirect(admin_url('galleries.php', ['action' => 'edit', 'id' => $galleryId]));
        }
        $up = $uploader->uploadImage($_FILES['image'], 'images');
        if (!$up['success']) {
            flash_set('error', $up['message']);
            redirect(admin_url('galleries.php', ['action' => 'edit', 'id' => $galleryId]));
        }
        $pdo->prepare(
            'INSERT INTO gallery_images (gallery_id, image, caption, sort_order, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())'
        )->execute([$galleryId, $up['path'], $caption, $sortOrder]);
        flash_set('success', 'Görsel eklendi.');
        redirect(admin_url('galleries.php', ['action' => 'edit', 'id' => $galleryId]));
    }

    if ($postAction === 'delete_image') {
        $imgId = (int) ($_POST['id'] ?? 0);
        $galleryId = (int) ($_POST['gallery_id'] ?? 0);
        $pdo->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$imgId]);
        flash_set('success', 'Görsel silindi.');
        redirect(admin_url('galleries.php', ['action' => 'edit', 'id' => $galleryId]));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('galleries.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug === '' ? Security::slugify($title) : Security::slugify($slug);
        $slug = unique_slug('galleries', $slug, $editId);

        $cover = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT cover_image FROM galleries WHERE id = ?');
            $cur->execute([$editId]);
            $row = $cur->fetch();
            if (!$row) {
                flash_set('error', 'Galeri bulunamadı.');
                redirect(admin_url('galleries.php'));
            }
            $cover = $row['cover_image'];
        }

        if (!empty($_FILES['cover_image']['name'])) {
            $up = $uploader->uploadImage($_FILES['cover_image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('galleries.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $cover = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE galleries SET title=?, slug=?, description=?, cover_image=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $slug, $description, $cover, $status, $editId]);
            flash_set('success', 'Galeri güncellendi.');
            redirect(admin_url('galleries.php', ['action' => 'edit', 'id' => $editId]));
        }

        $pdo->prepare(
            'INSERT INTO galleries (title, slug, description, cover_image, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        )->execute([$title, $slug, $description, $cover, $status]);
        flash_set('success', 'Galeri oluşturuldu.');
        redirect(admin_url('galleries.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'slug' => '', 'description' => '',
        'cover_image' => null, 'status' => 'draft',
    ];
    $images = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM galleries WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Galeri bulunamadı.');
            redirect(admin_url('galleries.php'));
        }
        $imgStmt = $pdo->prepare('SELECT * FROM gallery_images WHERE gallery_id = ? ORDER BY sort_order ASC, id ASC');
        $imgStmt->execute([(int) $item['id']]);
        $images = $imgStmt->fetchAll();
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Galeri Düzenle' : 'Yeni Galeri' ?></h1>
            <a href="<?= e(admin_url('galleries.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required data-slug-source="#slug" value="<?= e($item['title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Yayında</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kapak</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['cover_image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['cover_image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>

    <?php if ($item['id']): ?>
    <div class="panel mt-4">
        <div class="panel-header"><h2 class="h5 mb-0">Galeri Görselleri</h2></div>
        <form method="post" enctype="multipart/form-data" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_image">
            <input type="hidden" name="gallery_id" value="<?= (int) $item['id'] ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Görsel</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="caption" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-secondary w-100">Ekle</button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Görsel</th><th>Başlık</th><th>Sıra</th><th></th></tr></thead>
                <tbody>
                <?php if ($images === []): ?>
                    <tr><td colspan="4" class="empty-state">Henüz görsel yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($images as $img): ?>
                    <tr>
                        <td><img src="<?= e(media_url($img['image'])) ?>" class="thumb-sm" alt=""></td>
                        <td><?= e($img['caption'] ?? '') ?></td>
                        <td><?= (int) $img['sort_order'] ?></td>
                        <td class="text-end">
                            <form method="post" data-confirm="Görseli silmek istediğinize emin misiniz?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_image">
                                <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
                                <input type="hidden" name="gallery_id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php
    render($action === 'edit' ? 'Galeri Düzenle' : 'Yeni Galeri', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM galleries ORDER BY created_at DESC, id DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Galeriler</h1>
        <a href="<?= e(admin_url('galleries.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Kapak</th><th>Başlık</th><th>Slug</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="empty-state">Galeri bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['cover_image']): ?>
                            <img src="<?= e(media_url($row['cover_image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= e($row['title']) ?></td>
                    <td class="text-muted small"><?= e($row['slug']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('galleries.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu galeriyi silmek istediğinize emin misiniz?">
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
render('Galeriler', ob_get_clean());
