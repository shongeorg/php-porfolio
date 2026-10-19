<?php
/**
 * Сесії в Postgres (Neon).
 *
 * На Vercel контейнер може мати кілька інстансів, а /tmp у кожного свій —
 * тому файлові сесії губляться і адмінка «викидає» з логіна. Тримаємо сесії
 * в тій самій базі, що й решту даних.
 */

class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private bool $tableReady = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function ensureTable(): void
    {
        if ($this->tableReady) {
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(128) PRIMARY KEY,
                data TEXT NOT NULL,
                last_activity BIGINT NOT NULL
            )'
        );
        $this->tableReady = true;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $this->ensureTable();
            $stmt = $this->pdo->prepare(
                'SELECT data FROM sessions WHERE id = ? AND last_activity >= ?'
            );
            $stmt->execute([$id, time() - (int) ini_get('session.gc_maxlifetime')]);
            $data = $stmt->fetchColumn();

            return $data === false ? '' : (string) $data;
        } catch (Throwable $e) {
            error_log('session read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $this->ensureTable();
            $stmt = $this->pdo->prepare(
                'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
                 ON CONFLICT (id) DO UPDATE
                 SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity'
            );

            return $stmt->execute([$id, $data, time()]);
        } catch (Throwable $e) {
            error_log('session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->ensureTable();
            $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = ?');

            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            error_log('session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $this->ensureTable();
            $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < ?');
            $stmt->execute([time() - $max_lifetime]);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log('session gc error: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Запустити сесію: у Postgres, якщо база доступна, інакше — файли.
 */
function sessionDbStart(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $pdo = db();
    if ($pdo !== null && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
        session_set_save_handler(new DbSessionHandler($pdo), true);
    }

    session_start();
}
