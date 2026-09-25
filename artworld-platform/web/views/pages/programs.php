<?php /** @var array $programs */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h1>Programlar</h1></header>
        <div class="program-grid">
            <?php foreach ($programs as $program): ?>
                <a class="program-card" href="<?= e(program_url((string) $program['slug'])) ?>">
                    <?php if (!empty($program['cover_image'])): ?>
                        <img src="<?= e((string) $program['cover_image']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <div>
                        <h3><?= e((string) $program['title']) ?></h3>
                        <?php if (!empty($program['presenter'])): ?><p><?= e((string) $program['presenter']) ?></p><?php endif; ?>
                        <?php if (!empty($program['broadcast_day'])): ?><span class="meta"><?= e((string) $program['broadcast_day']) ?></span><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
