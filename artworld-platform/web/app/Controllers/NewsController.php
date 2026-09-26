<?php

declare(strict_types=1);

namespace Web\Controllers;

final class NewsController extends BaseController
{
    public function show(string $slug): void
    {
        $news = $this->api->data('/news/' . rawurlencode($slug), [], null);
        if (!is_array($news) || empty($news['slug'])) {
            $this->notFound('Haber bulunamadı');
            return;
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news['title'] ?? '',
            'description' => $news['summary'] ?? '',
            'image' => $news['cover_image'] ?? null,
            'datePublished' => $news['published_at'] ?? null,
            'author' => [
                '@type' => 'Person',
                'name' => $news['author'] ?? 'Art World',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Art World',
            ],
        ];

        $mostReadResp = $this->api->get('/news', ['sort' => 'popular', 'per_page' => 5]);
        $mostRead = [];
        if (is_array($mostReadResp) && ($mostReadResp['success'] ?? false) === true) {
            $data = $mostReadResp['data'] ?? [];
            $mostRead = is_array($data) ? (array_is_list($data) ? $data : (is_array($data['items'] ?? null) ? $data['items'] : [])) : [];
        }
        if ($mostRead === []) {
            $mostRead = is_array($news['related'] ?? null) ? array_slice($news['related'], 0, 5) : [];
        }

        $this->render('pages/news-show', [
            'title' => ($news['title'] ?? 'Haber') . ' | Art World',
            'metaDescription' => truncate((string) ($news['summary'] ?? ''), 160),
            'ogImage' => (string) ($news['cover_image'] ?? ''),
            'ogType' => 'article',
            'canonical' => absolute_url(news_url((string) $news['slug'])),
            'jsonLd' => $jsonLd,
            'news' => $news,
            'related' => is_array($news['related'] ?? null) ? $news['related'] : [],
            'mostRead' => $mostRead,
            'bodyClass' => 'page-news',
        ]);
    }
}
