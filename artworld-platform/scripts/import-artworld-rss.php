#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Import news metadata from artworld.com.tr (RSS + category listings).
 * - Dedupes by source_url and slug
 * - Stores title, summary, image URL, category, published_at, source link
 * - Does NOT bulk-copy full article bodies (content stub + source attribution)
 *
 * Usage (on server):
 *   php scripts/import-artworld-rss.php
 */

$root = dirname(__DIR__);
require_once $root . '/backend/config/bootstrap.php';

use App\Core\Database;
use App\Core\Security;

$pdo = Database::connection();
$sourceName = 'Art World';
$base = 'https://www.artworld.com.tr';

$catMap = [
    'ANTALYA' => 'antalya',
    'GÜNDEM' => 'gundem',
    'GUNDEM' => 'gundem',
    'SPOR' => 'spor',
    'EKONOMİ' => 'ekonomi',
    'EKONOMI' => 'ekonomi',
    'KÜLTÜR SANAT' => 'kultur-sanat',
    'KULTUR SANAT' => 'kultur-sanat',
    'TEKNOLOJİ' => 'teknoloji',
    'TEKNOLOJI' => 'teknoloji',
    'ASAYİŞ' => 'asayis',
    'ASAYIS' => 'asayis',
    'SAĞLIK' => 'saglik',
    'SAGLIK' => 'saglik',
    'TURİZM' => 'turizm',
    'TURIZM' => 'turizm',
];

$catPages = [
    'antalya' => '/antalya-haberleri',
    'gundem' => '/gundem-haberleri',
    'spor' => '/spor-haberleri',
    'ekonomi' => '/ekonomi-haberleri',
    'kultur-sanat' => '/kultur-sanat-haberleri',
    'teknoloji' => '/teknoloji-haberleri',
    'asayis' => '/asayis-haberleri',
    'saglik' => '/saglik-haberleri',
    'turizm' => '/turizm-haberleri',
];

function http_get(string $url): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'ArtWorldPlatformImporter/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        throw new RuntimeException("HTTP {$code} for {$url}");
    }
    return (string) $body;
}

function ensure_category(PDO $pdo, string $slug, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare(
        'INSERT INTO categories (name, slug, description, sort_order, status, created_at, updated_at)
         VALUES (?, ?, ?, 50, \'active\', UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    )->execute([$name, $slug, $name . ' haberleri']);
    return (int) $pdo->lastInsertId();
}

function slug_from_url(string $url): string
{
    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $base = basename($path, '.html');
    $base = preg_replace('/^haber-/', '', $base) ?? $base;
    $base = preg_replace('/-\d+$/', '', $base) ?? $base;
    $slug = Security::slugify($base);
    return $slug !== '' ? $slug : ('haber-' . substr(sha1($url), 0, 10));
}

function unique_news_slug(PDO $pdo, string $slug): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM news WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

/** @return list<array{title:string,url:string,summary:?string,image:?string,category:string,published_at:?string}> */
function parse_rss(string $xml, array $catMap): array
{
    $items = [];
    $doc = new DOMDocument();
    @$doc->loadXML($xml);
    foreach ($doc->getElementsByTagName('item') as $item) {
        $title = trim($item->getElementsByTagName('title')->item(0)?->textContent ?? '');
        $link = trim($item->getElementsByTagName('link')->item(0)?->textContent ?? '');
        $desc = trim($item->getElementsByTagName('description')->item(0)?->textContent ?? '');
        $pub = trim($item->getElementsByTagName('pubDate')->item(0)?->textContent ?? '');
        $cat = trim($item->getElementsByTagName('category')->item(0)?->textContent ?? 'GÜNDEM');
        $image = null;
        foreach ($item->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content') as $media) {
            $u = $media->getAttribute('url');
            if ($u !== '') {
                $image = $u;
                break;
            }
        }
        if ($image === null) {
            foreach ($item->getElementsByTagName('enclosure') as $enc) {
                $u = $enc->getAttribute('url');
                if ($u !== '') {
                    $image = $u;
                    break;
                }
            }
        }
        if ($title === '' || $link === '') {
            continue;
        }
        $published = null;
        if ($pub !== '') {
            try {
                $published = (new DateTimeImmutable($pub))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            } catch (Throwable) {
                $published = gmdate('Y-m-d H:i:s');
            }
        }
        $slugKey = mb_strtoupper($cat);
        $items[] = [
            'title' => html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url' => $link,
            'summary' => html_entity_decode(strip_tags($desc), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'image' => $image ? preg_replace('#^http://#i', 'https://', $image) : null,
            'category' => $catMap[$slugKey] ?? 'gundem',
            'published_at' => $published,
        ];
    }
    return $items;
}

/** @return list<array{title:string,url:string,summary:?string,image:?string,category:string,published_at:?string}> */
function parse_category_page(string $html, string $catSlug, string $base): array
{
    $items = [];
    if (!preg_match_all(
        '#<a[^>]+href="(haber-[^"]+\.html)"[^>]*>\s*<img[^>]+alt="([^"]+)"[^>]+data-src="(https://www\.artworld\.com\.tr/images/haber/[^"]+)"#iu',
        $html,
        $m,
        PREG_SET_ORDER
    )) {
        // looser: any img with data-src near haber link
        if (!preg_match_all(
            '#href="(haber-[^"]+\.html)"[^>]*>.*?data-src="(https://www\.artworld\.com\.tr/images/haber/[^"]+)"[^>]*alt="([^"]*)"#ius',
            $html,
            $m2,
            PREG_SET_ORDER
        )) {
            preg_match_all('#href="(haber-[^"]+\.html)"#i', $html, $hrefs);
            preg_match_all('#data-src="(https://www\.artworld\.com\.tr/images/haber/[^"]+)"#i', $html, $imgs);
            preg_match_all('#alt="([^"]{10,})"#u', $html, $alts);
            $hrefs = array_values(array_unique($hrefs[1] ?? []));
            $imgs = $imgs[1] ?? [];
            $alts = $alts[1] ?? [];
            foreach ($hrefs as $i => $h) {
                $title = html_entity_decode($alts[$i] ?? preg_replace('/-\d+\.html$/', '', $h) ?? $h, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $title = trim(preg_replace('/^haber-/', '', str_replace('-', ' ', $title)) ?? $title);
                $items[] = [
                    'title' => mb_convert_case($title, MB_CASE_TITLE, 'UTF-8'),
                    'url' => rtrim($base, '/') . '/' . ltrim($h, '/'),
                    'summary' => null,
                    'image' => isset($imgs[$i]) ? preg_replace('#^http://#i', 'https://', $imgs[$i]) : null,
                    'category' => $catSlug,
                    'published_at' => gmdate('Y-m-d H:i:s', time() - ($i * 3600)),
                ];
            }
            return $items;
        }
        foreach ($m2 as $row) {
            $items[] = [
                'title' => html_entity_decode($row[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'url' => rtrim($base, '/') . '/' . $row[1],
                'summary' => null,
                'image' => preg_replace('#^http://#i', 'https://', $row[2]),
                'category' => $catSlug,
                'published_at' => gmdate('Y-m-d H:i:s'),
            ];
        }
        return $items;
    }
    foreach ($m as $row) {
        $items[] = [
            'title' => html_entity_decode($row[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url' => rtrim($base, '/') . '/' . $row[1],
            'summary' => null,
            'image' => preg_replace('#^http://#i', 'https://', $row[3]),
            'category' => $catSlug,
            'published_at' => gmdate('Y-m-d H:i:s'),
        ];
    }
    return $items;
}

function upsert_news(PDO $pdo, array $item, string $sourceName): string
{
    $url = $item['url'];
    $check = $pdo->prepare('SELECT id FROM news WHERE source_url = ? LIMIT 1');
    $check->execute([$url]);
    if ($check->fetchColumn()) {
        return 'skip';
    }

    $slug = unique_news_slug($pdo, slug_from_url($url));
    // also skip if same slug already (demo collision)
    $catId = ensure_category($pdo, $item['category'], mb_convert_case(str_replace('-', ' ', $item['category']), MB_CASE_TITLE, 'UTF-8'));
    $summary = $item['summary'] ?: truncate_local((string) $item['title'], 180);
    $content = '<p>' . htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
        . '<p><em>Kaynak: <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer" target="_blank">'
        . htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8') . '</a></em></p>';
    $image = $item['image'];
    $published = $item['published_at'] ?: gmdate('Y-m-d H:i:s');

    $pdo->prepare(
        'INSERT INTO news (category_id, title, slug, summary, content, cover_image, author, source_url, source_name,
          is_featured, is_breaking, manset_order, status, published_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NULL, \'published\', ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    )->execute([
        $catId,
        $item['title'],
        $slug,
        $summary,
        $content,
        $image,
        $sourceName,
        $url,
        $sourceName,
        $published,
    ]);
    return 'insert';
}

function truncate_local(string $text, int $limit): string
{
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

echo "=== Art World import start ===\n";

// Ensure columns exist (best-effort if migration not yet applied)
try {
    $pdo->query('SELECT source_url, manset_order FROM news LIMIT 1');
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: run migration 004_homepage_density.sql first\n");
    exit(1);
}

$collected = [];
try {
    $rss = http_get($base . '/rss.xml');
    foreach (parse_rss($rss, $catMap) as $it) {
        $collected[$it['url']] = $it;
    }
    echo "RSS items: " . count($collected) . "\n";
} catch (Throwable $e) {
    echo "RSS fail: {$e->getMessage()}\n";
}

foreach ($catPages as $slug => $path) {
    try {
        $html = http_get($base . $path);
        $parsed = parse_category_page($html, $slug, $base);
        foreach ($parsed as $it) {
            if (!isset($collected[$it['url']])) {
                $collected[$it['url']] = $it;
            } else {
                // fill missing image/title
                if (empty($collected[$it['url']]['image']) && !empty($it['image'])) {
                    $collected[$it['url']]['image'] = $it['image'];
                }
                if (($collected[$it['url']]['category'] ?? '') === 'gundem' && $slug !== 'gundem') {
                    $collected[$it['url']]['category'] = $slug;
                }
            }
        }
        echo "Category {$slug}: +" . count($parsed) . "\n";
    } catch (Throwable $e) {
        echo "Category {$slug} fail: {$e->getMessage()}\n";
    }
}

$inserted = 0;
$skipped = 0;
foreach ($collected as $item) {
    try {
        $r = upsert_news($pdo, $item, $sourceName);
        if ($r === 'insert') {
            $inserted++;
        } else {
            $skipped++;
        }
    } catch (Throwable $e) {
        echo "Upsert fail {$item['url']}: {$e->getMessage()}\n";
    }
}

// Mark top items as featured / manset / breaking for density
$top = $pdo->query(
    "SELECT id FROM news WHERE status='published' ORDER BY published_at DESC LIMIT 16"
)->fetchAll(PDO::FETCH_COLUMN);
$order = 1;
foreach ($top as $id) {
    $pdo->prepare('UPDATE news SET is_featured = 1, manset_order = ? WHERE id = ?')->execute([$order, (int) $id]);
    $order++;
}
if ($top !== []) {
    $pdo->prepare('UPDATE news SET is_breaking = 1 WHERE id = ?')->execute([(int) $top[0]]);
}

// Seed dual sliders from latest news covers if empty
foreach (['home_slider_1' => 0, 'home_slider_2' => 6] as $pos => $offset) {
    $cnt = (int) $pdo->prepare("SELECT COUNT(*) FROM banners WHERE position = ? AND status='active'")->execute([$pos]) ?: 0;
    $check = $pdo->prepare("SELECT COUNT(*) FROM banners WHERE position = ?");
    $check->execute([$pos]);
    $cnt = (int) $check->fetchColumn();
    if ($cnt > 0) {
        continue;
    }
    $newsRows = $pdo->query(
        "SELECT id, title, cover_image FROM news WHERE status='published' AND cover_image IS NOT NULL
         ORDER BY published_at DESC LIMIT 12"
    )->fetchAll();
    $slice = array_slice($newsRows, $offset, 6);
    $sort = 1;
    foreach ($slice as $n) {
        if (empty($n['cover_image'])) {
            continue;
        }
        $pdo->prepare(
            'INSERT INTO banners (title, image, target_type, target_id, target_url, position, sort_order, status, created_at, updated_at)
             VALUES (?, ?, \'news\', ?, NULL, ?, ?, \'active\', UTC_TIMESTAMP(), UTC_TIMESTAMP())'
        )->execute([$n['title'], $n['cover_image'], (int) $n['id'], $pos, $sort]);
        $sort++;
    }
    echo "Seeded banners for {$pos}: " . count($slice) . "\n";
}

// Fake some views for most-read
$viewNews = $pdo->query("SELECT id FROM news WHERE status='published' ORDER BY published_at DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
foreach ($viewNews as $i => $nid) {
    $times = 20 - $i;
    for ($v = 0; $v < $times; $v++) {
        try {
            $pdo->prepare('INSERT INTO news_views (news_id, device_uuid, ip_hash, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())')
                ->execute([(int) $nid, 'import-' . $nid . '-' . $v, hash('sha256', 'import' . $nid . $v)]);
        } catch (Throwable) {
            break;
        }
    }
}

echo "Inserted: {$inserted}, skipped: {$skipped}, total candidates: " . count($collected) . "\n";
echo "=== done ===\n";
