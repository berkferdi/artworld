<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Auth;
use App\Core\Database;

Auth::requireRole('super_admin');

$pdo = Database::connection();
$action = get_action();
$id = request_id();
$roles = [
    'super_admin' => 'Süper Admin',
    'admin' => 'Admin',
    'editor' => 'Editör',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId === Auth::id()) {
            flash_set('error', 'Kendi hesabınızı silemezsiniz.');
            redirect(admin_url('admins.php'));
        }
        $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$delId]);
        flash_set('success', 'Yönetici silindi.');
        redirect(admin_url('admins.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'admin');
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirmation'] ?? '');

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Geçerli ad ve e-posta girin.');
            redirect(admin_url('admins.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if (!isset($roles[$role])) {
            $role = 'admin';
        }

        $check = $pdo->prepare('SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1');
        $check->execute([$email, $editId ?: 0]);
        if ($check->fetch()) {
            flash_set('error', 'Bu e-posta zaten kayıtlı.');
            redirect(admin_url('admins.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        if ($editId) {
            if ($password !== '') {
                if (strlen($password) < 8 || $password !== $password2) {
                    flash_set('error', 'Şifre en az 8 karakter olmalı ve eşleşmelidir.');
                    redirect(admin_url('admins.php', ['action' => 'edit', 'id' => $editId]));
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare(
                    'UPDATE admins SET name=?, email=?, password=?, role=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
                )->execute([$name, $email, $hash, $role, $status, $editId]);
            } else {
                $pdo->prepare(
                    'UPDATE admins SET name=?, email=?, role=?, status=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
                )->execute([$name, $email, $role, $status, $editId]);
            }
            flash_set('success', 'Yönetici güncellendi.');
        } else {
            if (strlen($password) < 8 || $password !== $password2) {
                flash_set('error', 'Şifre en az 8 karakter olmalı ve eşleşmelidir.');
                redirect(admin_url('admins.php', ['action' => 'create']));
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO admins (name, email, password, role, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$name, $email, $hash, $role, $status]);
            flash_set('success', 'Yönetici oluşturuldu.');
        }
        redirect(admin_url('admins.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'name' => '', 'email' => '', 'role' => 'admin', 'status' => 'active',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM admins WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Yönetici bulunamadı.');
            redirect(admin_url('admins.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Yönetici Düzenle' : 'Yeni Yönetici' ?></h1>
            <a href="<?= e(admin_url('admins.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad *</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($item['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-posta *</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($item['email']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <?php foreach ($roles as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['role'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şifre <?= $item['id'] ? '(boş bırakılırsa değişmez)' : '*' ?></label>
                    <input type="password" name="password" class="form-control" <?= $item['id'] ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şifre tekrar</label>
                    <input type="password" name="password_confirmation" class="form-control" <?= $item['id'] ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Yönetici Düzenle' : 'Yeni Yönetici', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT id, name, email, role, status, last_login_at, created_at FROM admins ORDER BY id')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Yöneticiler</h1>
        <a href="<?= e(admin_url('admins.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son giriş</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><?= e($roles[$row['role']] ?? $row['role']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['last_login_at'] ?? null) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('admins.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <?php if ((int) $row['id'] !== Auth::id()): ?>
                            <form method="post" data-confirm="Yönetici silinsin mi?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Sil</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render('Yöneticiler', ob_get_clean());
