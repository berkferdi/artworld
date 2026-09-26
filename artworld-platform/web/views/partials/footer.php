<?php
/** @var array $settings */
/** @var string $siteName */
/** @var string $siteTagline */
$footerLogo = asset('/assets/images/branding/logo-horizontal-light.svg');
?>
<footer class="site-footer">
    <div class="shell footer-grid">
        <div>
            <img class="footer-logo" src="<?= e($footerLogo) ?>" alt="<?= e($siteName) ?>" width="160" height="48">
            <p class="footer-tagline"><?= e($siteTagline !== '' ? $siteTagline : 'Dünyanın Buluştuğu Yerdesiniz') ?></p>
            <?php if (!empty($settings['about_text'])): ?>
                <p class="footer-about"><?= e(truncate((string) $settings['about_text'], 220)) ?></p>
            <?php endif; ?>
            <div class="social">
                <?php foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'youtube_url' => 'YouTube', 'x_url' => 'X'] as $key => $label): ?>
                    <?php if (!empty($settings[$key])): ?>
                        <a href="<?= e((string) $settings[$key]) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h3>Menü</h3>
            <ul>
                <li><a href="<?= e(url('/')) ?>">Ana Sayfa</a></li>
                <li><a href="<?= e(url('/canli-yayin')) ?>">Canlı Yayın</a></li>
                <li><a href="<?= e(url('/videolar')) ?>">Videolar</a></li>
                <li><a href="<?= e(url('/programlar')) ?>">Programlar</a></li>
                <li><a href="<?= e(url('/foto-galeri')) ?>">Foto Galeri</a></li>
                <li><a href="<?= e(url('/iletisim')) ?>">İletişim</a></li>
            </ul>
        </div>
        <div>
            <h3>Kategoriler</h3>
            <ul>
                <li><a href="<?= e(url('/gundem')) ?>">Gündem</a></li>
                <li><a href="<?= e(url('/spor')) ?>">Spor</a></li>
                <li><a href="<?= e(url('/ekonomi')) ?>">Ekonomi</a></li>
                <li><a href="<?= e(url('/kultur-sanat')) ?>">Kültür Sanat</a></li>
                <li><a href="<?= e(url('/teknoloji')) ?>">Teknoloji</a></li>
            </ul>
        </div>
        <div>
            <h3>İletişim & Yasal</h3>
            <?php if (!empty($settings['contact_email'])): ?>
                <p><?= e((string) $settings['contact_email']) ?></p>
            <?php endif; ?>
            <?php if (!empty($settings['contact_phone'])): ?>
                <p><?= e((string) $settings['contact_phone']) ?></p>
            <?php endif; ?>
            <ul style="margin-top:0.75rem">
                <li><a href="<?= e(url('/kvkk')) ?>">KVKK</a></li>
                <li><a href="<?= e(url('/gizlilik-politikasi')) ?>">Gizlilik</a></li>
                <li><a href="<?= e(url('/kullanim-sartlari')) ?>">Kullanım Şartları</a></li>
                <li><a href="<?= e(url('/kunye')) ?>">Künye</a></li>
            </ul>
        </div>
    </div>
    <div class="shell footer-bottom">
        <span>&copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <a href="<?= e(url('/canli-yayin')) ?>">Canlı Yayın</a>
    </div>
</footer>
