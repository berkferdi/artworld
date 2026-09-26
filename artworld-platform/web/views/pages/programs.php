<?php /** @var array $programs */ ?>
<section class="section">
    <div class="shell">
        <header class="section-head"><h1>Programlar</h1></header>
        <?php if (empty($programs)): ?>
            <p class="empty">Henüz program yok.</p>
        <?php else: ?>
            <div class="program-grid">
                <?php foreach ($programs as $program): ?>
                    <a class="program-card" href="<?= e(program_url((string) $program['slug'])) ?>">
                        <?php if (!empty($program['cover_image'])): ?>
                            <img src="<?= e((string) $program['cover_image']) ?>" alt="" loading="lazy" width="280" height="160">
                        <?php else: ?>
                            <div class="media-fallback" style="width:140px;height:84px;border-radius:4px"></div>
                        <?php endif; ?>
                        <div>
                            <h3><?= e((string) $program['title']) ?></h3>
                            <?php if (!empty($program['presenter'])): ?>
                                <p><?= e((string) $program['presenter']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($program['summary'])): ?>
                                <p><?= e(truncate((string) $program['summary'], 110)) ?></p>
                            <?php elseif (!empty($program['description'])): ?>
                                <p><?= e(truncate((string) $program['description'], 110)) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($program['broadcast_day']) || !empty($program['broadcast_time'])): ?>
                                <span class="meta">
                                    <?= e(trim((string) ($program['broadcast_day'] ?? '') . ' ' . (string) ($program['broadcast_time'] ?? ''))) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
