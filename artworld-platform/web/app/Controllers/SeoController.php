<?php

declare(strict_types=1);

namespace Web\Controllers;

use Web\Core\Config;

final class SeoController extends BaseController
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $host = rtrim((string) Config::get('CANONICAL_HOST', 'https://www.artworld.com.tr'), '/');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/\n\nSitemap: {$host}/sitemap.xml\nSitemap: {$host}/sitemap-news.xml\n";
    }

    public function sitemap(): void
    {
        $urls = [
            ['loc' => absolute_url('/'), 'changefreq' => 'hourly', 'priority' => '1.0'],
            ['loc' => absolute_url('/canli'), 'changefreq' => 'hourly', 'priority' => '0.9'],
            ['loc' => absolute_url('/video'), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => absolute_url('/programlar'), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => absolute_url('/foto-galeri'), 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => absolute_url('/yazarlar'), 'changefreq' => 'weekly', 'priority' => '0.5'],
            ['loc' => absolute_url('/roportajlar'), 'changefreq' => 'weekly', 'priority' => '0.5'],
            ['loc' => absolute_url('/arsiv'), 'changefreq' => 'daily', 'priority' => '0.6'],
            ['loc' => absolute_url('/iletisim'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => absolute_url('/hakkimizda'), 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];

        $map = $this->api->data('/site-map', [], null);
        if (is_array($map)) {
            foreach (['news', 'videos', 'programs', 'galleries', 'pages', 'categories'] as $key) {
                if (!isset($map[$key]) || !is_array($map[$key])) {
                    continue;
                }
                foreach ($map[$key] as $item) {
                    if (!is_array($item) || empty($item['url'])) {
                        continue;
                    }
                    $urls[] = [
                        'loc' => (string) $item['url'],
                        'lastmod' => (string) ($item['updated_at'] ?? ''),
                        'changefreq' => 'daily',
                        'priority' => '0.7',
                    ];
                }
            }
        } else {
            $news = $this->api->data('/news', ['per_page' => 50], []);
            if (is_array($news)) {
                foreach ($news as $item) {
                    if (!empty($item['slug'])) {
                        $urls[] = [
                            'loc' => absolute_url(news_url((string) $item['slug'])),
                            'lastmod' => (string) ($item['published_at'] ?? ''),
                            'changefreq' => 'daily',
                            'priority' => '0.8',
                        ];
                    }
                }
            }
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $lm = date('c', strtotime($u['lastmod']) ?: time());
                echo '    <lastmod>' . htmlspecialchars($lm, ENT_XML1) . "</lastmod>\n";
            }
            echo '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1) . "</changefreq>\n";
            echo '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1) . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
    }

    public function sitemapNews(): void
    {
        $news = $this->api->data('/news', ['per_page' => 50], []);
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
        if (is_array($news)) {
            foreach ($news as $item) {
                if (empty($item['slug']) || empty($item['title'])) {
                    continue;
                }
                $pub = !empty($item['published_at']) ? date('Y-m-d', strtotime((string) $item['published_at']) ?: time()) : date('Y-m-d');
                echo "  <url>\n";
                echo '    <loc>' . htmlspecialchars(absolute_url(news_url((string) $item['slug'])), ENT_XML1) . "</loc>\n";
                echo "    <news:news>\n";
                echo "      <news:publication>\n";
                echo "        <news:name>Art World</news:name>\n";
                echo "        <news:language>tr</news:language>\n";
                echo "      </news:publication>\n";
                echo '      <news:publication_date>' . htmlspecialchars($pub, ENT_XML1) . "</news:publication_date>\n";
                echo '      <news:title>' . htmlspecialchars((string) $item['title'], ENT_XML1) . "</news:title>\n";
                echo "    </news:news>\n";
                echo "  </url>\n";
            }
        }
        echo '</urlset>';
    }
}
