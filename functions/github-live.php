<?php
/**
 * GitHub Live — «саморозписані» віджети з JSON API (elysia-gihub-generator).
 * Готові SVG не використовуємо: беремо /api/* з кешем на сервері,
 * а графіку (донат, бари, лічильники) малюємо самі через CSS.
 */

const GH_LIVE_API         = 'https://elysia-gihub-generator.vercel.app';
const GH_LIVE_DEFAULT_USER  = 'shongeorg';
const GH_LIVE_DEFAULT_LOC   = 'Global';
const GH_LIVE_DEFAULT_THEME = 'dark';
const GH_LIVE_TTL           = 3600; // кеш даних GitHub, сек

// ==================== Джерело налаштувань (таблиця settings) ====================

/**
 * GitHub username для панелі: з адмінки (settings), інакше дефолт
 */
function githubLiveUsername(): string
{
    return (string) settingGet('github_username', GH_LIVE_DEFAULT_USER);
}

/**
 * Локація для банера (з адмінки)
 */
function githubLiveLocation(): string
{
    return (string) settingGet('github_location', GH_LIVE_DEFAULT_LOC);
}

/**
 * Доступні теми панелі
 */
const GH_LIVE_THEMES = ['dark', 'midnight', 'neon', 'ocean', 'sunset', 'light'];

/**
 * Тема панелі: 'dark' | 'light' | 'neon' | ...
 */
function githubLiveTheme(): string
{
    $theme = (string) settingGet('github_theme', GH_LIVE_DEFAULT_THEME);
    return in_array($theme, GH_LIVE_THEMES, true) ? $theme : GH_LIVE_DEFAULT_THEME;
}

// ==================== Дані з API (з кешем) ====================

function githubLiveCachePath(string $key): string
{
    $dir = DATA_PATH . 'cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir . '/gh-' . $key . '.json';
}

function githubLiveDecode(string $body): ?array
{
    if ($body === '') {
        return null;
    }
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/**
 * Тягнути JSON із /api/<kind>/<username> з файл-кешем.
 * API впав — повертаємо протухлий кеш, панель не зникає.
 */
function githubLiveJson(string $kind, string $username): ?array
{
    $cache = githubLiveCachePath($kind . '-' . $username . '.json');

    if (is_file($cache) && (time() - (int) filemtime($cache)) < GH_LIVE_TTL) {
        return githubLiveDecode((string) file_get_contents($cache));
    }

    $url    = GH_LIVE_API . '/api/' . rawurlencode($kind) . '/' . rawurlencode($username);
    $stream = @fopen($url, 'rb', false, stream_context_create([
        'http' => [
            'timeout' => 6,
            'header'  => "User-Agent: portfolio-site\r\n",
        ],
    ]));
    $body = $stream ? (string) stream_get_contents($stream) : '';
    if ($stream) {
        fclose($stream);
    }

    $data = githubLiveDecode($body);
    if ($data !== null) {
        file_put_contents($cache, $body);
        return $data;
    }

    return is_file($cache) ? githubLiveDecode((string) file_get_contents($cache)) : null;
}

// ==================== Розмальовка мов ====================

/**
 * Кольори мов (лингвіст-палітра) + фолбек для невідомих
 */
function githubLiveLangColor(string $lang): string
{
    $known = [
        'TypeScript' => '#3178c6', 'JavaScript' => '#f1e05a', 'HTML' => '#e34c26',
        'CSS' => '#563d7c', 'SCSS' => '#c6538c', 'Vue' => '#41b883',
        'Svelte' => '#ff3e00', 'Astro' => '#ff5a03', 'PHP' => '#4F5D95',
        'Python' => '#3572A5', 'Go' => '#00ADD8', 'Rust' => '#dea584',
        'Java' => '#b07219', 'C++' => '#f34b7d', 'C' => '#555555',
        'C#' => '#178600', 'Shell' => '#89e051', 'Ruby' => '#701516',
        'Swift' => '#F05138', 'Kotlin' => '#A97BFF', 'Dart' => '#00B4AB',
        'Lua' => '#000080', 'Blade' => '#f7523f', 'Twig' => '#c1d026',
        'EJS' => '#a91e50', 'Dockerfile' => '#384d54', 'Nix' => '#7e7eff',
    ];

    if (isset($known[$lang])) {
        return $known[$lang];
    }

    $fallback = ['#58a6ff', '#22c55e', '#f97316', '#a855f7', '#eab308', '#ef4444', '#06b6d4', '#ec4899'];

    return $fallback[abs(crc32($lang)) % count($fallback)];
}

// ==================== Віджети ====================

/**
 * Банер у стилі «значків»: GITHUB · PROJECTS · MEMBER SINCE · LOCATION
 */
function githubLiveBannerHtml(array $user, string $username): string
{
    $repos = (int) ($user['publicRepos'] ?? 0);
    $year  = substr((string) ($user['createdAt'] ?? ''), 0, 4);
    $loc   = strtoupper(htmlspecialchars(githubLiveLocation()));

    $badge = static fn (string $label, string $value = '', string $mod = ''): string
        => '<span class="gh-badge">'
        . '<span class="gh-badge__label">' . $label . '</span>'
        . ($value !== '' ? '<span class="gh-badge__value' . ($mod !== '' ? ' gh-badge__value--' . $mod : '') . '">'
            . $value . '</span>' : '')
        . '</span>';

    return '<div class="gh-banner">'
        . '<div class="gh-banner__title">Open Source Enthusiast</div>'
        . '<div class="gh-banner__badges">'
        . $badge('GITHUB')
        . $badge('PROJECTS', (string) $repos)
        . ($year !== '' ? $badge('MEMBER SINCE', $year, 'orange') : '')
        . $badge('LOCATION', $loc, 'sky')
        . '</div>'
        . '<div class="gh-banner__line"></div>'
        . '</div>';
}

/**
 * Міні-метрики: repos / stars / forks / followers
 */
function githubLiveStatsHtml(array $stats, string $username): string
{
    $tiles = [
        ['Repos', (int) ($stats['reposScanned'] ?? 0)],
        ['Total Stars', (int) ($stats['totalStars'] ?? 0)],
        ['Total Forks', (int) ($stats['totalForks'] ?? 0)],
        ['Followers', (int) ($stats['followers'] ?? 0)],
    ];

    $html = '<div class="gh-card gh-card--stats">'
        . '<h3 class="gh-card__title">GitHub Stats</h3>'
        . '<p class="gh-card__subtitle">@' . htmlspecialchars($username) . ' • '
        . (int) ($stats['reposScanned'] ?? 0) . ' repos scanned</p>'
        . '<div class="gh-tiles">';

    foreach ($tiles as [$label, $value]) {
        $html .= '<div class="gh-tile">'
            . '<span class="gh-tile__value">' . $value . '</span>'
            . '<span class="gh-tile__label">' . $label . '</span>'
            . '</div>';
    }

    return $html . '</div></div>';
}

/**
 * Топ мов: CSS-донат (conic-gradient) + легенда з барами
 */
function githubLiveLangsHtml(array $data, string $username): string
{
    $langs = array_slice(is_array($data['languages'] ?? null) ? $data['languages'] : [], 0, 10);
    if ($langs === []) {
        return '';
    }

    // Конічний градієнт з накопиченням відсотків
    $acc = 0;
    $stops = [];
    foreach ($langs as $lang) {
        $color = githubLiveLangColor((string) ($lang['name'] ?? '?'));
        $start = $acc;
        $acc   = min($acc + max(0, (float) ($lang['percent'] ?? 0)), 100);
        $stops[] = $color . ' ' . $start . '% ' . $acc . '%';
    }
    if ($acc < 100 && isset($color)) {
        $stops[] = $color . ' ' . $acc . '% 100%';
    }

    $top = $langs[0];

    $html = '<div class="gh-card">'
        . '<h3 class="gh-card__title">Top Languages</h3>'
        . '<p class="gh-card__subtitle">@' . htmlspecialchars($username) . ' • '
        . count($langs) . ' languages</p>'
        . '<div class="gh-langs">'
        . '<div class="gh-donut" style="background:conic-gradient(' . implode(', ', $stops) . ')">'
        . '<span class="gh-donut__label">'
        . '<span class="gh-donut__percent">' . (float) $top['percent'] . '%</span>'
        . '<span class="gh-donut__lang">' . htmlspecialchars((string) ($top['name'] ?? '')) . '</span>'
        . '</span>'
        . '</div>'
        . '<ul class="gh-legend">';

    foreach ($langs as $lang) {
        $name    = htmlspecialchars((string) ($lang['name'] ?? '?'));
        $percent = max(0, (float) ($lang['percent'] ?? 0));
        $color   = githubLiveLangColor((string) ($lang['name'] ?? ''));

        $html .= '<li class="gh-legend__row">'
            . '<span class="gh-legend__dot" style="background:' . $color . '"></span>'
            . '<span class="gh-legend__name">' . $name . '</span>'
            . '<span class="gh-legend__track">'
            . '<span class="gh-legend__fill" style="width:' . $percent . '%;background:' . $color . '"></span>'
            . '</span>'
            . '<span class="gh-legend__pct">' . $percent . '%</span>'
            . '</li>';
    }

    return $html . '</ul></div></div>';
}

/**
 * Стріки: total / current / longest
 */
function githubLiveStreakHtml(array $data): string
{
    $from = (string) ($data['from'] ?? '');
    $to   = (string) ($data['to'] ?? '');
    $range = ($from !== '' && $to !== '')
        ? date('d.m.Y', strtotime($from)) . ' – ' . date('d.m.Y', strtotime($to))
        : '';

    $cols = [
        [(int) ($data['totalContributions'] ?? 0), 'Total Contributions', ''],
        [(int) ($data['currentStreak'] ?? 0), '🔥 Current Streak', ''],
        [(int) ($data['longestStreak'] ?? 0), '🏆 Longest Streak', ''],
    ];

    $html = '<div class="gh-card">'
        . '<h3 class="gh-card__title">Contribution Streaks</h3>'
        . '<p class="gh-card__subtitle">' . htmlspecialchars($range) . '</p>'
        . '<div class="gh-streak">';

    foreach ($cols as [$value, $label]) {
        $html .= '<div class="gh-streak__col">'
            . '<span class="gh-streak__value">' . $value . '</span>'
            . '<span class="gh-streak__label">' . $label . '</span>'
            . '</div>';
    }

    return $html . '</div></div>';
}

/**
 * Готовий HTML панелі для pages/portfolio.php
 */
function githubLivePanelHtml(): string
{
    $username = githubLiveUsername();

    $user   = githubLiveJson('user', $username);
    $stats  = githubLiveJson('stats', $username);
    $langs  = githubLiveJson('top-langs', $username);
    $streak = githubLiveJson('streak', $username);

    $html = $user !== null ? githubLiveBannerHtml($user, $username) : '';

    $row = '';
    if ($stats !== null) {
        $row .= githubLiveStatsHtml($stats, $username);
    }
    if ($langs !== null) {
        $row .= githubLiveLangsHtml($langs, $username);
    }
    if ($row !== '') {
        $html .= '<div class="gh-row">' . $row . '</div>';
    }

    if ($streak !== null) {
        $html .= githubLiveStreakHtml($streak);
    }

    return $html;
}
