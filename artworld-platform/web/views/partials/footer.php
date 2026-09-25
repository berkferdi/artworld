<?php
/** @var array $settings */
/** @var string $siteName */
/** @var string $siteTagline */
?>
<footer class="site-footer">
    <div class="shell footer-grid">
        <div>
            <div class="footer-brand">ART WORLD</div>
            <p class="footer-tagline"><?= e($siteTagline !== '' ? $siteTagline : 'Dünyanın Buluştuğu Yerdesiniz') ?></p>
            <?php if (!empty($settings['about_text'])): ?>
                <p class="footer-about"><?= e(truncate((string) $settings['about_text'], 220)) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <h3>Kurumsal</h3>
            <ul>
                <li><a href="<?= e(url('/hakkimizda')) ?>">Hakkımızda</a></li>
                <li><a href="<?= e(url('/yayin-ilkeleri')) ?>">Yayın İlkeleri</a></li>
                <li><a href="<?= e(url('/kunye')) ?>">Künye</a></li>
                <li><a href="<?= e(url('/iletisim')) ?>">İletişim</a></li>
            </ul>
        </div>
        <div>
            <h3>Yasal</h3>
            <ul>
                <li><a href="<?= e(url('/kullanim-sartlari')) ?>">Kullanım Şartları</a></li>
                <li><a href="<?= e(url('/gizlilik-politikasi')) ?>">Gizlilik Politikası</a></li>
                <li><a href="<?= e(url('/kvkk')) ?>">KVKK</a></li>
            </ul>
        </div>
        <div>
            <h3>İletişim</h3>
            <?php if (!empty($settings['contact_email'])): ?>
                <p><?= e((string) $settings['contact_email']) ?></p>
            <?php endif; ?>
            <?php if (!empty($settings['contact_phone'])): ?>
                <p><?= e((string) $settings['contact_phone']) ?></p>
            <?php endif; ?>
            <div class="social">
                <?php foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'youtube_url' => 'YouTube', 'x_url' => 'X'] as $key => $label): ?>
                    <?php if (!empty($settings[$key])): ?>
                        <a href="<?= e((string) $settings[$key]) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="shell footer-bottom">
        <span>&copy; <?= date('Y') ?> <?= e($siteName) ?></span>
        <a href="<?= e(url('/canli')) ?>">Canlı Yayın</a>
    </div>
</footer>
