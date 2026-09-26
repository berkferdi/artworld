<?php

declare(strict_types=1);

namespace Web\Controllers;

final class AuthorController extends BaseController
{
    public function index(): void
    {
        $authors = $this->api->data('/authors', [], []);
        $this->render('pages/authors', [
            'title' => 'Yazarlar | Art World',
            'metaDescription' => 'Art World yazarları',
            'authors' => is_array($authors) ? $authors : [],
            'bodyClass' => 'page-authors',
        ]);
    }

    public function show(string $slug): void
    {
        $author = $this->api->data('/authors/' . rawurlencode($slug), [], null);
        if (!is_array($author) || empty($author['slug'])) {
            $this->notFound('Yazar bulunamadı');
            return;
        }

        $this->render('pages/author-show', [
            'title' => ($author['name'] ?? 'Yazar') . ' | Art World',
            'metaDescription' => truncate((string) ($author['bio'] ?? ''), 160),
            'ogImage' => (string) ($author['photo'] ?? ''),
            'author' => $author,
            'news' => is_array($author['news'] ?? null) ? $author['news'] : [],
            'bodyClass' => 'page-author',
        ]);
    }
}
