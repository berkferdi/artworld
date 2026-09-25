<?php

declare(strict_types=1);

namespace Web\Controllers;

final class HomeController extends BaseController
{
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
        $videos = is_array($home['videos'] ?? null) ? $home['videos'] : ($home['latest_videos'] ?? []);
        $programs = is_array($home['programs'] ?? null) ? $home['programs'] : [];
        $live = is_array($home['live'] ?? null) ? $home['live'] : ($home['live_stream'] ?? null);
        $mostRead = is_array($home['most_read'] ?? null) ? $home['most_read'] : array_slice($latest, 0, 6);
        $selected = is_array($home['selected'] ?? null) ? $home['selected'] : array_slice($featured, 0, 4);
        $banners = is_array($home['banners'] ?? null) ? $home['banners'] : [];
        $settings = is_array($home['settings'] ?? null) ? $home['settings'] : $this->settings();

        // Prefer dedicated live endpoint so admin live URL drives web + mobile identically.
        $liveFromApi = $this->api->data('/live', [], null);
        if (is_array($liveFromApi) && !empty($liveFromApi['stream_url'])) {
            $live = $liveFromApi;
        }

        $galleries = $this->api->data('/galleries', ['per_page' => 4], []);
        $authors = $this->api->data('/authors', [], []);
        $interviews = $this->api->data('/interviews', ['per_page' => 5], []);

        $this->render('pages/home', [
            'title' => ($settings['app_name'] ?? 'Art World') . ' — Canlı Yayın & Haber',
            'settings' => $settings,
            'breaking' => is_array($breaking) ? $breaking : [],
            'hero' => $hero,
            'latest' => $latest,
            'featured' => $featured,
            'videos' => is_array($videos) ? $videos : [],
            'programs' => $programs,
            'live' => is_array($live) ? $live : null,
            'mostRead' => $mostRead,
            'selected' => $selected,
            'banners' => $banners,
            'galleries' => is_array($galleries) ? $galleries : [],
            'authors' => is_array($authors) ? $authors : [],
            'interviews' => is_array($interviews) ? $interviews : [],
            'bodyClass' => 'page-home',
        ]);
    }
}
