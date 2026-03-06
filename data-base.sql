-- Таблица администратора
-- Пароль по умолчанию: admin123 (хеш bcrypt)
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем админа с паролем 'admin123'
-- Хеш создан через PHP: password_hash('admin123', PASSWORD_DEFAULT)
-- ВАЖНО: хеш генерируется индивидуально для каждого сервера!
-- Если пароль не подходит, запусти generate-hash.php и обнови хеш в таблице
INSERT INTO `admin` (`username`, `password`) VALUES
('admin', '$2y$10$kSaBw7J7sYDXOKe9q/lywuJznPnj6UXxXPdoJ1Evdh9uoew95yH5.');

-- Таблица проектов портфолио
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `github_url` VARCHAR(255),
  `site_url` VARCHAR(255),
  `image_url` VARCHAR(255) DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Примеры проектов (можно удалить)
INSERT INTO `projects` (`title`, `description`, `github_url`, `site_url`, `tags`, `sort_order`, `is_active`) VALUES
('Portfolio Website', 'Мой личный сайт-портфолио на PHP', 'https://github.com/pankit/portfolio', 'https://pan-kit.infinityfreeapp.com', 'PHP, CSS, HTML', 1, 1),
('E-commerce Project', 'Интернет-магазин с корзиной и оплатой', 'https://github.com/pankit/ecommerce', 'https://shop-demo.infinityfreeapp.com', 'PHP, MySQL, JavaScript', 2, 1),
('Task Manager', 'Приложение для управления задачами', 'https://github.com/pankit/taskmanager', NULL, 'PHP, AJAX, CSS', 3, 1);
