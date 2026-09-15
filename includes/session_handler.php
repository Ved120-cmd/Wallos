<?php

/**
 * Stores PHP session data in Postgres instead of the container's local
 * filesystem. Local files don't survive a container restart/redeploy (and
 * aren't shared across instances), which was silently invalidating
 * already-logged-in users' sessions - most visibly as "Invalid CSRF token"
 * errors on form submits, since the CSRF secret lived only in that lost
 * session data.
 *
 * Uses its own PDO connection (rather than the shared WallosDatabase
 * instance) so session reads/writes never interact with whatever
 * transaction the rest of the request may have open.
 */
class PostgresSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private int $maxLifetime;

    public function __construct(array $dbConfig, int $maxLifetime)
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['name'],
            $dbConfig['sslmode']
        );

        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->maxLifetime = $maxLifetime;
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
            $stmt = $this->pdo->prepare(
                'SELECT data FROM sessions WHERE id = :id AND last_access > (NOW() - make_interval(secs => :maxLifetime))'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':maxLifetime', $this->maxLifetime, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row !== false ? base64_decode($row['data']) : '';
        } catch (Throwable $exception) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO sessions (id, data, last_access) VALUES (:id, :data, NOW())
                 ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_access = EXCLUDED.last_access'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);
            $stmt->bindValue(':data', base64_encode($data), PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM sessions WHERE last_access < (NOW() - make_interval(secs => :maxLifetime))'
            );
            $stmt->bindValue(':maxLifetime', $max_lifetime, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (Throwable $exception) {
            return false;
        }
    }
}

?>
