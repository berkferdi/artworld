<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
admin_bootstrap(false);

use App\Core\Auth;
use App\Core\Session;

if (Auth::check()) {
    redirect(admin_url('index.php'));
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    $result = Auth::attempt($email, $password, $remember);
    if ($result['success']) {
        redirect(admin_url('index.php'));
    }
    $error = $result['message'] ?? 'Giriş başarısız.';
}

$appName = (string) (\App\Core\Config::get('APP_NAME', 'Art World Mobile'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Giriş | <?= e($appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(admin_url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-brand">
        <span class="brand-mark">AW</span>
        <div>
            <h1><?= e($appName) ?></h1>
            <div class="text-muted small">Yönetim Paneli</div>
        </div>
    </div>
    <p class="subtitle">Hesabınıza giriş yapın</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(admin_url('login.php')) ?>" autocomplete="on">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">E-posta</label>
            <input type="email" class="form-control" id="email" name="email" required
                   value="<?= e((string) ($_POST['email'] ?? '')) ?>" autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Şifre</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
            <label class="form-check-label" for="remember">Beni hatırla</label>
        </div>
        <button type="submit" class="btn btn-accent w-100">Giriş Yap</button>
    </form>
</div>
</body>
</html>
