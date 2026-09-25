<?php

declare(strict_types=1);

namespace Web\Controllers;

final class VideoController extends BaseController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $response = $this->api->get('/videos', ['page' => $page, 'per_page' => 24]);
        $videos = [];
        $meta = [];
        if (is_array($response) && ($response['success'] ?? false) === true) {
            $videos = is_array($response['data'] ?? null) ? $response['data'] : [];
            $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        }

        $this->render('pages/videos', [
            'title' => 'Videolar | Art World',
            'metaDescription' => 'Art World TV video arşivi',
            'videos' => $videos,
            'meta' => $meta,
            'bodyClass' => 'page-videos',
        ]);
    }

    public function show(string $slug): void
    {
        $video = $this->api->data('/videos/' . rawurlencode($slug), [], null);
        if (!is_array($video) || empty($video['slug'])) {
            $this->notFound('Video bulunamadı');
            return;
        }

        $embed = null;
        if (($video['video_type'] ?? '') === 'youtube') {
            $embed = youtube_embed_url((string) ($video['video_url'] ?? ''));
        }

        $this->render('pages/video-show', [
            'title' => ($video['title'] ?? 'Video') . ' | Art World',
            'metaDescription' => truncate((string) ($video['description'] ?? ''), 160),
            'ogImage' => (string) ($video['thumbnail'] ?? ''),
            'canonical' => absolute_url(video_url((string) $video['slug'])),
            'video' => $video,
            'embed' => $embed,
            'related' => is_array($video['related'] ?? null) ? $video['related'] : [],
            'bodyClass' => 'page-video',
        ]);
    }
}
