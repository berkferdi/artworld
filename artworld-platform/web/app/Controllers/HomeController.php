<?php

declare(strict_types=1);

namespace Web\Controllers;

final class HomeController extends BaseController
{
    private const PRIMARY_CATS = [
        ['slug' => 'gundem', 'name' => 'Gündem'],
        ['slug' => 'spor', 'name' => 'Spor'],
        ['slug' => 'ekonomi', 'name' => 'Ekonomi'],
    ];

    private const SECONDARY_CATS = [
        ['slug' => 'kultur-sanat', 'name' => 'Kültür Sanat'],
        ['slug' => 'teknoloji', 'name' => 'Teknoloji'],
        ['slug' => 'antalya', 'name' => 'Antalya'],
        ['slug' => 'asayis', 'name' => 'Asayiş'],
        ['slug' => 'saglik', 'name' => 'Sağlık'],
        ['slug' => 'turizm', 'name' => 'Turizm'],
    ];

    public function index(): void
    {
        $home = $this->api->data('/home/web', [], null);
        if (!is_array($home)) {
            $home = $this->api->data('/home', [], []);
        }
        if (!is_array($home)) {
            $home = [];
        }

        $featured = is_array($home['featured_news'] ?? null) ? $home['featured_news'] : [];
        $latest = is_array($home['latest_news'] ?? null) ? $home['latest_news'] : [];
        $manset = is_array($home['manset_news'] ?? null) ? $home['manset_news'] : [];
        if ($manset === []) {
            $manset = $featured !== [] ? $featured : array_slice($latest, 0, 12);
        }

        $breaking = is_array($home['breaking'] ?? null) ? $home['breaking'] : ($home['breaking_news'] ?? []);
        if (!is_array($breaking) || $breaking === []) {
            $breaking = array_map(static function (array $n): array {
                return [
                    'title' => $n['title'] ?? '',
                    'news_slug' => $n['slug'] ?? null,
                    'target_url' => null,
                ];
            }, array_slice($latest, 0, 10));
        }

        $videos = is_array($home['videos'] ?? null) ? $home['videos'] : ($home['latest_videos'] ?? []);
        $programs = is_array($home['programs'] ?? null) ? $home['programs'] : [];
        $live = is_array($home['live'] ?? null) ? $home['live'] : ($home['live_stream'] ?? null);
        $mostRead = is_array($home['most_read'] ?? null) ? $home['most_read'] : array_slice($latest, 0, 8);
        $selected = is_array($home['selected'] ?? null) ? $home['selected'] : array_slice($featured, 0, 8);
        $settings = is_array($home['settings'] ?? null) ? $home['settings'] : $this->settings();

        $liveFromApi = $this->api->data('/live', [], null);
        if (is_array($liveFromApi) && !empty($liveFromApi['stream_url'])) {
            $live = $liveFromApi;
        }

        $slider1 = $this->normalizeSlides(is_array($home['slider1'] ?? null) ? $home['slider1'] : []);
        $slider2 = $this->normalizeSlides(is_array($home['slider2'] ?? null) ? $home['slider2'] : []);
        if ($slider1 === []) {
            $slider1 = $this->newsToSlides(array_slice($featured !== [] ? $featured : $latest, 0, 6));
        }
        if ($slider2 === []) {
            $slider2 = $this->newsToSlides(array_slice($latest, 3, 6));
        }

        $featuredMain = $featured[0] ?? ($manset[0] ?? ($latest[0] ?? null));
        $featuredSide = [];
        $used = [(int) ($featuredMain['id'] ?? 0)];
        foreach (array_merge(array_slice($featured, 1), $latest) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && in_array($id, $used, true)) {
                continue;
            }
            $featuredSide[] = $item;
            $used[] = $id;
            if (count($featuredSide) >= 5) {
                break;
            }
        }

        $categoryNews = is_array($home['category_news'] ?? null) ? $home['category_news'] : [];
        $primaryBlocks = $this->buildBlocks(self::PRIMARY_CATS, $categoryNews);
        $secondaryBlocks = $this->buildBlocks(self::SECONDARY_CATS, $categoryNews);
        // Drop empty secondary to avoid blank cards, keep at least ones with items
        $secondaryBlocks = array_values(array_filter(
            $secondaryBlocks,
            static fn(array $b): bool => ($b['items'] ?? []) !== []
        ));

        $this->render('pages/home', [
            'title' => ($settings['app_name'] ?? 'Art World') . ' — Canlı Yayın & Haber',
            'settings' => $settings,
            'breaking' => is_array($breaking) ? $breaking : [],
            'live' => is_array($live) ? $live : null,
            'slider1' => $slider1,
            'slider2' => $slider2,
            'featuredMain' => is_array($featuredMain) ? $featuredMain : null,
            'featuredSide' => $featuredSide,
            'manset' => $manset,
            'primaryBlocks' => $primaryBlocks,
            'secondaryBlocks' => $secondaryBlocks,
            'mostRead' => is_array($mostRead) ? $mostRead : [],
            'selected' => is_array($selected) ? $selected : [],
            'latest' => $latest,
            'videos' => is_array($videos) ? $videos : [],
            'programs' => is_array($programs) ? $programs : [],
            'bodyClass' => 'page-home',
        ]);
    }

    /**
     * @param list<array{slug:string,name:string}> $defs
     * @param array<string, mixed> $categoryNews
     * @return list<array{slug:string,name:string,items:list<array<string,mixed>>}>
     */
    private function buildBlocks(array $defs, array $categoryNews): array
    {
        $blocks = [];
        foreach ($defs as $cat) {
            $items = $categoryNews[$cat['slug']] ?? null;
            if (!is_array($items)) {
                $response = $this->api->get('/categories/' . rawurlencode($cat['slug']) . '/news', [
                    'page' => 1,
                    'per_page' => 6,
                ]);
                $items = [];
                if (is_array($response) && ($response['success'] ?? false) === true) {
                    $data = is_array($response['data'] ?? null) ? $response['data'] : [];
                    $items = is_array($data['news'] ?? null) ? $data['news'] : [];
                }
            }
            $blocks[] = [
                'slug' => $cat['slug'],
                'name' => $cat['name'],
                'items' => is_array($items) ? $items : [],
            ];
        }
        return $blocks;
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function normalizeSlides(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $href = '#';
            if (!empty($row['news_slug'])) {
                $href = news_url((string) $row['news_slug']);
            } elseif (($row['target_type'] ?? '') === 'news' && !empty($row['target_id'])) {
                // resolved later via slug if present; else API news id page not available — use url
                $href = !empty($row['target_url']) ? (string) $row['target_url'] : $href;
            } elseif (!empty($row['target_url'])) {
                $href = (string) $row['target_url'];
                if (str_starts_with($href, '/live')) {
                    $href = url('/canli-yayin');
                }
            } elseif (!empty($row['slug'])) {
                $href = news_url((string) $row['slug']);
            }

            $image = (string) ($row['image'] ?? $row['cover_image'] ?? '');
            if ($image === '') {
                continue;
            }
            $out[] = [
                'title' => (string) ($row['title'] ?? ''),
                'summary' => (string) ($row['summary'] ?? ''),
                'image' => $image,
                'href' => $href,
                'category' => is_array($row['category'] ?? null)
                    ? (string) ($row['category']['name'] ?? '')
                    : (string) ($row['category_name'] ?? ''),
            ];
        }
        return $out;
    }

    /** @param list<array<string,mixed>> $items @return list<array<string,mixed>> */
    private function newsToSlides(array $items): array
    {
        $mapped = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $mapped[] = [
                'title' => $item['title'] ?? '',
                'summary' => $item['summary'] ?? '',
                'image' => $item['cover_image'] ?? '',
                'news_slug' => $item['slug'] ?? null,
                'category' => $item['category'] ?? null,
            ];
        }
        return $this->normalizeSlides($mapped);
    }
}
