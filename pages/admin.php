<?php
/**
 * Admin dashboard - main admin page
 */
requireAdmin();

$stats = [
    'total_projects' => dbCount('projects'),
    'active_projects' => db()->query("SELECT COUNT(*) FROM `projects` WHERE is_active = 1")->fetchColumn(),
];
?>

<section class="admin">
    <div class="admin__container">
        <div class="admin__header">
            <h1 class="admin__title">Панель адміністратора</h1>
            <p class="admin__welcome">Привіт, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</p>
        </div>

        <div class="admin__stats">
            <div class="stat-card">
                <span class="stat-card__value"><?= $stats['total_projects'] ?></span>
                <span class="stat-card__label">Всього проєктів</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__value"><?= $stats['active_projects'] ?></span>
                <span class="stat-card__label">Активних</span>
            </div>
        </div>

        <div class="admin__actions">
            <a href="/?page=admin-projects" class="admin-btn admin-btn--primary">
                <span class="admin-btn__icon">📋</span>
                Управління проєктами
            </a>
            <a href="/?page=portfolio" class="admin-btn admin-btn--secondary" target="_blank">
                <span class="admin-btn__icon">👁️</span>
                Переглянути сайт
            </a>
            <a href="/?page=admin-logout" class="admin-btn admin-btn--danger">
                <span class="admin-btn__icon">🚪</span>
                Вийти
            </a>
        </div>
    </div>
</section>
