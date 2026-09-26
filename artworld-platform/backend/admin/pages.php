<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Core\Security;

$pdo = Database::connection();
$action = get_action();
$id = request_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Sayfa silindi.');
        redirect(admin_url('pages.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        $metaTitle = null_if_empty((string) ($_POST['meta_title'] ?? ''));
        $metaDescription = null_if_empty((string) ($_POST['meta_description'] ?? ''));
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('pages.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug === '' ? Security::slugify($title) : Security::slugify($slug);
        $slug = unique_slug('pages', $slug, $editId);

        if ($editId) {
            $pdo->prepare(
                'UPDATE pages SET title=?, slug=?, content=?, meta_title=?, meta_description=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $slug, $content, $metaTitle, $metaDescription, $status, $editId]);
            flash_set('success', 'Sayfa güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO pages (title, slug, content, meta_title, meta_description, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$title, $slug, $content, $metaTitle, $metaDescription, $status]);
            flash_set('success', 'Sayfa oluşturuldu.');
        }
        redirect(admin_url('pages.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'slug' => '', 'content' => '',
        'meta_title' => '', 'meta_description' => '', 'status' => 'draft',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Sayfa bulunamadı.');
            redirect(admin_url('pages.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Sayfa Düzenle' : 'Yeni Sayfa' ?></h1>
            <a href="<?= e(admin_url('pages.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post">
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
                    <label class="form-label">İçerik</label>
                    <textarea name="content" class="form-control" rows="10"><?= e($item['content'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta Başlık</label>
                    <input type="text" name="meta_title" class="form-control" value="<?= e($item['meta_title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Yayında</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Meta Açıklama</label>
                    <textarea name="meta_description" class="form-control" rows="2"><?= e($item['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Sayfa Düzenle' : 'Yeni Sayfa', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM pages ORDER BY title ASC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Sayfalar</h1>
        <a href="<?= e(admin_url('pages.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Başlık</th><th>Slug</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="4" class="empty-state">Sayfa bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td class="text-muted small"><?= e($row['slug']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('pages.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu sayfayı silmek istediğinize emin misiniz?">
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
render('Sayfalar', ob_get_clean());
