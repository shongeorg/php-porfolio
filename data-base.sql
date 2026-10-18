-- Схема БД для Neon Postgres (і MySQL-сумісний варіант бачив у git-історії)
-- Застосування: php scripts/init-db.php (схема + проєкти + admin/admin для локального тесту)
--
-- ВАЖЛИМО: адмінський запис НЕ створюється цим SQL — додай його сам при деплої:
--   INSERT INTO admin (username, password) VALUES ('<логін>', '<bcrypt-хеш>');
-- Хеш: password_hash('<пароль>', PASSWORD_DEFAULT) (генератор — generate-hash.php)

-- Таблиця адміністратора
CREATE TABLE IF NOT EXISTS admin (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблиця проєктів портфоліо
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

-- Налаштування сайту (key/value): github_username, github_location, github_theme ...
CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(64) NOT NULL UNIQUE,
    setting_value VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO settings (setting_key, setting_value) VALUES ('github_username', 'shongeorg')
    ON CONFLICT (setting_key) DO NOTHING;
INSERT INTO settings (setting_key, setting_value) VALUES ('github_location', 'Global')
    ON CONFLICT (setting_key) DO NOTHING;
INSERT INTO settings (setting_key, setting_value) VALUES ('github_theme', 'dark')
    ON CONFLICT (setting_key) DO NOTHING;
