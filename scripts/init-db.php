<?php
/**
 * Разова ініціалізація Neon Postgres: таблиці + admin/admin + тестові проєкти.
 * Запуск: php scripts\init-db.php
 */

require __DIR__ . '/../functions/data-base.php';

$db = db();
if ($db === null) {
    fwrite(STDERR, "Немає з'єднання з БД. Перевір .env (NEON_DB).\n");
    exit(1);
}

$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver: {$driver}\n";
echo "Server: " . $db->query('SELECT version()')->fetchColumn() . "\n\n";

// ---------- Схема ----------

if ($driver === 'pgsql') {
    $db->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS admin (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS projects (
            id SERIAL PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            github_url VARCHAR(255),
            site_url VARCHAR(255),
            image_url VARCHAR(255),
            tags VARCHAR(255),
            sort_order INT DEFAULT 0,
            is_active INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(64) NOT NULL UNIQUE,
            setting_value VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        SQL);
} else {
    $db->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            github_url VARCHAR(255),
            site_url VARCHAR(255),
            image_url VARCHAR(255),
            tags VARCHAR(255),
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(64) NOT NULL UNIQUE,
            setting_value VARCHAR(255),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        SQL);
}

echo "Схема готова.\n";

// ---------- Дефолтні налаштування (панель «Живий GitHub») ----------

settingSet('github_username', 'shongeorg');
settingSet('github_location', 'Global');
settingSet('github_theme', 'dark');
echo "Настройки: github_username=shongeorg, github_location=Global, github_theme=dark.\n";

// ---------- Admin: admin/admin ----------

$hash = password_hash('admin', PASSWORD_DEFAULT);
$exists = $db->prepare("SELECT id FROM admin WHERE username = ?");
$exists->execute(['admin']);

if ($row = $exists->fetch()) {
    $up = $db->prepare("UPDATE admin SET password = ? WHERE id = ?");
    $up->execute([$hash, $row['id']]);
    echo "Пароль адміна 'admin' оновлено на 'admin'.\n";
} else {
    $ins = $db->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $ins->execute(['admin', $hash]);
    echo "Створено адміна admin/admin.\n";
}

// ---------- Тестові проєкти з GitHub shongeorg ----------

echo "Завантажую репозиторії GitHub...\n";

$repos = json_decode(file_get_contents(
    'https://api.github.com/users/shongeorg/repos?per_page=100&sort=updated',
    false,
    stream_context_create(['http' => [
        'header' => "User-Agent: portfolio-seed\r\nAccept: application/vnd.github+json",
        'timeout' => 20,
    ]])
), true);

if (!is_array($repos)) {
    fwrite(STDERR, "GitHub API не відповів.\n");
    exit(1);
}

// Короткі описи для реп, де description пустий або англійський з одного слова
$descriptions = [
    'figma-mcp-context-opencode'   => 'MCP-інтеграція Figma для OpenCode — контекст і дизайн-дані з Figma в агенті',
    'photon-studio-opencode'       => 'Photon Studio для OpenCode — інструменти для роботи з iMessage-інтеграціями',
    'maket-photon'                 => 'Макет-сторінка на Photon з адаптивною версткою',
    'elysia-telegram-cloud'        => 'Telegram-бот хмарного сховища на Elysia: завантаження і збереження файлів',
    'news-next'                    => 'Новинний сайт на Next.js з лентою і пошуком',
    'tech-store'                   => 'Інтернет-магазин техніки: каталог, фільтри, кошик',
    'wh40k'                        => 'Сайт по Warhammer 40k тау на Vercel',
    'elysia-gihub-generator'       => 'Генератор README/профіля для GitHub на Elysia',
    'elysia-telegram-cloud-player' => 'Медіа-плеєр для хмарного сховища через Telegram, деплой Vercel',
    'elysia-api'                   => 'REST API на Elysia (Bun): маршрути, валідація, продакшн-деплой',
    'elysia-client-app'            => 'Клієнтський SPA для Elysia API — фронт з авторизацією',
    'Speech-Synthesis'             => 'Синтез мовлення у браузері через Web Speech API (Vite)',
    'elysia-react'                 => 'React-клієнт для Elysia API',
    'resume-builder-ai'            => 'Генератор резюме/PDF у JSON-пайплайні, Vercel',
    'my-next'                      => 'Тестовий Next.js проєкт',
    'svg-generator'                => 'Онлайн-генератор SVG-графіки',
    'shadcn-theme-constructor'     => 'Конструктор тем для shadcn/ui',
    'Flibusta-API'                 => 'API для книжкового архіву Flibusta, Vercel',
    'food-delivery-template'       => 'Шаблон доставки їжі: меню, кошик, оформлення замовлення',
    'modo-landing'                 => 'Лендінг-сторінка Mode з анімаціями',
    'php-porfolio'                 => 'Це портфоліо на чистому PHP (пан-кіт) — live версія',
    'hono-on-vercel'               => 'Демонстрація Hono-фреймворку на Vercel Edge',
    'user-management-system'       => 'Система управління користувачами на PHP + MySQL',
    'react-pwa-radio'              => 'PWA радіо на React: офлайн-кеш, потокове аудіо',
    'cafe-template-landing'        => 'Лендінг-шаблон для кафе з меню та контактами',
    'radio-angular'                => 'Радіо-плеєр на Angular',
    'node-api'                     => 'Node.js API: маршрути і ендпоінти на Vercel',
    'astro-static'                 => 'Статичний сайт на Astro',
];

$tagsByLang = ['PHP' => 'PHP', 'JavaScript' => 'JavaScript', 'TypeScript' => 'TypeScript'];

$extraTags = [
    'news-next' => 'Next.js', 'my-next' => 'Next.js', 'hono-on-vercel' => 'Hono',
    'elysia-api' => 'Bun', 'elysia-telegram-cloud' => 'Bun', 'elysia-telegram-cloud-player' => 'Bun',
    'elysia-gihub-generator' => 'Bun', 'elysia-client-app' => 'SPA', 'elysia-react' => 'React',
    'elysia-telegram-master' => 'Bot API', 'react-pwa-radio' => 'React, PWA',
    'radio-angular' => 'Angular', 'astro-static' => 'Astro', 'shadcn-theme-constructor' => 'React, shadcn/ui',
    'Speech-Synthesis' => 'Vite', 'svg-generator' => 'SVG', 'tech-store' => 'E-commerce',
    'food-delivery-template' => 'E-commerce', 'user-management-system' => 'MySQL',
    'php-porfolio' => 'Чистий PHP',
];

// Очистка попередніх сид-даних проєктів
$db->exec('DELETE FROM projects');

$insert = $db->prepare(
    'INSERT INTO projects (title, description, github_url, site_url, tags, sort_order, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);

$sort = 1;
$added = 0;
foreach ($repos as $repo) {
    $name = $repo['name'];

    // Тільки репи з задеплоєним сайтом у homepage
    $site = trim((string)($repo['homepage'] ?? ''));
    if ($site === '') {
        continue;
    }
    // StackBlitz-редактори не є задеплоєним сайтом
    if (str_contains($site, 'stackblitz.com')) {
        continue;
    }
    if (!preg_match('#^https?://#i', $site)) {
        $site = 'https://' . $site;
    }

    $title = str_starts_with($name, 'elysia-') ? 'Elysia — ' . trim(str_starts_with($name, 'elysia-') ? substr($name, 7) : $name, '-') : $name;

    // людяніша назва: eloysia-... → 'Elysia: ...'
    if (str_starts_with($name, 'elysia-')) {
        $tail = substr($name, strlen('elysia-'));
        $tail = str_replace('-', ' ', $tail);
        $title = 'Elysia: ' . ucfirst($tail);
    } else {
        $title = str_replace(['-', '_'], ' ', $name);
        $title = mb_convert_case($title, MB_CASE_TITLE_SIMPLE);
    }

    $desc = $descriptions[$name] ?? trim((string)($repo['description'] ?? '')) ?: 'Проєкт на GitHub, задеплоєний на Vercel';

    $lang = (string)($repo['language'] ?? '');
    $tags = [];
    if ($lang !== '' && isset($tagsByLang[$lang])) {
        $tags[] = $tagsByLang[$lang];
    }
    if (isset($extraTags[$name])) {
        foreach (explode(',', $extraTags[$name]) as $t) {
            $tags[] = trim($t);
        }
    }
    $tags = implode(', ', array_unique($tags));

    $insert->execute([
        $title,
        $desc,
        $repo['html_url'],
        $site,
        $tags !== '' ? $tags : null,
        $sort++,
        1,
    ]);
    $added++;
    echo "  + {$name} → {$site}\n";
}

echo "\nДодано проєктів: {$added}\n";
echo "Разом у БД: " . $db->query('SELECT COUNT(*) FROM projects')->fetchColumn() . "\n";
echo "Адмін у БД: " . $db->query('SELECT COUNT(*) FROM admin')->fetchColumn() . "\n";
