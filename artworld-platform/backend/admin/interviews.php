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
        $pdo->prepare('DELETE FROM interviews WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Röportaj silindi.');
        redirect(admin_url('interviews.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $summary = null_if_empty((string) ($_POST['summary'] ?? ''));
        $content = (string) ($_POST['content'] ?? '');
        $author = null_if_empty((string) ($_POST['author'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }
        $publishedAt = null_if_empty((string) ($_POST['published_at'] ?? ''));
        if ($publishedAt !== null) {
            try {
                $publishedAt = (new DateTimeImmutable($publishedAt))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                $publishedAt = null;
            }
        }
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = gmdate('Y-m-d H:i:s');
        }

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('interviews.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug === '' ? Security::slugify($title) : Security::slugify($slug);
        $slug = unique_slug('interviews', $slug, $editId);

        $cover = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT cover_image FROM interviews WHERE id = ?');
            $cur->execute([$editId]);
            $row = $cur->fetch();
            if (!$row) {
                flash_set('error', 'Röportaj bulunamadı.');
                redirect(admin_url('interviews.php'));
            }
            $cover = $row['cover_image'];
        }

        if (!empty($_FILES['cover_image']['name'])) {
            $up = $uploader->uploadImage($_FILES['cover_image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('interviews.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $cover = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE interviews SET title=?, slug=?, summary=?, content=?, cover_image=?, author=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $slug, $summary, $content, $cover, $author, $status, $publishedAt, $editId]);
            flash_set('success', 'Röportaj güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO interviews (title, slug, summary, content, cover_image, author, status, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$title, $slug, $summary, $content, $cover, $author, $status, $publishedAt]);
            flash_set('success', 'Röportaj oluşturuldu.');
        }
        redirect(admin_url('interviews.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'slug' => '', 'summary' => '', 'content' => '',
        'cover_image' => null, 'author' => '', 'status' => 'draft', 'published_at' => null,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM interviews WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Röportaj bulunamadı.');
            redirect(admin_url('interviews.php'));
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
            <h1><?= $action === 'edit' ? 'Röportaj Düzenle' : 'Yeni Röportaj' ?></h1>
            <a href="<?= e(admin_url('interviews.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
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
                <div class="col-md-4">
                    <label class="form-label">Yazar</label>
                    <input type="text" name="author" class="form-control" value="<?= e($item['author'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Taslak</option>
                        <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Yayında</option>
                        <option value="archived" <?= $item['status'] === 'archived' ? 'selected' : '' ?>>Arşiv</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın Tarihi</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= e($publishedLocal) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Özet</label>
                    <textarea name="summary" class="form-control" rows="2"><?= e($item['summary'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">İçerik</label>
                    <textarea name="content" class="form-control" rows="10"><?= e($item['content'] ?? '') ?></textarea>
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
    <?php
    render($action === 'edit' ? 'Röportaj Düzenle' : 'Yeni Röportaj', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM interviews ORDER BY published_at DESC, id DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Röportajlar</h1>
        <a href="<?= e(admin_url('interviews.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Kapak</th><th>Başlık</th><th>Yazar</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="6" class="empty-state">Röportaj bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['cover_image']): ?>
                            <img src="<?= e(media_url($row['cover_image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['author'] ?? '—') ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['published_at'] ?? null) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('interviews.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu röportajı silmek istediğinize emin misiniz?">
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
render('Röportajlar', ob_get_clean());
