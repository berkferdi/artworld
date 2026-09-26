<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Media;
use App\Core\Security;
use App\Core\Session;

/**
 * Escape HTML for safe output.
 */
function e(?string $value): string
{
    return Security::escape($value);
}

/**
 * CSRF hidden input field.
 */
function csrf_field(): string
{
    $name = (string) Config::get('CSRF_TOKEN_NAME', '_csrf_token');
    $token = Session::csrfToken();
    return '<input type="hidden" name="' . e($name) . '" value="' . e($token) . '">';
}

/**
 * CSRF token value.
 */
function csrf_token(): string
{
    return Session::csrfToken();
}

/**
 * CSRF POST field name.
 */
function csrf_name(): string
{
    return (string) Config::get('CSRF_TOKEN_NAME', '_csrf_token');
}

/**
 * Validate CSRF from request; exit with flash on failure.
 */
function require_csrf(): void
{
    $token = $_POST[csrf_name()] ?? null;
    if (!Session::validateCsrf(is_string($token) ? $token : null)) {
        Session::flash('error', 'Geçersiz güvenlik tokenı. Lütfen formu yeniden gönderin.');
        redirect(admin_url(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php')));
    }
}

/**
 * Build admin-relative URL.
 */
function admin_url(string $path = '', array $query = []): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');
    $path = ltrim($path, '/');
    $url = $base . ($path !== '' ? '/' . $path : '/index.php');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

/**
 * Absolute media/display URL helper.
 */
function media_url(?string $path): string
{
    return (string) (Media::url($path) ?? '');
}

/**
 * Redirect and exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Flash set helper.
 */
function flash_set(string $type, string $message): void
{
    Session::flash($type, $message);
}

/**
 * Get and clear flash message.
 */
function flash_get(string $key): ?string
{
    $val = Session::flash($key);
    return is_string($val) ? $val : null;
}

/**
 * Active menu class helper.
 */
function active_menu(string|array $pages, string $class = 'active'): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $pages = (array) $pages;
    return in_array($current, $pages, true) ? $class : '';
}

/**
 * Ensure unique slug in a table.
 */
function unique_slug(string $table, string $slug, ?int $excludeId = null, string $column = 'slug', ?string $extraWhere = null, array $extraParams = []): string
{
    $pdo = Database::connection();
    $base = $slug;
    $i = 0;
    do {
        $candidate = $i === 0 ? $base : $base . '-' . $i;
        $sql = "SELECT id FROM {$table} WHERE {$column} = ?";
        $params = [$candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        if ($extraWhere !== null) {
            $sql .= ' AND ' . $extraWhere;
            $params = array_merge($params, $extraParams);
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = (bool) $stmt->fetch();
        if (!$exists) {
            return $candidate;
        }
        $i++;
    } while ($i < 200);

    return $base . '-' . time();
}

/**
 * Render layout with content.
 *
 * @param array<string, mixed> $data
 */
function render(string $title, string $content, array $data = []): void
{
    $pageTitle = $title;
    $admin = Auth::user();
    $flashSuccess = flash_get('success');
    $flashError = flash_get('error');
    $flashWarning = flash_get('warning');
    extract($data, EXTR_SKIP);
    require __DIR__ . '/views/layouts/main.php';
}

/**
 * Render a partial view file into a string.
 *
 * @param array<string, mixed> $data
 */
function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $name . '.php';
    return (string) ob_get_clean();
}

/**
 * POST action string.
 */
function post_action(): string
{
    return (string) ($_POST['action'] ?? $_GET['action'] ?? 'list');
}

/**
 * GET action string.
 */
function get_action(): string
{
    return (string) ($_GET['action'] ?? 'list');
}

/**
 * Require integer ID from GET/POST.
 */
function request_id(string $key = 'id'): ?int
{
    $val = $_POST[$key] ?? $_GET[$key] ?? null;
    if ($val === null || $val === '') {
        return null;
    }
    return (int) $val;
}

/**
 * Checkbox to tinyint.
 */
function checkbox(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

/**
 * Empty string to null.
 */
function null_if_empty(?string $value): ?string
{
    $value = $value !== null ? trim($value) : null;
    return $value === '' ? null : $value;
}

/**
 * Bootstrap + session start for protected pages.
 */
function admin_bootstrap(bool $requireAuth = true): void
{
    require_once dirname(__DIR__) . '/config/bootstrap.php';
    Session::start();
    if ($requireAuth) {
        Auth::requireLogin();
    }
}

/**
 * Status badge HTML.
 */
function status_badge(?string $status): string
{
    $map = [
        'active' => 'success',
        'inactive' => 'secondary',
        'published' => 'success',
        'draft' => 'warning',
        'archived' => 'secondary',
        'sent' => 'success',
        'failed' => 'danger',
    ];
    $labels = [
        'active' => 'Aktif',
        'inactive' => 'Pasif',
        'published' => 'Yayında',
        'draft' => 'Taslak',
        'archived' => 'Arşiv',
        'sent' => 'Gönderildi',
        'failed' => 'Başarısız',
    ];
    $s = (string) $status;
    $cls = $map[$s] ?? 'secondary';
    $label = $labels[$s] ?? $s;
    return '<span class="badge text-bg-' . e($cls) . '">' . e($label) . '</span>';
}

/**
 * Format UTC datetime for display (Europe/Istanbul friendly local label).
 */
function format_dt(?string $dt): string
{
    if ($dt === null || $dt === '') {
        return '—';
    }
    try {
        $d = new DateTimeImmutable($dt, new DateTimeZone('UTC'));
        return $d->setTimezone(new DateTimeZone('Europe/Istanbul'))->format('d.m.Y H:i');
    } catch (Throwable) {
        return e($dt);
    }
}
