<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;

$pdo = Database::connection();
$action = get_action();
$id = request_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Menü silindi.');
        redirect(admin_url('menus.php'));
    }

    if ($postAction === 'delete_item') {
        $itemId = (int) ($_POST['id'] ?? 0);
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$itemId]);
        flash_set('success', 'Menü öğesi silindi.');
        redirect(admin_url('menus.php', ['action' => 'edit', 'id' => $menuId]));
    }

    if ($postAction === 'save_item') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $itemId = (int) ($_POST['item_id'] ?? 0) ?: null;
        $title = trim((string) ($_POST['item_title'] ?? ''));
        $url = trim((string) ($_POST['item_url'] ?? ''));
        $target = (string) ($_POST['item_target'] ?? '_self');
        $sortOrder = (int) ($_POST['item_sort_order'] ?? 0);
        $status = ($_POST['item_status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($menuId <= 0 || $title === '' || $url === '') {
            flash_set('error', 'Menü öğesi başlık ve URL zorunludur.');
            redirect(admin_url('menus.php', ['action' => 'edit', 'id' => $menuId]));
        }

        if ($itemId) {
            $pdo->prepare(
                'UPDATE menu_items SET title=?, url=?, target=?, sort_order=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=? AND menu_id=?'
            )->execute([$title, $url, $target, $sortOrder, $status, $itemId, $menuId]);
            flash_set('success', 'Menü öğesi güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO menu_items (menu_id, parent_id, title, url, target, sort_order, status, created_at, updated_at)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$menuId, $title, $url, $target, $sortOrder, $status]);
            flash_set('success', 'Menü öğesi eklendi.');
        }
        redirect(admin_url('menus.php', ['action' => 'edit', 'id' => $menuId]));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($title === '' || $location === '') {
            flash_set('error', 'Başlık ve konum zorunludur.');
            redirect(admin_url('menus.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE menus SET title=?, location=?, sort_order=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$title, $location, $sortOrder, $status, $editId]);
            flash_set('success', 'Menü güncellendi.');
            redirect(admin_url('menus.php', ['action' => 'edit', 'id' => $editId]));
        }

        $pdo->prepare(
            'INSERT INTO menus (title, location, sort_order, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        )->execute([$title, $location, $sortOrder, $status]);
        flash_set('success', 'Menü oluşturuldu.');
        redirect(admin_url('menus.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = ['id' => null, 'title' => '', 'location' => 'header', 'sort_order' => 0, 'status' => 'active'];
    $menuItems = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Menü bulunamadı.');
            redirect(admin_url('menus.php'));
        }
        $mi = $pdo->prepare('SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC, id ASC');
        $mi->execute([(int) $item['id']]);
        $menuItems = $mi->fetchAll();
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Menü Düzenle' : 'Yeni Menü' ?></h1>
            <a href="<?= e(admin_url('menus.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($item['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Konum *</label>
                    <input type="text" name="location" class="form-control" required value="<?= e($item['location']) ?>" placeholder="header, footer...">
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
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>

    <?php if ($item['id']): ?>
    <div class="panel mt-4">
        <div class="panel-header"><h2 class="h5 mb-0">Menü Öğeleri</h2></div>
        <form method="post" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_item">
            <input type="hidden" name="menu_id" value="<?= (int) $item['id'] ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="item_title" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">URL</label>
                    <input type="text" name="item_url" class="form-control" required placeholder="/">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hedef</label>
                    <select name="item_target" class="form-select">
                        <option value="_self">_self</option>
                        <option value="_blank">_blank</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="item_sort_order" class="form-control" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Durum</label>
                    <select name="item_status" class="form-select">
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-secondary w-100">Ekle</button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Başlık</th><th>URL</th><th>Hedef</th><th>Sıra</th><th>Durum</th><th></th></tr></thead>
                <tbody>
                <?php if ($menuItems === []): ?>
                    <tr><td colspan="6" class="empty-state">Henüz öğe yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($menuItems as $mi): ?>
                    <tr>
                        <td><?= e($mi['title']) ?></td>
                        <td class="small text-muted"><?= e($mi['url']) ?></td>
                        <td><?= e($mi['target']) ?></td>
                        <td><?= (int) $mi['sort_order'] ?></td>
                        <td><?= status_badge($mi['status']) ?></td>
                        <td class="text-end">
                            <form method="post" data-confirm="Öğeyi silmek istediğinize emin misiniz?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="id" value="<?= (int) $mi['id'] ?>">
                                <input type="hidden" name="menu_id" value="<?= (int) $item['id'] ?>">
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
    render($action === 'edit' ? 'Menü Düzenle' : 'Yeni Menü', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM menus ORDER BY location ASC, sort_order ASC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Menüler</h1>
        <a href="<?= e(admin_url('menus.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Başlık</th><th>Konum</th><th>Sıra</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="empty-state">Menü bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><code><?= e($row['location']) ?></code></td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('menus.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu menüyü silmek istediğinize emin misiniz?">
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
render('Menüler', ob_get_clean());
