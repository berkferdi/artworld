<?php /** @var bool $sent */ /** @var string|null $error */ /** @var array $settings */ ?>
<section class="section"><div class="shell narrow">
<header class="section-head"><h1>İletişim</h1></header>
<?php if ($sent): ?>
<p class="notice notice--ok">Mesajınız alındı. Teşekkür ederiz.</p>
<?php else: ?>
<?php if ($error): ?><p class="notice notice--err"><?= e($error) ?></p><?php endif; ?>
<div class="contact-grid">
<div>
<?php if (!empty($settings['contact_email'])): ?><p><strong>E-posta</strong><br><?= e((string) $settings['contact_email']) ?></p><?php endif; ?>
<?php if (!empty($settings['contact_phone'])): ?><p><strong>Telefon</strong><br><?= e((string) $settings['contact_phone']) ?></p><?php endif; ?>
</div>
<form class="contact-form" method="post" action="<?= e(url('/iletisim')) ?>">
<label>Ad Soyad<input type="text" name="name" required maxlength="120"></label>
<label>E-posta<input type="email" name="email" required maxlength="180"></label>
<label>Mesaj<textarea name="message" rows="6" required maxlength="4000"></textarea></label>
<button class="btn btn--primary" type="submit">Gönder</button>
</form>
</div>
<?php endif; ?>
</div></section>
