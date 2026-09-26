<?php

declare(strict_types=1);

namespace Web\Controllers;

final class HomeController extends BaseController
{
    private const CATEGORY_BLOCKS = [
        ['slug' => 'gundem', 'name' => 'Gündem'],
        ['slug' => 'spor', 'name' => 'Spor'],
        ['slug' => 'ekonomi', 'name' => 'Ekonomi'],
        ['slug' => 'kultur-sanat', 'name' => 'Kültür Sanat'],
        ['slug' => 'teknoloji', 'name' => 'Teknoloji'],
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
        $hero = is_array($home['hero'] ?? null) ? $home['hero'] : array_slice($featured, 0, 5);
        if ($hero === [] && $featured !== []) {
            $hero = array_slice($featured, 0, 5);
        }
        if ($hero === [] && $latest !== []) {
            $hero = array_slice($latest, 0, 5);
        }

        $breaking = is_array($home['breaking'] ?? null) ? $home['breaking'] : ($home['breaking_news'] ?? []);
        if (!is_array($breaking) || $breaking === []) {
            // Fallback: use latest headlines so ticker never layout-shifts empty
            $breaking = array_map(static function (array $n): array {
                return [
                    'title' => $n['title'] ?? '',
                    'news_slug' => $n['slug'] ?? null,
                    'target_url' => null,
                ];
            }, array_slice($latest, 0, 8));
        }

        $videos = is_array($home['videos'] ?? null) ? $home['videos'] : ($home['latest_videos'] ?? []);
        $programs = is_array($home['programs'] ?? null) ? $home['programs'] : [];
        $live = is_array($home['live'] ?? null) ? $home['live'] : ($home['live_stream'] ?? null);
        $mostRead = is_array($home['most_read'] ?? null) ? $home['most_read'] : array_slice($latest, 0, 6);
        $selected = is_array($home['selected'] ?? null) ? $home['selected'] : array_slice($featured, 0, 4);
        $banners = is_array($home['banners'] ?? null) ? $home['banners'] : [];
        $settings = is_array($home['settings'] ?? null) ? $home['settings'] : $this->settings();

        $liveFromApi = $this->api->data('/live', [], null);
        if (is_array($liveFromApi) && !empty($liveFromApi['stream_url'])) {
            $live = $liveFromApi;
        }

        $galleries = $this->api->data('/galleries', ['per_page' => 4], []);
        $authors = $this->api->data('/authors', [], []);
        $interviews = $this->api->data('/interviews', ['per_page' => 5], []);

        // Hero slider: featured/hero set
        $slider = array_slice($hero, 0, 6);
        $sliderIds = array_map(static fn(array $n): int => (int) ($n['id'] ?? 0), $slider);

        // Second rail: prefer selected/latest not already in slider
        $railSource = $selected !== [] ? $selected : $latest;
        $rail = [];
        foreach ($railSource as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && in_array($id, $sliderIds, true)) {
                continue;
            }
            $rail[] = $item;
            if (count($rail) >= 8) {
                break;
            }
        }
        if ($rail === []) {
            // Still empty (small dataset): use later latest items offset from slider
            $rail = array_slice($latest, count($slider), 8);
        }
        if ($rail === [] && $latest !== []) {
            $rail = array_slice(array_reverse($latest), 0, min(6, count($latest)));
        }

        // Featured block: first from featured/hero + next side cards (avoid pure duplicates when possible)
        $featuredMain = $featured[0] ?? ($slider[0] ?? null);
        $featuredSide = [];
        $usedIds = [(int) ($featuredMain['id'] ?? 0)];
        foreach (array_merge(array_slice($featured, 1), $latest) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0 && in_array($id, $usedIds, true)) {
                continue;
            }
            $featuredSide[] = $item;
            $usedIds[] = $id;
            if (count($featuredSide) >= 4) {
                break;
            }
        }

        $categoryBlocks = $this->loadCategoryBlocks();

        $this->render('pages/home', [
            'title' => ($settings['app_name'] ?? 'Art World') . ' — Canlı Yayın & Haber',
            'settings' => $settings,
            'breaking' => is_array($breaking) ? $breaking : [],
            'slider' => $slider,
            'rail' => $rail,
            'featuredMain' => is_array($featuredMain) ? $featuredMain : null,
            'featuredSide' => $featuredSide,
            'categoryBlocks' => $categoryBlocks,
            'hero' => $hero,
            'latest' => $latest,
            'featured' => $featured,
            'videos' => is_array($videos) ? $videos : [],
            'programs' => $programs,
            'live' => is_array($live) ? $live : null,
            'mostRead' => is_array($mostRead) ? $mostRead : [],
            'selected' => $selected,
            'banners' => $banners,
            'galleries' => is_array($galleries) ? $galleries : [],
            'authors' => is_array($authors) ? $authors : [],
            'interviews' => is_array($interviews) ? $interviews : [],
            'bodyClass' => 'page-home',
        ]);
    }

    /** @return list<array{slug:string,name:string,items:list<array<string,mixed>>}> */
    private function loadCategoryBlocks(): array
    {
        $blocks = [];
        foreach (self::CATEGORY_BLOCKS as $cat) {
            $response = $this->api->get('/categories/' . rawurlencode($cat['slug']) . '/news', [
                'page' => 1,
                'per_page' => 5,
            ]);
            $items = [];
            if (is_array($response) && ($response['success'] ?? false) === true) {
                $data = is_array($response['data'] ?? null) ? $response['data'] : [];
                $news = $data['news'] ?? null;
                if (is_array($news)) {
                    $items = $news;
                }
                $name = (string) ($data['category']['name'] ?? $cat['name']);
            } else {
                $name = $cat['name'];
            }
            $blocks[] = [
                'slug' => $cat['slug'],
                'name' => $name,
                'items' => $items,
            ];
        }
        return $blocks;
    }
}
