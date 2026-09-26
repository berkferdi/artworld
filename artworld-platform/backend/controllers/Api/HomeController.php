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

        $featured = $news->featured(10);
        $hero = array_slice($featured, 0, 5);
        $selected = $featured;

        $mostRead = $news->list(['sort' => 'popular'], 1, 10);
        if (($mostRead['meta']['total'] ?? 0) === 0) {
            $mostRead = $news->list(['sort' => 'latest'], 1, 10);
        }

        Response::success([
            'breaking' => $breaking->active(),
            'hero' => $hero,
            'latest_news' => $news->latest(12),
            'featured_news' => $featured,
            'most_read' => $mostRead['items'],
            'selected' => $selected,
            'videos' => $videos->latest(8),
            'programs' => $programs->allActive(12),
            'live' => $live->active(),
            'banners' => $banners->active('home_hero'),
            'categories' => $categories->allActive(),
            'settings' => $settings,
        ], 'Web ana sayfa verileri');
    }
}
