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
        $delId = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$delId]);
        flash_set('success', 'Kategori silindi.');
        redirect(admin_url('categories.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($name === '') {
            flash_set('error', 'Kategori adı zorunludur.');
            redirect(admin_url('categories.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if ($slug === '') {
            $slug = Security::slugify($name);
        } else {
            $slug = Security::slugify($slug);
        }
        $slug = unique_slug('categories', $slug, $editId);

        $image = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT image FROM categories WHERE id = ?');
            $cur->execute([$editId]);
            $row = $cur->fetch();
            if (!$row) {
                flash_set('error', 'Kategori bulunamadı.');
                redirect(admin_url('categories.php'));
            }
            $image = $row['image'];
        }

        if (!empty($_FILES['image']['name'])) {
            $up = $uploader->uploadImage($_FILES['image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('categories.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $image = $up['path'];
        }

        if ($editId) {
            $stmt = $pdo->prepare(
                'UPDATE categories SET name=?, slug=?, description=?, image=?, sort_order=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            );
            $stmt->execute([$name, $slug, $description, $image, $sortOrder, $status, $editId]);
            flash_set('success', 'Kategori güncellendi.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, slug, description, image, sort_order, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $stmt->execute([$name, $slug, $description, $image, $sortOrder, $status]);
            flash_set('success', 'Kategori oluşturuldu.');
        }
        redirect(admin_url('categories.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null,
        'name' => '',
        'slug' => '',
        'description' => '',
        'image' => null,
        'sort_order' => 0,
        'status' => 'active',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Kategori bulunamadı.');
            redirect(admin_url('categories.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Kategori Düzenle' : 'Yeni Kategori' ?></h1>
            <a href="<?= e(admin_url('categories.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad *</label>
                    <input type="text" name="name" class="form-control" required data-slug-source="#slug"
                           value="<?= e($item['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($item['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) $item['sort_order'] ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Görsel</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-accent">Kaydet</button>
            </div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Kategori Düzenle' : 'Yeni Kategori', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Kategoriler</h1>
        <a href="<?= e(admin_url('categories.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Görsel</th>
                <th>Ad</th>
                <th>Slug</th>
                <th>Sıra</th>
                <th>Durum</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="6" class="empty-state">Kategori bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="<?= e(media_url($row['image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-muted small"><?= e($row['slug']) ?></td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('categories.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu kategoriyi silmek istediğinize emin misiniz?">
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
render('Kategoriler', ob_get_clean());
