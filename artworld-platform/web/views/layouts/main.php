<?php
/** @var string $content */
/** @var string $title */
/** @var string $metaDescription */
/** @var string $canonical */
/** @var string $ogImage */
/** @var string $ogType */
/** @var mixed $jsonLd */
/** @var array $settings */
/** @var array $categories */
/** @var array $menuItems */
/** @var string $siteName */
/** @var string $siteTagline */
/** @var string|null $bodyClass */
$brandLogo = asset('/assets/images/branding/logo-horizontal.svg');
$apiLogo = (string) ($settings['app_logo'] ?? '');
// Prefer same-origin branding for header; keep API logo only if absolute https and not a missing demo path pattern handled server-side.
$logo = $brandLogo;
if ($apiLogo !== '' && str_starts_with($apiLogo, 'https://') && !str_contains($apiLogo, '/images/demo/logo.png')) {
    $logo = $apiLogo;
} elseif ($apiLogo !== '' && str_starts_with($apiLogo, 'https://api.') && str_contains($apiLogo, '/images/')) {
    // API-hosted media OK over HTTPS
    $logo = $apiLogo;
}
// Always use same-origin branding as default reliable logo
$logo = $brandLogo;
$favicon = asset('/assets/images/branding/favicon.svg');
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $siteName) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <link rel="canonical" href="<?= e($canonical ?? absolute_url('/')) ?>">
    <meta property="og:title" content="<?= e($title ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <meta property="og:url" content="<?= e($canonical ?? '') ?>">
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= e($favicon) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/theme.css')) ?>">
    <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <?php \Web\Core\View::partial('partials/header', compact('settings', 'categories', 'menuItems', 'siteName', 'siteTagline', 'logo', 'bodyClass')); ?>
    <main id="main" class="site-main">
        <?= $content ?>
    </main>
    <?php \Web\Core\View::partial('partials/footer', compact('settings', 'siteName', 'siteTagline')); ?>
    <script src="<?= e(asset('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
