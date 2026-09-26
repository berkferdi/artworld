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

$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY sort_order, name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $delId = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$delId]);
        flash_set('success', 'Haber silindi.');
        redirect(admin_url('news.php'));
    }

    if ($postAction === 'delete_gallery') {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        $newsId = (int) ($_POST['news_id'] ?? 0);
        $pdo->prepare('DELETE FROM news_images WHERE id = ? AND news_id = ?')->execute([$imgId, $newsId]);
        flash_set('success', 'Galeri görseli silindi.');
        redirect(admin_url('news.php', ['action' => 'edit', 'id' => $newsId]));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $summary = null_if_empty((string) ($_POST['summary'] ?? ''));
        $content = Security::sanitizeHtml((string) ($_POST['content'] ?? ''));
        $author = null_if_empty((string) ($_POST['author'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $isFeatured = checkbox('is_featured');
        $isBreaking = checkbox('is_breaking');
        $status = (string) ($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }
        $publishedAt = null_if_empty((string) ($_POST['published_at'] ?? ''));

        if ($title === '') {
            flash_set('error', 'Haber başlığı zorunludur.');
            redirect(admin_url('news.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug !== '' ? Security::slugify($slug) : Security::slugify($title);
        $slug = unique_slug('news', $slug, $editId);

        $cover = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT cover_image, published_at FROM news WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Haber bulunamadı.');
                redirect(admin_url('news.php'));
            }
            $cover = $existing['cover_image'];
        }

        if (!empty($_FILES['cover_image']['name'])) {
            $up = $uploader->uploadImage($_FILES['cover_image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('news.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $cover = $up['path'];
        }

        if ($status === 'published' && ($publishedAt === null || $publishedAt === '')) {
            $publishedAt = gmdate('Y-m-d H:i:s');
        } elseif ($publishedAt !== null) {
            // datetime-local is local; store as given (admin enters UTC-ish) — convert from TR if needed
            try {
                $dt = new DateTimeImmutable($publishedAt);
                $publishedAt = $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                $publishedAt = gmdate('Y-m-d H:i:s');
            }
        }

        if ($editId) {
            $stmt = $pdo->prepare(
                'UPDATE news SET category_id=?, title=?, slug=?, summary=?, content=?, cover_image=?, author=?,
                 is_featured=?, is_breaking=?, status=?, published_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            );
            $stmt->execute([
                $categoryId, $title, $slug, $summary, $content, $cover, $author,
                $isFeatured, $isBreaking, $status, $publishedAt, $editId,
            ]);
            $newsId = $editId;
            flash_set('success', 'Haber güncellendi.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO news (category_id, title, slug, summary, content, cover_image, author, is_featured, is_breaking, status, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            );
            $stmt->execute([
                $categoryId, $title, $slug, $summary, $content, $cover, $author,
                $isFeatured, $isBreaking, $status, $publishedAt,
            ]);
            $newsId = (int) $pdo->lastInsertId();
            flash_set('success', 'Haber oluşturuldu.');
        }

        if (!empty($_FILES['gallery']['name'][0])) {
            $count = count($_FILES['gallery']['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($_FILES['gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['gallery']['name'][$i],
                    'type' => $_FILES['gallery']['type'][$i],
                    'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                    'error' => $_FILES['gallery']['error'][$i],
                    'size' => $_FILES['gallery']['size'][$i],
                ];
                $up = $uploader->uploadImage($file, 'images');
                if ($up['success']) {
                    $sort = $i + 1;
                    $pdo->prepare(
                        'INSERT INTO news_images (news_id, image_url, caption, sort_order, created_at) VALUES (?, ?, NULL, ?, UTC_TIMESTAMP())'
                    )->execute([$newsId, $up['path'], $sort]);
                }
            }
        }

        redirect(admin_url('news.php', ['action' => 'edit', 'id' => $newsId]));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null,
        'category_id' => null,
        'title' => '',
        'slug' => '',
        'summary' => '',
        'content' => '',
        'cover_image' => null,
        'author' => '',
        'is_featured' => 0,
        'is_breaking' => 0,
        'status' => 'draft',
        'published_at' => null,
    ];
    $gallery = [];

    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Haber bulunamadı.');
            redirect(admin_url('news.php'));
        }
        $g = $pdo->prepare('SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order, id');
        $g->execute([$id]);
        $gallery = $g->fetchAll();
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
            <h1><?= $action === 'edit' ? 'Haber Düzenle' : 'Yeni Haber' ?></h1>
            <a href="<?= e(admin_url('news.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-lg-8">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required data-slug-source="#slug" value="<?= e($item['title']) ?>">
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control" value="<?= e($item['slug']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">— Seçin —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $item['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yazar</label>
                    <input type="text" name="author" class="form-control" value="<?= e($item['author'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <?php foreach (['draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Özet</label>
                    <textarea name="summary" class="form-control" rows="2"><?= e($item['summary'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">İçerik (HTML izinli)</label>
                    <textarea name="content" class="form-control font-monospace" rows="12"><?= e($item['content'] ?? '') ?></textarea>
                    <div class="form-hint">İzin verilen etiketler: p, br, strong, em, u, ul, ol, li, a, h2-h4, blockquote, img, span</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kapak görseli</label>
                    <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['cover_image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['cover_image'])) ?>" alt="" style="max-width:180px;border-radius:8px;"></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Yayın tarihi</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= e($publishedLocal) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= (int) $item['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Manşet</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_breaking" id="is_breaking" value="1" <?= (int) $item['is_breaking'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_breaking">Son dakika</label>
                    </div>
                </div>
                <?php if ($item['id']): ?>
                <div class="col-12">
                    <label class="form-label">Galeri görselleri</label>
                    <input type="file" name="gallery[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                    <?php if ($gallery !== []): ?>
                        <div class="row g-2 mt-2">
                            <?php foreach ($gallery as $gImg): ?>
                                <div class="col-auto text-center">
                                    <img src="<?= e(media_url($gImg['image_url'])) ?>" class="thumb-sm" alt="" style="width:90px;height:70px;">
                                    <form method="post" class="mt-1" data-confirm="Görsel silinsin mi?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_gallery">
                                        <input type="hidden" name="image_id" value="<?= (int) $gImg['id'] ?>">
                                        <input type="hidden" name="news_id" value="<?= (int) $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0">Sil</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-accent">Kaydet</button>
            </div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Haber Düzenle' : 'Yeni Haber', ob_get_clean());
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$sql = 'SELECT n.*, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (n.title LIKE ? OR n.slug LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if (in_array($statusFilter, ['draft', 'published', 'archived'], true)) {
    $sql .= ' AND n.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY n.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Haberler</h1>
        <a href="<?= e(admin_url('news.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni Haber</a>
    </div>
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Ara..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">Tüm durumlar</option>
                <?php foreach (['draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100">Filtrele</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Kapak</th>
                <th>Başlık</th>
                <th>Kategori</th>
                <th>Durum</th>
                <th>Özellik</th>
                <th>Tarih</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="empty-state">Haber bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['cover_image']): ?>
                            <img src="<?= e(media_url($row['cover_image'])) ?>" class="thumb-sm" alt="">
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= e(admin_url('news.php', ['action' => 'edit', 'id' => $row['id']])) ?>"><?= e($row['title']) ?></a>
                    </td>
                    <td class="small text-muted"><?= e($row['category_name'] ?? '—') ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small">
                        <?php if ((int) $row['is_featured']): ?><span class="badge text-bg-dark">Manşet</span><?php endif; ?>
                        <?php if ((int) $row['is_breaking']): ?><span class="badge text-bg-danger">Son Dakika</span><?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= format_dt($row['published_at'] ?: $row['created_at']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('news.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu haberi silmek istediğinize emin misiniz?">
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
render('Haberler', ob_get_clean());
