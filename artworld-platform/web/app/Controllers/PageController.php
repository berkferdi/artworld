<?php

declare(strict_types=1);

namespace Web\Controllers;

final class PageController extends BaseController
{
    public function show(string $slug): void
    {
        $page = $this->api->data('/pages/' . rawurlencode($slug), [], null);
        if (!is_array($page) || empty($page['slug'])) {
            $this->renderFallback($slug);
            return;
        }

        $this->render('pages/page', [
            'title' => ($page['meta_title'] ?? $page['title'] ?? 'Sayfa') . ' | Art World',
            'metaDescription' => (string) ($page['meta_description'] ?? truncate((string) ($page['content'] ?? ''), 160)),
            'page' => $page,
            'bodyClass' => 'page-static',
        ]);
    }

    public function hakkimizda(): void
    {
        $this->show('hakkimizda');
    }

    public function yayinIlkeleri(): void
    {
        $this->show('yayin-ilkeleri');
    }

    public function kullanimSartlari(): void
    {
        $this->show('kullanim-sartlari');
    }

    public function gizlilikPolitikasi(): void
    {
        $this->show('gizlilik-politikasi');
    }

    public function kvkk(): void
    {
        $this->show('kvkk');
    }

    public function kunye(): void
    {
        $this->show('kunye');
    }

    private function renderFallback(string $slug): void
    {
        $titles = [
            'hakkimizda' => 'Hakkımızda',
            'yayin-ilkeleri' => 'Yayın İlkeleri',
            'kullanim-sartlari' => 'Kullanım Şartları',
            'gizlilik-politikasi' => 'Gizlilik Politikası',
            'kvkk' => 'KVKK / Veri Politikası',
            'kunye' => 'Künye',
        ];
        if (!isset($titles[$slug])) {
            $this->notFound();
            return;
        }

        $settings = $this->settings();
        $content = match ($slug) {
            'hakkimizda' => nl2br(e((string) ($settings['about_text'] ?? 'Art World TV — Dünyanın buluştuğu yerdesiniz.'))),
            'kunye' => '<p><strong>Art World TV</strong></p><p>E-posta: ' . e((string) ($settings['contact_email'] ?? '')) . '</p><p>Telefon: ' . e((string) ($settings['contact_phone'] ?? '')) . '</p>',
            default => '<p>Bu sayfa yakında admin panel üzerinden yayınlanacaktır.</p>',
        };

        $this->render('pages/page', [
            'title' => $titles[$slug] . ' | Art World',
            'metaDescription' => $titles[$slug],
            'page' => [
                'title' => $titles[$slug],
                'slug' => $slug,
                'content' => $content,
            ],
            'bodyClass' => 'page-static',
        ]);
    }
}
