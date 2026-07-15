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
        $pdo->prepare('DELETE FROM programs WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Program silindi.');
        redirect(admin_url('programs.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = null_if_empty((string) ($_POST['description'] ?? ''));
        $presenter = null_if_empty((string) ($_POST['presenter'] ?? ''));
        $broadcastDay = null_if_empty((string) ($_POST['broadcast_day'] ?? ''));
        $broadcastTime = null_if_empty((string) ($_POST['broadcast_time'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('programs.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug !== '' ? Security::slugify($slug) : Security::slugify($title);
        $slug = unique_slug('programs', $slug, $editId);

        $cover = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT cover_image FROM programs WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Program bulunamadı.');
                redirect(admin_url('programs.php'));
            }
            $cover = $existing['cover_image'];
        }

        if (!empty($_FILES['cover_image']['name'])) {
            $up = $uploader->uploadImage($_FILES['cover_image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('programs.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $cover = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE programs SET title=?, slug=?, description=?, cover_image=?, presenter=?, broadcast_day=?, broadcast_time=?, sort_order=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $slug, $description, $cover, $presenter, $broadcastDay, $broadcastTime, $sortOrder, $status, $editId]);
            flash_set('success', 'Program güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO programs (title, slug, description, cover_image, presenter, broadcast_day, broadcast_time, sort_order, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$title, $slug, $description, $cover, $presenter, $broadcastDay, $broadcastTime, $sortOrder, $status]);
            flash_set('success', 'Program oluşturuldu.');
        }
        redirect(admin_url('programs.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'slug' => '', 'description' => '', 'cover_image' => null,
        'presenter' => '', 'broadcast_day' => '', 'broadcast_time' => '', 'sort_order' => 0, 'status' => 'active',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM programs WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Program bulunamadı.');
            redirect(admin_url('programs.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Program Düzenle' : 'Yeni Program' ?></h1>
            <a href="<?= e(admin_url('programs.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
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
                    <label class="form-label">Sunucu</label>
                    <input type="text" name="presenter" class="form-control" value="<?= e($item['presenter'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın günü</label>
                    <input type="text" name="broadcast_day" class="form-control" value="<?= e($item['broadcast_day'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın saati</label>
                    <input type="text" name="broadcast_time" class="form-control" value="<?= e($item['broadcast_time'] ?? '') ?>" placeholder="20:00">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kapak</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['cover_image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['cover_image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
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
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Program Düzenle' : 'Yeni Program', ob_get_clean());
    exit;
}

$rows = $pdo->query(
    'SELECT p.*, (SELECT COUNT(*) FROM program_episodes e WHERE e.program_id = p.id) AS episode_count
     FROM programs p ORDER BY p.sort_order, p.title'
)->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Programlar</h1>
        <a href="<?= e(admin_url('programs.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th></th><th>Başlık</th><th>Sunucu</th><th>Bölüm</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Program yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php if ($row['cover_image']): ?><img src="<?= e(media_url($row['cover_image'])) ?>" class="thumb-sm" alt=""><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <a href="<?= e(admin_url('programs.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a>
                        <div class="small text-muted"><?= e(($row['broadcast_day'] ?? '') . ' ' . ($row['broadcast_time'] ?? '')) ?></div>
                    </td>
                    <td><?= e($row['presenter'] ?? '—') ?></td>
                    <td>
                        <a href="<?= e(admin_url('episodes.php', ['program_id' => $row['id']])) ?>"><?= (int) $row['episode_count'] ?></a>
                    </td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('programs.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Program ve bölümleri silinsin mi?">
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
render('Programlar', ob_get_clean());
