<?php
/**
 * Portfolio main page - displays all active projects
 */
$projects = projectGetAllActive();
$ghPanel  = githubLivePanelHtml();
$ghTheme  = githubLiveTheme();
?>

<section class="portfolio">
    <div class="portfolio__container">
        <h1 class="portfolio__title">Мої Проєкти</h1>
        <p class="portfolio__subtitle">Ласкаво просимо до мого портфоліо</p>

        <?php if ($ghPanel !== ''): ?>
            <section class="github-panel github-panel--<?= $ghTheme ?>" aria-label="Жива статистика GitHub">
                <div class="github-panel__head">
                    <h2 class="github-panel__title">Живий GitHub</h2>
                    <p class="github-panel__subtitle">
                        Дані тягнуться з мого власного API —
                        <a href="https://github.com/shongeorg/elysia-gihub-generator"
                           target="_blank" rel="noopener noreferrer">elysia-gihub-generator</a>
                    </p>
                </div>

                <?= $ghPanel ?>
            </section>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
            <div class="portfolio__empty">
                <p>Проєктів поки що немає</p>
            </div>
        <?php else: ?>
            <div class="portfolio__grid">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $tags = !empty($project['tags']) ? explode(',', $project['tags']) : [];
                    ?>
                    <article class="project-card project-card--active">
                        <div class="project-card__header">
                            <h2 class="project-card__title"><?= htmlspecialchars($project['title']) ?></h2>
                        </div>
                        
                        <div class="project-card__body">
                            <?php if (!empty($project['description'])): ?>
                                <p class="project-card__description">
                                    <?= htmlspecialchars($project['description']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($tags)): ?>
                                <div class="project-card__tags">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="project-card__tag"><?= htmlspecialchars(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="project-card__footer">
                            <?php if (!empty($project['github_url'])): ?>
                                <a href="<?= htmlspecialchars($project['github_url']) ?>" 
                                   class="project-card__link project-card__link--github"
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <span class="project-card__link-icon">📦</span>
                                    GitHub
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($project['site_url'])): ?>
                                <a href="<?= htmlspecialchars($project['site_url']) ?>" 
                                   class="project-card__link project-card__link--site"
                                   target="_blank" 
                                   rel="noopener noreferrer">
                                    <span class="project-card__link-icon">🌐</span>
                                    Сайт
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
