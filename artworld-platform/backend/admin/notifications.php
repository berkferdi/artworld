<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;
use App\Services\PushNotificationService;
use App\Services\UploadService;

$pdo = Database::connection();
$action = get_action();
$id = request_id();
$uploader = new UploadService();
$push = new PushNotificationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM notifications WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Bildirim silindi.');
        redirect(admin_url('notifications.php'));
    }

    if ($postAction === 'save' || $postAction === 'send') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $targetType = (string) ($_POST['target_type'] ?? 'none');
        if (!in_array($targetType, ['news', 'video', 'program', 'url', 'none'], true)) {
            $targetType = 'none';
        }
        $targetId = (int) ($_POST['target_id'] ?? 0) ?: null;
        $targetUrl = null_if_empty((string) ($_POST['target_url'] ?? ''));

        if ($title === '' || $body === '') {
            flash_set('error', 'Başlık ve metin zorunludur.');
            redirect(admin_url('notifications.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $image = null;
        if ($editId) {
            $cur = $pdo->prepare('SELECT image, status FROM notifications WHERE id = ?');
            $cur->execute([$editId]);
            $existing = $cur->fetch();
            if (!$existing) {
                flash_set('error', 'Bildirim bulunamadı.');
                redirect(admin_url('notifications.php'));
            }
            $image = $existing['image'];
            if ($existing['status'] === 'sent' && $postAction === 'save') {
                flash_set('error', 'Gönderilmiş bildirim düzenlenemez.');
                redirect(admin_url('notifications.php'));
            }
        }

        if (!empty($_FILES['image']['name'])) {
            $up = $uploader->uploadImage($_FILES['image'], 'images');
            if (!$up['success']) {
                flash_set('error', $up['message']);
                redirect(admin_url('notifications.php', ['action' => 'create']));
            }
            $image = $up['path'];
        }

        if ($editId) {
            $pdo->prepare(
                'UPDATE notifications SET title=?, body=?, image=?, target_type=?, target_id=?, target_url=? WHERE id=? AND status = \'draft\''
            )->execute([$title, $body, $image, $targetType, $targetId, $targetUrl, $editId]);
            $notifId = $editId;
        } else {
            $pdo->prepare(
                'INSERT INTO notifications (title, body, image, target_type, target_id, target_url, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, \'draft\', UTC_TIMESTAMP())'
            )->execute([$title, $body, $image, $targetType, $targetId, $targetUrl]);
            $notifId = (int) $pdo->lastInsertId();
        }

        if ($postAction === 'send') {
            $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id = ?');
            $stmt->execute([$notifId]);
            $notif = $stmt->fetch();
            $result = $push->send([
                'title' => $notif['title'],
                'body' => $notif['body'],
                'image' => $notif['image'] ? media_url($notif['image']) : null,
                'target_type' => $notif['target_type'],
                'target_id' => $notif['target_id'],
                'target_url' => $notif['target_url'],
            ]);

            if ($result['success']) {
                $pdo->prepare("UPDATE notifications SET status='sent', sent_at=UTC_TIMESTAMP() WHERE id=?")->execute([$notifId]);
                flash_set('success', $result['message']);
            } else {
                $status = !empty($result['configured']) ? 'failed' : 'draft';
                if ($status === 'failed') {
                    $pdo->prepare("UPDATE notifications SET status='failed' WHERE id=?")->execute([$notifId]);
                }
                flash_set('warning', $result['message']);
                if (!$result['configured']) {
                    flash_set('success', 'Taslak kaydedildi (FCM yapılandırılmamış).');
                }
            }
        } else {
            flash_set('success', 'Bildirim taslağı kaydedildi.');
        }

        redirect(admin_url('notifications.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'title' => '', 'body' => '', 'image' => null,
        'target_type' => 'none', 'target_id' => null, 'target_url' => '', 'status' => 'draft',
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Bildirim bulunamadı.');
            redirect(admin_url('notifications.php'));
        }
        if ($item['status'] !== 'draft') {
            flash_set('error', 'Yalnızca taslak bildirimler düzenlenebilir.');
            redirect(admin_url('notifications.php'));
        }
    }

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Bildirim Düzenle' : 'Yeni Bildirim' ?></h1>
            <a href="<?= e(admin_url('notifications.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required maxlength="200" value="<?= e($item['title']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Metin *</label>
                    <textarea name="body" class="form-control" rows="4" required><?= e($item['body']) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Görsel</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($item['image'])): ?>
                        <div class="mt-2"><img src="<?= e(media_url($item['image'])) ?>" class="thumb-sm" alt=""></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hedef tipi</label>
                    <select name="target_type" class="form-select">
                        <?php foreach (['none' => 'Yok', 'news' => 'Haber', 'video' => 'Video', 'program' => 'Program', 'url' => 'URL'] as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $item['target_type'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hedef ID</label>
                    <input type="number" name="target_id" class="form-control" value="<?= e((string) ($item['target_id'] ?? '')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Hedef URL</label>
                    <input type="text" name="target_url" class="form-control" value="<?= e($item['target_url'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="action" value="save" class="btn btn-outline-secondary">Taslak Kaydet</button>
                <button type="submit" name="action" value="send" class="btn btn-accent" data-confirm-click="Bildirim tüm cihazlara gönderilsin mi?">Kaydet ve Gönder</button>
            </div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Bildirim Düzenle' : 'Yeni Bildirim', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Bildirimler</h1>
        <a href="<?= e(admin_url('notifications.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Başlık</th><th>Durum</th><th>Oluşturma</th><th>Gönderim</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="5" class="empty-state">Bildirim yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <strong><?= e($row['title']) ?></strong>
                        <div class="small text-muted text-truncate" style="max-width:360px;"><?= e($row['body']) ?></div>
                    </td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['created_at']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['sent_at'] ?? null) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <?php if ($row['status'] === 'draft'): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('notifications.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <?php endif; ?>
                            <form method="post" data-confirm="Bildirim silinsin mi?">
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
render('Bildirimler', ob_get_clean());
