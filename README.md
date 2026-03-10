# 🚀 PHP Portfolio — Навчальний проект

Простий та зрозумілий приклад побудови веб-сайту на чистому PHP з використанням базових архітектурних патернів.

---

## 📖 Зміст

- [Особливості](#особливості)
- [Архітектура проекту](#архітектура-проекту)
- [Логіка роутингу](#логіка-роутингу)
- [Робота з базою даних](#робота-з-базою-даних)
- [Структура файлів](#структура-файлів)
- [Встановлення](#встановлення)
- [Використання](#використання)

---

## ✨ Особливості

- 🔄 **Front Controller** — весь трафік обробляється через `index.php`
- 🧩 **Компонентний підхід** — reusable header/footer для всіх сторінок
- 🗄️ **Singleton для БД** — одне підключення на весь запит
- 🛡️ **PDO з prepared statements** — захист від SQL-ін'єкцій
- 🎨 **BEM + CSS змінні** — чиста та підтримувана верстка
- 🔐 **Сесійна аутентифікація** — безпечний вхід в адмінку
- 📝 **CRUD для проєктів** — повний цикл управління контентом

---

## 🏗 Архітектура проекту

Проект побудований за принципом **Front Controller** — це архітектурний патерн, де всі запити проходять через одну точку входу (`index.php`), яка вирішує, яку сторінку відобразити.

```
┌─────────────┐
│   Запит     │
│  ?page=home │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│   index.php     │ ← Front Controller
│  (роутинг +     │
│   рендеринг)    │
└────────┬────────┘
         │
    ┌────┴────┬────────────┬──────────┐
    │         │            │          │
    ▼         ▼            ▼          ▼
┌───────┐ ┌───────┐  ┌─────────┐ ┌───────┐
│header │ │ page  │  │ footer  │ │  DB   │
│.php   │ │.php   │  │ .php    │ │layer  │
└───────┘ └───────┘  └─────────┘ └───────┘
```

---

## 🧭 Логіка роутингу

Роутинг реалізовано максимально просто та прозоро — через GET-параметр `page`.

### Як це працює:

```php
// 1. Отримуємо назву сторінки з URL
$page = $_GET["page"] ?? "portfolio";

// 2. Формуємо ім'я файлу
$file = $page . ".php";

// 3. Перевіряємо існування та обираємо fallback
$render_page = PAGE_PATH . (file_exists(PAGE_PATH . $file) ? $file : '404.php');

// 4. Динамічно підключаємо сторінку
require $render_page;
```

### Приклади URL:

| URL | Сторінка | Файл |
|-----|----------|------|
| `/` | portfolio | `pages/portfolio.php` |
| `/?page=admin` | admin | `pages/admin.php` |
| `/?page=unknown` | 404 | `pages/404.php` |

### Переваги такого підходу:

✅ **Простота** — зрозуміло навіть початківцям  
✅ **Гнучкість** — легко додати нову сторінку (створи файл у `pages/`)  
✅ **Безпека** — fallback на 404 для неіснуючих сторінок  
✅ **Централізація** — вся логіка в одному місці

---

## 🗄 Робота з базою даних

### Singleton патерн для підключення

Функція `db()` використовує ключове слово **`static`**, що реалізує патерн **Singleton** — створюється лише одне підключення до БД на весь запит.

```php
function db(): ?PDO
{
    static $dbh = null;  // ← Статична змінна зберігається між викликами

    if ($dbh !== null) {
        return $dbh;  // ← Повертаємо існуюче підключення
    }

    try {
        $dbh = new PDO(
            "mysql:dbname=database;host=localhost;charset=utf8mb4",
            "username",
            "password",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $dbh;
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        return null;
    }
}
```

### Як працює static:

```
1-й виклик: db() → створює нове PDO → зберігає в $dbh → повертає
2-й виклик: db() → $dbh вже не null → повертає існуюче
3-й виклик: db() → $dbh вже не null → повертає існуюче
```

**Результат:** економимо ресурси, уникаємо множинних підключень.

---

### Helper-функції для роботи з БД

| Функція | Опис | Приклад |
|---------|------|---------|
| `dbGetAll($table)` | Отримати всі записи | `$users = dbGetAll('users')` |
| `dbGetById($table, $id)` | Знайти за ID | `$user = dbGetById('users', 1)` |
| `dbInsert($table, $data)` | Додати запис | `dbInsert('users', ['name' => 'John'])` |
| `dbUpdate($table, $id, $data)` | Оновити запис | `dbUpdate('users', 1, ['name' => 'Jane'])` |
| `dbDelete($table, $id)` | Видалити запис | `dbDelete('users', 1)` |
| `dbCount($table)` | Порахувати записи | `$count = dbCount('users')` |
| `dbPaginate($table, $limit, $page)` | Пагінація | `$posts = dbPaginate('posts', 10, 1)` |

---

### Приклад використання в коді:

```php
// Отримати всі активні проєкти
$projects = projectGetAllActive();

// Всередині функції:
function projectGetAllActive(): array
{
    $stmt = db()->prepare("SELECT * FROM `projects` WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}
```

---

## 📁 Структура файлів

```
pan-kit.infinityfreeapp.com/
├── index.php                 # 🎯 Front Controller (роутинг)
├── components/               # 🧩 UI компоненти
│   ├── header.php            # Шапка сайту
│   └── footer.php            # Підвал сайту
├── pages/                    # 📄 Сторінки
│   ├── portfolio.php         # Портфоліо (головна)
│   ├── admin.php             # Адмін-панель
│   ├── admin-login.php       # Вхід для адміна
│   ├── admin-projects.php    # CRUD проєктів
│   └── 404.php               # Помилка 404
├── functions/                # ⚙️ Допоміжні функції
│   ├── data-base.php         # Робота з БД (Singleton)
│   └── seo.php               # SEO meta-теги
├── data/                     # 📊 Дані
│   └── seo.json              # SEO конфігурація
├── public/                   # 🎨 Статичні файли
│   ├── index.css             # Стилі (BEM + CSS змінні)
│   └── index.js              # JavaScript
└── data-base.sql             # 🗄️ SQL схема бази даних
```

---

## 🚀 Встановлення

### Вимоги:
- PHP 8.0+
- MySQL 5.7+
- Веб-сервер (Apache/Nginx)

### Кроки:

1. **Завантажте файли на сервер**
   ```bash
   # Через FTP або копіюванням у htdocs/www/public_html
   ```

2. **Імпортуйте базу даних**
   ```sql
   mysql -u username -p database_name < data-base.sql
   ```

3. **Налаштуйте підключення до БД**
   
   Відредагуйте `functions/data-base.php`:
   ```php
   new PDO(
       "mysql:dbname=YOUR_DB;host=localhost;charset=utf8mb4",
       "YOUR_USERNAME",
       "YOUR_PASSWORD"
   );
   ```

4. **Готово!** Відкрийте сайт у браузері.

---

## 💡 Використання

### Додавання нової сторінки:

1. Створіть файл `pages/my-page.php`:
   ```php
   <h1>Моя нова сторінка</h1>
   <p>Тут якийсь контент</p>
   ```

2. Відкрийте у браузері:
   ```
   https://yoursite.com/?page=my-page
   ```

### Робота з проєктами через БД:

```php
// Додати новий проєкт
dbInsert('projects', [
    'title' => 'Мій проєкт',
    'description' => 'Опис проєкту',
    'github_url' => 'https://github.com/user/repo',
    'is_active' => 1
]);

// Отримати проєкт
$project = dbGetById('projects', 1);

// Оновити
dbUpdate('projects', 1, ['title' => 'Нова назва']);

// Видалити
dbDelete('projects', 1);
```

---

## 📚 Для чого цей проект?

Цей проект створено з **навчальною метою** для демонстрації:

- ✅ Як працює роутинг на PHP
- ✅ Як динамічно підключати файли
- ✅ Як реалізувати Singleton для БД
- ✅ Як будувати простий CRUD
- ✅ Як організувати компонентну верстку

**Ідеально підходить для:**
- Початківців, які вивчають PHP
- Прикладів на курсах/уроках
- Швидкого старту власного портфоліо

---

## 📄 Ліцензія

Вільне використання для навчальних цілей.

---

**Зроблено з ❤️ для навчання**
