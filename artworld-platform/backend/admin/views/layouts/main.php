<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $content */
/** @var array|null $admin */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */
/** @var string|null $flashWarning */

$appName = (string) (\App\Core\Config::get('APP_NAME', 'Art World Mobile'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?> Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(admin_url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <a href="<?= e(admin_url('index.php')) ?>">
                <span class="brand-mark">AW</span>
                <span class="brand-text">Art World</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Genel</div>
            <a class="nav-link <?= active_menu('index.php') ?>" href="<?= e(admin_url('index.php')) ?>">
                <i class="bi bi-speedometer2"></i> Panel
            </a>
            <div class="nav-section">İçerik</div>
            <a class="nav-link <?= active_menu('news.php') ?>" href="<?= e(admin_url('news.php')) ?>">
                <i class="bi bi-newspaper"></i> Haberler
            </a>
            <a class="nav-link <?= active_menu('breaking.php') ?>" href="<?= e(admin_url('breaking.php')) ?>">
                <i class="bi bi-lightning-charge"></i> Son Dakika
            </a>
            <a class="nav-link <?= active_menu('categories.php') ?>" href="<?= e(admin_url('categories.php')) ?>">
                <i class="bi bi-folder2"></i> Kategoriler
            </a>
            <a class="nav-link <?= active_menu('banners.php') ?>" href="<?= e(admin_url('banners.php')) ?>">
                <i class="bi bi-image"></i> Bannerlar
            </a>
            <div class="nav-section">Medya</div>
            <a class="nav-link <?= active_menu('videos.php') ?>" href="<?= e(admin_url('videos.php')) ?>">
                <i class="bi bi-play-btn"></i> Videolar
            </a>
            <a class="nav-link <?= active_menu('programs.php') ?>" href="<?= e(admin_url('programs.php')) ?>">
                <i class="bi bi-collection-play"></i> Programlar
            </a>
            <a class="nav-link <?= active_menu('episodes.php') ?>" href="<?= e(admin_url('episodes.php')) ?>">
                <i class="bi bi-film"></i> Bölümler
            </a>
            <a class="nav-link <?= active_menu('live.php') ?>" href="<?= e(admin_url('live.php')) ?>">
                <i class="bi bi-broadcast"></i> Canlı Yayın
            </a>
            <div class="nav-section">Sistem</div>
            <a class="nav-link <?= active_menu('notifications.php') ?>" href="<?= e(admin_url('notifications.php')) ?>">
                <i class="bi bi-bell"></i> Bildirimler
            </a>
            <a class="nav-link <?= active_menu('settings.php') ?>" href="<?= e(admin_url('settings.php')) ?>">
                <i class="bi bi-gear"></i> Ayarlar
            </a>
            <?php if (\App\Core\Auth::isSuperAdmin()): ?>
            <a class="nav-link <?= active_menu('admins.php') ?>" href="<?= e(admin_url('admins.php')) ?>">
                <i class="bi bi-people"></i> Yöneticiler
            </a>
            <?php endif; ?>
            <a class="nav-link <?= active_menu('system.php') ?>" href="<?= e(admin_url('system.php')) ?>">
                <i class="bi bi-hdd-rack"></i> Sistem Bilgisi
            </a>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="btn btn-link sidebar-toggle" id="sidebarToggle" aria-label="Menü">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title"><?= e($pageTitle) ?></div>
            <div class="topbar-user dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?= e($admin['name'] ?? 'Admin') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= e($admin['email'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= e(admin_url('logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Çıkış</a></li>
                </ul>
            </div>
        </header>

        <main class="admin-content">
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashWarning): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?= e($flashWarning) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(admin_url('assets/js/admin.js')) ?>"></script>
</body>
</html>
