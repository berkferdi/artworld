<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap();

use App\Core\Database;

$pdo = Database::connection();
$action = get_action();
$id = request_id();

$newsOptions = $pdo->query("SELECT id, title FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 200")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $postAction = (string) ($_POST['action'] ?? '');

    if ($postAction === 'delete') {
        $pdo->prepare('DELETE FROM breaking_news WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash_set('success', 'Son dakika kaydı silindi.');
        redirect(admin_url('breaking.php'));
    }

    if ($postAction === 'save') {
        $editId = request_id();
        $title = trim((string) ($_POST['title'] ?? ''));
        $newsId = (int) ($_POST['news_id'] ?? 0) ?: null;
        $targetUrl = null_if_empty((string) ($_POST['target_url'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $startsAt = null_if_empty((string) ($_POST['starts_at'] ?? ''));
        $expiresAt = null_if_empty((string) ($_POST['expires_at'] ?? ''));

        if ($title === '') {
            flash_set('error', 'Başlık zorunludur.');
            redirect(admin_url('breaking.php', $editId ? ['action' => 'edit', 'id' => $editId] : ['action' => 'create']));
        }

        $toUtc = static function (?string $v): ?string {
            if ($v === null || $v === '') {
                return null;
            }
            try {
                return (new DateTimeImmutable($v))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return null;
            }
        };
        $startsAt = $toUtc($startsAt);
        $expiresAt = $toUtc($expiresAt);

        if ($editId) {
            $pdo->prepare(
                'UPDATE breaking_news SET news_id=?, title=?, target_url=?, sort_order=?, status=?, starts_at=?, expires_at=?, updated_at=UTC_TIMESTAMP() WHERE id=?'
            )->execute([$newsId, $title, $targetUrl, $sortOrder, $status, $startsAt, $expiresAt, $editId]);
            flash_set('success', 'Son dakika güncellendi.');
        } else {
            $pdo->prepare(
                'INSERT INTO breaking_news (news_id, title, target_url, sort_order, status, starts_at, expires_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
            )->execute([$newsId, $title, $targetUrl, $sortOrder, $status, $startsAt, $expiresAt]);
            flash_set('success', 'Son dakika oluşturuldu.');
        }
        redirect(admin_url('breaking.php'));
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => null, 'news_id' => null, 'title' => '', 'target_url' => '',
        'sort_order' => 0, 'status' => 'active', 'starts_at' => null, 'expires_at' => null,
    ];
    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM breaking_news WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) {
            flash_set('error', 'Kayıt bulunamadı.');
            redirect(admin_url('breaking.php'));
        }
    }

    $fmtLocal = static function (?string $v): string {
        if (!$v) {
            return '';
        }
        try {
            return (new DateTimeImmutable($v, new DateTimeZone('UTC')))
                ->setTimezone(new DateTimeZone('Europe/Istanbul'))
                ->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return '';
        }
    };

    ob_start();
    ?>
    <div class="panel">
        <div class="panel-header">
            <h1><?= $action === 'edit' ? 'Son Dakika Düzenle' : 'Yeni Son Dakika' ?></h1>
            <a href="<?= e(admin_url('breaking.php')) ?>" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($item['id']): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Başlık *</label>
                    <input type="text" name="title" class="form-control" required value="<?= e($item['title']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">İlişkili haber</label>
                    <select name="news_id" class="form-select">
                        <option value="">— Yok —</option>
                        <?php foreach ($newsOptions as $n): ?>
                            <option value="<?= (int) $n['id'] ?>" <?= (int) $item['news_id'] === (int) $n['id'] ? 'selected' : '' ?>><?= e($n['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hedef URL</label>
                    <input type="url" name="target_url" class="form-control" value="<?= e($item['target_url'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sıra</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int) $item['sort_order'] ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Başlangıç</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="<?= e($fmtLocal($item['starts_at'] ?? null)) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bitiş</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="<?= e($fmtLocal($item['expires_at'] ?? null)) ?>">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-accent">Kaydet</button></div>
        </form>
    </div>
    <?php
    render($action === 'edit' ? 'Son Dakika Düzenle' : 'Yeni Son Dakika', ob_get_clean());
    exit;
}

$rows = $pdo->query('SELECT b.*, n.title AS news_title FROM breaking_news b LEFT JOIN news n ON n.id = b.news_id ORDER BY b.sort_order, b.id DESC')->fetchAll();
ob_start();
?>
<div class="panel">
    <div class="panel-header">
        <h1>Son Dakika</h1>
        <a href="<?= e(admin_url('breaking.php', ['action' => 'create'])) ?>" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Yeni</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Başlık</th><th>Haber</th><th>Sıra</th><th>Durum</th><th>Tarih</th><th></th></tr></thead>
            <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="empty-state">Kayıt yok.</td></tr><?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td class="small text-muted"><?= e($row['news_title'] ?? '—') ?></td>
                    <td><?= (int) $row['sort_order'] ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td class="small text-muted"><?= format_dt($row['starts_at']) ?> → <?= format_dt($row['expires_at']) ?></td>
                    <td class="text-end">
                        <div class="action-btns justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(admin_url('breaking.php', ['action' => 'edit', 'id' => $row['id']])) ?>">Düzenle</a>
                            <form method="post" data-confirm="Silmek istediğinize emin misiniz?">
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
render('Son Dakika', ob_get_clean());
