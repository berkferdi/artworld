<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Models\BannerModel;
use App\Models\BreakingNewsModel;
use App\Models\CategoryModel;
use App\Models\LiveStreamModel;
use App\Models\NewsModel;
use App\Models\ProgramModel;
use App\Models\VideoModel;
use App\Services\SettingsService;

final class HomeController
{
    public function index(): void
    {
        $settings = (new SettingsService())->publicSettings();
        $news = new NewsModel();
        $videos = new VideoModel();
        $programs = new ProgramModel();
        $breaking = new BreakingNewsModel();
        $banners = new BannerModel();
        $live = new LiveStreamModel();

        Response::success([
            'settings' => $settings,
            'breaking_news' => $breaking->active(),
            'banners' => $banners->active('home_hero'),
            'featured_news' => $news->featured(8),
            'latest_news' => $news->latest(10),
            'featured_videos' => $videos->featured(6),
            'latest_videos' => $videos->latest(8),
            'programs' => $programs->allActive(12),
            'live_stream' => $live->active(),
        ], 'Ana sayfa verileri');
    }

    public function web(): void
    {
        $settings = (new SettingsService())->publicSettings();
        $news = new NewsModel();
        $videos = new VideoModel();
        $programs = new ProgramModel();
        $breaking = new BreakingNewsModel();
        $banners = new BannerModel();
        $live = new LiveStreamModel();
        $categories = new CategoryModel();

        $featured = $news->featured(12);
        $latest = $news->latest(24);
        $manset = $news->manset(16);

        $slider1 = $banners->active('home_slider_1');
        if ($slider1 === []) {
            $slider1 = $banners->active('home_hero');
        }
        $slider2 = $banners->active('home_slider_2');

        // Fallback slides from news when banner slots empty
        if ($slider1 === []) {
            $slider1 = $this->newsAsBannerSlides(array_slice($featured !== [] ? $featured : $latest, 0, 6));
        }
        if ($slider2 === []) {
            $pool = $latest;
            $used = array_map(static fn(array $s): string => (string) ($s['title'] ?? ''), $slider1);
            $rest = [];
            foreach ($pool as $item) {
                if (in_array((string) ($item['title'] ?? ''), $used, true)) {
                    continue;
                }
                $rest[] = $item;
                if (count($rest) >= 6) {
                    break;
                }
            }
            if ($rest === []) {
                $rest = array_slice(array_reverse($latest), 0, 6);
            }
            $slider2 = $this->newsAsBannerSlides($rest);
        }

        $mostRead = $news->list(['sort' => 'popular'], 1, 8);
        if (($mostRead['meta']['total'] ?? 0) === 0) {
            $mostRead = $news->list(['sort' => 'latest'], 1, 8);
        }

        // Selected: featured offset, else mid-latest
        $selected = array_slice($featured, 0, 8);
        if (count($selected) < 6) {
            $selected = array_slice($latest, 2, 8);
        }

        $primaryCats = ['gundem', 'spor', 'ekonomi'];
        $secondaryCats = ['kultur-sanat', 'teknoloji', 'antalya', 'asayis', 'saglik', 'turizm'];
        $categoryNews = [];
        foreach (array_merge($primaryCats, $secondaryCats) as $slug) {
            $categoryNews[$slug] = $news->byCategorySlug($slug, 6);
        }

        Response::success([
            'breaking' => $breaking->active(),
            'hero' => array_slice($featured, 0, 5),
            'latest_news' => $latest,
            'featured_news' => $featured,
            'manset_news' => $manset,
            'most_read' => $mostRead['items'],
            'selected' => $selected,
            'slider1' => $slider1,
            'slider2' => $slider2,
            'banners' => $banners->active('home_hero'),
            'category_news' => $categoryNews,
            'videos' => $videos->latest(8),
            'programs' => $programs->allActive(12),
            'live' => $live->active(),
            'categories' => $categories->allActive(),
            'settings' => $settings,
        ], 'Web ana sayfa verileri');
    }

    /** @param list<array<string,mixed>> $items */
    private function newsAsBannerSlides(array $items): array
    {
        $out = [];
        foreach ($items as $i => $item) {
            $out[] = [
                'id' => (int) ($item['id'] ?? 0),
                'title' => (string) ($item['title'] ?? ''),
                'image' => $item['cover_image'] ?? null,
                'summary' => $item['summary'] ?? null,
                'category' => $item['category'] ?? null,
                'target_type' => 'news',
                'target_id' => (int) ($item['id'] ?? 0),
                'target_url' => null,
                'news_slug' => $item['slug'] ?? null,
                'position' => 'news_fallback',
                'sort_order' => $i + 1,
            ];
        }
        return $out;
    }
}
