<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Config;
use App\Core\Database;
use App\Core\Response;
use App\Models\CategoryModel;
use App\Models\GalleryModel;
use App\Models\PageModel;

final class SiteMapController
{
    public function index(): void
    {
        $base = rtrim((string) Config::get('WEB_URL', Config::get('APP_URL', '')), '/');
        $pdo = Database::connection();

        $urls = [];

        $urls[] = $this->entry($base, '/', 'home');

        $news = $pdo->query(
            "SELECT slug, published_at, updated_at FROM news WHERE status = 'published' ORDER BY published_at DESC LIMIT 5000"
        )->fetchAll();
        foreach ($news as $row) {
            $urls[] = $this->entry($base, '/haber/' . $row['slug'], 'news', $row['updated_at'] ?? $row['published_at']);
        }

        $videos = $pdo->query(
            "SELECT slug, published_at, updated_at FROM videos WHERE status = 'published' ORDER BY published_at DESC LIMIT 2000"
        )->fetchAll();
        foreach ($videos as $row) {
            $urls[] = $this->entry($base, '/video/' . $row['slug'], 'video', $row['updated_at'] ?? $row['published_at']);
        }

        $programs = $pdo->query(
            "SELECT slug, updated_at FROM programs WHERE status = 'active' ORDER BY title ASC LIMIT 500"
        )->fetchAll();
        foreach ($programs as $row) {
            $urls[] = $this->entry($base, '/program/' . $row['slug'], 'program', $row['updated_at'] ?? null);
        }

        foreach ((new CategoryModel())->allActive() as $cat) {
            $urls[] = $this->entry($base, '/kategori/' . $cat['slug'], 'category');
        }

        try {
            foreach ((new PageModel())->allPublishedSlugs() as $row) {
                $urls[] = $this->entry($base, '/sayfa/' . $row['slug'], 'page', $row['updated_at'] ?? null);
            }
        } catch (\Throwable) {
            // pages table may not exist yet
        }

        try {
            foreach ((new GalleryModel())->allPublishedSlugs() as $row) {
                $urls[] = $this->entry($base, '/galeri/' . $row['slug'], 'gallery', $row['updated_at'] ?? null);
            }
        } catch (\Throwable) {
            // galleries table may not exist yet
        }

        Response::success([
            'base_url' => $base,
            'urls' => $urls,
            'count' => count($urls),
        ], 'Site haritası');
    }

    private function entry(string $base, string $path, string $type, ?string $lastmod = null): array
    {
        return [
            'type' => $type,
            'path' => $path,
            'url' => $base !== '' ? $base . $path : $path,
            'lastmod' => $lastmod,
        ];
    }
}
