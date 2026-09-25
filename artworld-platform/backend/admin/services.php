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
        $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Servis silindi.');
        redirect(admin_url('services.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? ''));
        $configRaw = trim((string) ($_POST['config'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';

        if ($name === '' || $type === '') {
            flash_set('error', 'Ad ve tip zorunludur.');
            redirect(admin_url('services.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $slug = $slug === '' ? Security::slugify($name) : Security::slugify($slug);
        $slug = unique_slug('services', $slug, $editId);

        $config = null;
        if ($configRaw !== '') {
            $decoded = json_decode($configRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                flash_set('error', 'Config geçerli JSON olmalıdır.');
                redirect(admin_url('services.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
            }
            $config = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE services SET name=?, slug=?, type=?, config=?, status=?, sort_order=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$name, $slug, $type, $config, $status, $sortOrder, $editId]);
            flash_set('success', 'Servis güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO services (name, slug, type, config, status, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$name, $slug, $type, $config, $status, $sortOrder]);
            flash_set('success', 'Servis oluşturuldu.');
        }
        redirect(admin_url('services.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'name' => '', 'slug' => '', 'type' => '', 'config' => '',
        'status' => 'inactive', 'sort_order' => 0,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Servis bulunamadı.');
            redirect(admin_url('services.php'));
        }
    }

    $configDisplay = '';
    if (!empty($item['config'])) {
        if (is_string($item['config'])) {
            $decoded = json_decode($item['config'], true);
            $configDisplay = $decoded !== null
                ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : (string) $item['config'];
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Servis Düzenle' : 'Yeni Servis' ?></h1>
            <a href="<?= e(admin_url('services.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post">
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
                <div class="col-md-4">
                    <label class="form-label">Tip *</label>
                    <input type="text" name="type" class="form-control" required value="<?= e($item['type']) ?>" placeholder="market, weather...">
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
                <div class="col-12">
                    <label class="form-label">Config (JSON)</label>
                    <textarea name="config" class="form-control font-monospace" rows="6"><?= e($configDisplay) ?></textarea>
                </div>
            </div>
            <div class="mt-3"><button type="submit" class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Servis Düzenle' : 'Yeni Servis', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM services ORDER BY sort_order ASC, name ASC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Servisler</h1>
        <a href="<?= e(admin_url('services.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Ad</th><th>Slug</th><th>Tip</th><th>Sıra</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="6" class="empty-state">Servis bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-muted small"><?= e($row['slug']) ?></td>
                    <td><code><?= e($row['type']) ?></code></td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('services.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Bu servisi silmek istediğinize emin misiniz?">
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
render('Servisler', ob_get_clean());
