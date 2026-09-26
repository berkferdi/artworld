<?php

declare(strict_types=1);

namespace Web\Controllers;

final class ContactController extends BaseController
{
    public function index(): void
    {
        $this->render('pages/contact', [
            'title' => 'İletişim | Art World',
            'metaDescription' => 'Art World iletişim bilgileri',
            'sent' => false,
            'error' => null,
            'bodyClass' => 'page-contact',
        ]);
    }

    public function submit(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('pages/contact', [
                'title' => 'İletişim | Art World',
                'sent' => false,
                'error' => 'Lütfen tüm alanları doğru doldurun.',
                'bodyClass' => 'page-contact',
            ]);
            return;
        }

        // Contact is informational for now; store attempt in log for ops.
        $line = sprintf("[%s] contact from %s <%s>: %s\n", date('c'), $name, $email, str_replace(["\r", "\n"], ' ', $message));
        @file_put_contents(WEB_STORAGE . '/contact.log', $line, FILE_APPEND | LOCK_EX);

        $this->render('pages/contact', [
            'title' => 'İletişim | Art World',
            'sent' => true,
            'error' => null,
            'bodyClass' => 'page-contact',
        ]);
    }
}
