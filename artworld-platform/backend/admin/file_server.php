<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Services\FileServerConfigService;
use App\Services\FileServerService;

$config = new FileServerConfigService();
$fs = new FileServerService($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'test') {
        $probe = $fs->probe();
        $config->recordProbe((bool) $probe['reachable'], (string) $probe['detail']);
        if ($probe['reachable']) {
            flash_set('success', '✓ File Server bağlantısı başarılı — ' . $probe['detail']);
        } else {
            flash_set('error', '✗ Bağlantı başarısız — ' . $probe['detail']);
        }
        redirect(admin_url('file_server.php'));
    }

    if ($action === 'save') {
        $updatePassword = trim((string) ($_POST['password'] ?? '')) !== '';
        $updateToken = trim((string) ($_POST['upload_token'] ?? '')) !== '';
        if (!empty($_POST['clear_password'])) {
            $_POST['password'] = '';
            $updatePassword = true;
        }
        if (!empty($_POST['clear_token'])) {
            $_POST['upload_token'] = '';
            $updateToken = true;
        }
        $config->save([
            'enabled' => isset($_POST['enabled']),
            'mode' => (string) ($_POST['mode'] ?? 'local'),
            'host' => (string) ($_POST['host'] ?? ''),
            'port' => (int) ($_POST['port'] ?? 22),
            'user' => (string) ($_POST['user'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'ssh_key' => (string) ($_POST['ssh_key'] ?? ''),
            'base_path' => (string) ($_POST['base_path'] ?? '/media/videos'),
            'public_base_url' => (string) ($_POST['public_base_url'] ?? ''),
            'upload_url' => (string) ($_POST['upload_url'] ?? ''),
            'upload_token' => (string) ($_POST['upload_token'] ?? ''),
            'timeout' => (int) ($_POST['timeout'] ?? 8),
        ], $updatePassword, $updateToken);
        flash_set('success', 'File Server ayarları kaydedildi.');
        redirect(admin_url('file_server.php'));
    }
}

$form = $config->forAdminForm();
$probe = $fs->probe();

ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Medya / File Server</h1>
        <form method="post" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test">
            <button class="btn btn-sm btn-outline-secondary" type="submit">Bağlantıyı Test Et</button>
        </form>
    </div>

    <div class="alert <?= $probe['reachable'] ? 'alert-success' : 'alert-warning' ?>">
        <strong>Durum:</strong>
        <?= $probe['reachable'] ? '✓ Online' : '✗ Offline / Pending' ?>
        — <?= e((string) $probe['detail']) ?>
        <?php if (!empty($form['last_check_at'])): ?>
            <div class="small mt-1">Son test: <?= e((string) $form['last_check_at']) ?></div>
        <?php endif; ?>
        <?php if (!empty($probe['checks'])): ?>
            <div class="small mt-2">
                <?php foreach ($probe['checks'] as $k => $v): ?>
                    <span class="badge text-bg-light border me-1"><?= e($k) ?>: <?= e($v) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-muted small">
        Production hedef: <code>https://media.artworldapi.com.tr</code> → File Server
        (<code>193.35.155.55</code>). Host erişilemiyorsa <strong>local</strong> modda videolar
        API sunucusundaki <code>uploads/</code> alanına yazılır; YouTube kaynağı etkilenmez.
    </p>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1" <?= !empty($form['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enabled">File Server aktif</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Mod</label>
                <select name="mode" class="form-select">
                    <?php foreach (['local' => 'Local (API uploads)', 'sftp' => 'SFTP', 'http' => 'HTTP Upload'] as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($form['mode'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Timeout (sn)</label>
                <input type="number" name="timeout" class="form-control" min="3" max="60" value="<?= (int) ($form['timeout'] ?? 8) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Host / IP</label>
                <input type="text" name="host" class="form-control" value="<?= e((string) ($form['host'] ?? '')) ?>" placeholder="193.35.155.55">
            </div>
            <div class="col-md-2">
                <label class="form-label">Port</label>
                <input type="number" name="port" class="form-control" value="<?= (int) ($form['port'] ?? 22) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kullanıcı</label>
                <input type="text" name="user" class="form-control" value="<?= e((string) ($form['user'] ?? '')) ?>" autocomplete="off">
            </div>

            <div class="col-md-6">
                <label class="form-label">Şifre <?= !empty($form['password_set']) ? '(kayıtlı — boş bırakırsanız değişmez)' : '' ?></label>
                <input type="password" name="password" class="form-control" value="" autocomplete="new-password" placeholder="<?= !empty($form['password_set']) ? '••••••••' : '' ?>">
                <?php if (!empty($form['password_set'])): ?>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="clear_password" id="clear_password" value="1">
                        <label class="form-check-label" for="clear_password">Şifreyi temizle</label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">SSH private key path (opsiyonel)</label>
                <input type="text" name="ssh_key" class="form-control" value="<?= e((string) ($form['ssh_key'] ?? '')) ?>" placeholder="/home/art/.ssh/id_rsa">
            </div>

            <div class="col-md-6">
                <label class="form-label">Upload Path</label>
                <input type="text" name="base_path" class="form-control" value="<?= e((string) ($form['base_path'] ?? '/media/videos')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Public Media URL</label>
                <input type="url" name="public_base_url" class="form-control" value="<?= e((string) ($form['public_base_url'] ?? '')) ?>" placeholder="https://media.artworldapi.com.tr">
            </div>

            <div class="col-md-6">
                <label class="form-label">HTTP Upload URL (mode=http)</label>
                <input type="url" name="upload_url" class="form-control" value="<?= e((string) ($form['upload_url'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload Token <?= !empty($form['upload_token_set']) ? '(kayıtlı)' : '' ?></label>
                <input type="password" name="upload_token" class="form-control" value="" autocomplete="new-password" placeholder="<?= !empty($form['upload_token_set']) ? '••••••••' : '' ?>">
                <?php if (!empty($form['upload_token_set'])): ?>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="clear_token" id="clear_token" value="1">
                        <label class="form-check-label" for="clear_token">Token temizle</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-accent" type="submit">Kaydet</button>
        </div>
    </form>
</div>
<?php
render('File Server', ob_get_clean());
