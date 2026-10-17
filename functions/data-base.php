<?php
/**
 * Database connection and helper functions
 * Pure PHP - no frameworks, no Composer
 */

/**
 * Resolve database URL:
 * 1. environment variable NEON_DB (or DATABASE_URL / MYSQL_URL)
 * 2. .env file in project root (NEON_DB=...)
 * Supported: postgres://, postgresql://, mysql://
 * @return string|null
 */
function dbConfigUrl(): ?string
{
    $url = getenv('NEON_DB') ?: getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

    if ($url === false || $url === '') {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (preg_match('/^(?:export\s+)?NEON_DB=(.+)$/', $line, $m)) {
                    $url = trim($m[1], ' \'"');
                    break;
                }
            }
        }
    }

    return ($url !== false && $url !== '') ? $url : null;
}

/**
 * Build a PDO instance from a database URL
 * @return PDO|null
 */
function db(): ?PDO
{
    static $dbh = null;

    if ($dbh !== null) {
        return $dbh;
    }

    $url = dbConfigUrl();

    if ($url === null) {
        error_log('DB Connection Error: no DB URL configured (set NEON_DB or .env)');
        return null;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $p = parse_url($url);
        if ($p === false || empty($p['host'])) {
            error_log('DB Connection Error: invalid DB URL');
            return null;
        }

        $scheme = strtolower($p['scheme'] ?? '');
        $dbname = ltrim($p['path'] ?? '', '/');

        if ($dbname === '') {
            error_log('DB Connection Error: missing database name in URL');
            return null;
        }

        if (in_array($scheme, ['postgres', 'postgresql'], true)) {
            $dbh = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $p['host'], $p['port'] ?? 5432, $dbname),
                $p['user'] ?? '',
                $p['pass'] ?? '',
                $options
            );
        } elseif ($scheme === 'mysql') {
            $dbh = new PDO(
                sprintf('mysql:dbname=%s;host=%s;charset=utf8mb4;port=%d', $dbname, $p['host'], $p['port'] ?? 3306),
                $p['user'] ?? '',
                $p['pass'] ?? '',
                $options
            );
        } else {
            error_log("DB Connection Error: unsupported scheme '{$scheme}'");
            return null;
        }

        return $dbh;
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        return null;
    }
}

// ==================== Generic Functions ====================

/**
 * Get all records from table
 */
function dbGetAll(string $table, string $orderBy = 'id DESC'): array
{
    if (db() === null) return [];
    $table = dbEscapeIdentifier($table);
    return db()->query("SELECT * FROM {$table} ORDER BY {$orderBy}")->fetchAll();
}

/**
 * Count records in table
 */
function dbCount(string $table): int
{
    if (db() === null) return 0;
    $table = dbEscapeIdentifier($table);
    return (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

/**
 * Get single record by ID
 */
function dbGetById(string $table, int $id): ?array
{
    if (db() === null) return null;
    $table = dbEscapeIdentifier($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Insert record into table
 */
function dbInsert(string $table, array $data): int
{
    if (db() === null) return 0;
    $table = dbEscapeIdentifier($table);
    $fields = array_keys($data);
    $columns = implode(', ', $fields);
    $placeholders = ':' . implode(', :', $fields);
    
    $stmt = db()->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
    $stmt->execute($data);

    return (int)db()->lastInsertId();
}

/**
 * Update record by ID
 */
function dbUpdate(string $table, int $id, array $data): bool
{
    if (db() === null) return false;
    $table = dbEscapeIdentifier($table);
    $fields = array_keys($data);
    $setClause = implode(' = ?, ', $fields) . ' = ?';
    
    $stmt = db()->prepare("UPDATE {$table} SET {$setClause} WHERE id = ?");
    $values = array_values($data);
    $values[] = $id;
    
    return $stmt->execute($values);
}

/**
 * Delete record by ID
 */
function dbDelete(string $table, int $id): bool
{
    if (db() === null) return false;
    $table = dbEscapeIdentifier($table);
    $stmt = db()->prepare("DELETE FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    // true тільки якщо реально щось видалили
    return $stmt->rowCount() > 0;
}

/**
 * Paginated query
 */
function dbPaginate(string $table, int $perPage = 10, int $page = 1, string $orderBy = 'id DESC'): array
{
    if (db() === null) return [];
    $table = dbEscapeIdentifier($table);
    $offset = ($page - 1) * $perPage;
    $query = "SELECT * FROM {$table} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
    return db()->query($query)->fetchAll();
}

/**
 * Get total pages count
 */
function dbPages(string $table, int $perPage = 10): int
{
    $total = dbCount($table);
    return (int)ceil($total / $perPage);
}

/**
 * Escape identifier (table/column names) - basic sanitization
 */
function dbEscapeIdentifier(string $identifier): string
{
    // Без лапок: імена таблиць/колонок обмежені [a-zA-Z0-9_],
    // такий формат працює і в MySQL, і в PostgreSQL
    return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
}

// ==================== Admin Functions ====================

/**
 * Стартувати сесію один раз (безпечно для повторних викликів)
 */
function ensureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Find admin by username
 */
function adminGetByUsername(string $username): ?array
{
    if (db() === null) {
        return null;
    }
    $stmt = db()->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Verify admin password
 */
function adminVerifyPassword(string $username, string $password): ?array
{
    $admin = adminGetByUsername($username);
    
    if ($admin && password_verify($password, $admin['password'])) {
        return $admin;
    }
    
    return null;
}

/**
 * Start admin session
 */
function adminLogin(array $admin): void
{
    ensureSession();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_logged_in'] = true;
}

/**
 * Check if admin is logged in
 */
function adminIsLoggedIn(): bool
{
    ensureSession();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Logout admin
 */
function adminLogout(): void
{
    ensureSession();
    session_destroy();
}

/**
 * Require admin authentication (redirect to login if not logged in)
 */
function requireAdmin(): void
{
    if (!adminIsLoggedIn()) {
        header('Location: /?page=admin-login');
        exit;
    }
}

// ==================== Project Functions ====================

/**
 * Get all active projects for portfolio display
 */
function projectGetAllActive(): array
{
    // DB може бути недоступною локально — тоді показуємо порожній портфоліо
    if (db() === null) {
        return [];
    }
    $stmt = db()->prepare("SELECT * FROM projects WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all projects (for admin)
 */
function projectGetAll(): array
{
    return dbGetAll('projects', 'sort_order ASC, id DESC');
}

/**
 * Get project by ID
 */
function projectGetById(int $id): ?array
{
    return dbGetById('projects', $id);
}

/**
 * Create new project
 */
function projectCreate(array $data): int
{
    $defaults = [
        'title' => '',
        'description' => '',
        'github_url' => null,
        'site_url' => null,
        'image_url' => null,
        'tags' => null,
        'sort_order' => 0,
        'is_active' => 1,
    ];
    
    $data = array_merge($defaults, $data);
    return dbInsert('projects', $data);
}

/**
 * Update project
 */
function projectUpdate(int $id, array $data): bool
{
    return dbUpdate('projects', $id, $data);
}

/**
 * Delete project
 */
function projectDelete(int $id): bool
{
    return dbDelete('projects', $id);
}

/**
 * Toggle project active status
 */
function projectToggleActive(int $id): bool
{
    $project = projectGetById($id);
    if (!$project) {
        return false;
    }
    
    $newStatus = $project['is_active'] ? 0 : 1;
    return dbUpdate('projects', $id, ['is_active' => $newStatus]);
}

/**
 * Перемістити проєкт на один рядок вверх/вниз за sort_order (а потім id).
 * @param int $id ID проєкту
 * @param int $dir -1 (вверх) або 1 (вниз)
 */
function projectMove(int $id, int $dir): bool
{
    if (db() === null || !in_array($dir, [-1, 1], true)) {
        return false;
    }

    $project = projectGetById($id); // ORDER BY sort_order ASC, id DESC — так само, як у списках
    if (!$project) {
        return false;
    }

    $so = (int)$project['sort_order'];
    if ($dir === -1) {
        // попередній: менший sort_order, або рівний, але більший id
        $stmt = db()->prepare(
            "SELECT * FROM projects WHERE (sort_order < ?) OR (sort_order = ? AND id > ?)
             ORDER BY sort_order DESC, id ASC LIMIT 1"
        );
        $stmt->execute([$so, $so, $id]);
    } else {
        // наступний: більший sort_order, або рівний, але менший id
        $stmt = db()->prepare(
            "SELECT * FROM projects WHERE (sort_order > ?) OR (sort_order = ? AND id < ?)
             ORDER BY sort_order ASC, id DESC LIMIT 1"
        );
        $stmt->execute([$so, $so, $id]);
    }
    $neighbor = $stmt->fetch();

    if (!$neighbor) {
        return true; // край списку — переміщати нікуди, вважаємо успіхом
    }

    $nSo = (int)$neighbor['sort_order'];
    $neighborSo = ($nSo === $so) ? $so + $dir : $nSo;

    $upd = db()->prepare("UPDATE projects SET sort_order = ? WHERE id = ?");

    try {
        db()->beginTransaction();
        $upd->execute([$neighborSo, $id]);
        $upd->execute([$so, $neighbor['id']]);
        db()->commit();
        return true;
    } catch (Throwable $e) {
        db()->rollBack();
        error_log('projectMove error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Validate project data
 */
function projectValidate(array $data): array
{
    $errors = [];
    
    if (empty(trim($data['title'] ?? ''))) {
        $errors[] = 'Название проекта обязательно';
    } elseif (strlen(trim($data['title'])) > 100) {
        $errors[] = 'Название должно быть не более 100 символов';
    }
    
    if (!empty($data['github_url']) && !filter_var($data['github_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Некорректный GitHub URL';
    }
    
    if (!empty($data['site_url']) && !filter_var($data['site_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Некорректный URL сайта';
    }
    
    return $errors;
}
