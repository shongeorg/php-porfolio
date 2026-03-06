<?php
/**
 * Database connection and helper functions
 * Pure PHP - no frameworks, no Composer
 */

/**
 * Get singleton PDO database connection
 * @return PDO|null
 */
function db(): ?PDO
{
    static $dbh = null;
    
    if ($dbh !== null) {
        return $dbh;
    }
    
    try {
        $dbh = new PDO(
    "mysql:dbname=if0_41319385_pankit;host=sql100.infinityfree.com;charset=utf8mb4;port=3306",
    "if0_41319385",
    "K1QjjXi2LrX",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
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
    $table = dbEscapeIdentifier($table);
    return db()->query("SELECT * FROM {$table} ORDER BY {$orderBy}")->fetchAll();
}

/**
 * Count records in table
 */
function dbCount(string $table): int
{
    $table = dbEscapeIdentifier($table);
    return (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

/**
 * Get single record by ID
 */
function dbGetById(string $table, int $id): ?array
{
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
    $table = dbEscapeIdentifier($table);
    $fields = array_keys($data);
    $columns = '`' . implode('`, `', $fields) . '`';
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
    $table = dbEscapeIdentifier($table);
    $stmt = db()->prepare("DELETE FROM {$table} WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Paginated query
 */
function dbPaginate(string $table, int $perPage = 10, int $page = 1, string $orderBy = 'id DESC'): array
{
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
    return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $identifier) . '`';
}

// ==================== Admin Functions ====================

/**
 * Find admin by username
 */
function adminGetByUsername(string $username): ?array
{
    $stmt = db()->prepare("SELECT * FROM `admin` WHERE username = ?");
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
    session_start();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_logged_in'] = true;
}

/**
 * Check if admin is logged in
 */
function adminIsLoggedIn(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Logout admin
 */
function adminLogout(): void
{
    session_start();
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
    $stmt = db()->prepare("SELECT * FROM `projects` WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
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
