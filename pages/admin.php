<?php
/**
 * Admin dashboard - main admin page
 */
requireAdmin();

// ---- Збереження налаштувань GitHub (тільки POST + CSRF) ----
$ghMessage = '';
$ghMessageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save-github') {
    if (!csrfVerify()) {
        $ghMessage = 'Сесія протухла, оновіть сторінку (CSRF-токен невалідний)';
        $ghMessageType = 'error';
    } else {
        $username = preg_replace('/[^A-Za-z0-9-]/', '', trim((string) ($_POST['github_username'] ?? ''), "@ \t\n\r\0\x0B"));
        $location = trim((string) ($_POST['github_location'] ?? ''));
        $theme    = in_array($_POST['github_theme'] ?? '', GH_LIVE_THEMES, true) ? $_POST['github_theme'] : 'dark';

        if ($username === '') {
            $ghMessage = 'GitHub username обовʼязковий (лише A-Z, a-z, 0-9 та дефіс)';
            $ghMessageType = 'error';
        } else {
            $location = preg_replace('/\s+/', ' ', $location);
            $location = $location !== '' ? mb_substr($location, 0, 40) : 'Global';

            $ok = settingSet('github_username', $username)
                && settingSet('github_location', $location)
                && settingSet('github_theme', $theme);

            $ghMessage = $ok
                ? 'Налаштування GitHub збережено'
                : 'Помилка при збереженні в БД';
            $ghMessageType = $ok ? 'success' : 'error';
        }
    }
}

$stats = [
    'total_projects' => db() !== null ? (int) dbCount('projects') : 0,
    'active_projects' => db() !== null ? (int) db()->query("SELECT COUNT(*) FROM projects WHERE is_active = 1")->fetchColumn() : 0,
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

        <div class="form-card admin__github">
            <h2 class="form-card__title">Живий GitHub — джерело даних</h2>

            <?php if ($ghMessage): ?>
                <div class="message message--<?= $ghMessageType ?>">
                    <?= htmlspecialchars($ghMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/?page=admin">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save-github">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-group__label" for="github_username">GitHub username</label>
                        <input
                            class="form-group__input"
                            type="text"
                            id="github_username"
                            name="github_username"
                            value="<?= htmlspecialchars(githubLiveUsername()) ?>"
                            placeholder="shongeorg"
                            maxlength="39"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="github_theme">Тема сайту</label>
                        <select
                            class="form-group__input"
                            id="github_theme"
                            name="github_theme"
                        >
                            <?php foreach ([
                                'dark'     => '🌑 Dark (GitHub)',
                                'midnight' => '🌌 Midnight',
                                'neon'     => '🟢 Neon',
                                'ocean'    => '🌊 Ocean',
                                'sunset'   => '🌅 Sunset',
                                'light'    => '☀️ Light',
                            ] as $themeKey => $themeLabel): ?>
                                <option value="<?= $themeKey ?>" <?= githubLiveTheme() === $themeKey ? 'selected' : '' ?>>
                                    <?= $themeLabel ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-group__label" for="github_location">Локація в банері</label>
                    <input
                        class="form-group__input"
                        type="text"
                        id="github_location"
                        name="github_location"
                        value="<?= htmlspecialchars(githubLiveLocation()) ?>"
                        placeholder="Global"
                        maxlength="40"
                    >
                </div>

                <div class="form-actions">
                    <button type="submit" class="admin-btn admin-btn--primary">
                        Зберегти
                    </button>
                </div>
            </form>
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
