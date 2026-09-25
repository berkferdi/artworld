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

        $this->render('pages/news-show', [
            'title' => ($news['title'] ?? 'Haber') . ' | Art World',
            'metaDescription' => truncate((string) ($news['summary'] ?? ''), 160),
            'ogImage' => (string) ($news['cover_image'] ?? ''),
            'ogType' => 'article',
            'canonical' => absolute_url(news_url((string) $news['slug'])),
            'jsonLd' => $jsonLd,
            'news' => $news,
            'related' => is_array($news['related'] ?? null) ? $news['related'] : [],
            'bodyClass' => 'page-news',
        ]);
    }
}
