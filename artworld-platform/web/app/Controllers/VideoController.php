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

        $sourceType = (string) ($video['source_type'] ?? '');
        $playback = (string) ($video['playback_url'] ?? $video['video_url'] ?? '');
        $embed = null;
        if ($sourceType === 'youtube' || ($video['video_type'] ?? '') === 'youtube') {
            $embed = youtube_embed_url($playback);
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $video['title'] ?? '',
            'description' => $video['description'] ?? '',
            'thumbnailUrl' => $video['thumbnail'] ?? null,
            'uploadDate' => $video['published_at'] ?? null,
            'contentUrl' => $sourceType === 'youtube' ? null : $playback,
            'embedUrl' => $embed,
        ];

        $this->render('pages/video-show', [
            'title' => ($video['title'] ?? 'Video') . ' | Art World',
            'metaDescription' => truncate((string) ($video['description'] ?? ''), 160),
            'ogImage' => (string) ($video['thumbnail'] ?? ''),
            'canonical' => absolute_url(video_url((string) $video['slug'])),
            'jsonLd' => $jsonLd,
            'video' => $video,
            'embed' => $embed,
            'related' => is_array($video['related'] ?? null) ? $video['related'] : [],
            'bodyClass' => 'page-video',
        ]);
    }
}
