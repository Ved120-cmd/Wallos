<?php

class WallosDatabase
{
    private PDO $pdo;
    private int $lastRowCount = 0;
    private string $lastError = '';

    public function __construct()
    {
        $this->pdo = new PDO($this->buildDsn(), $this->databaseConfig()['user'], $this->databaseConfig()['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);
    }

    public function prepare(string $query): WallosStatement|false
    {
        try {
            return new WallosStatement($this, $this->pdo->prepare($this->normalizeSql($query)));
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }
    }

    public function query(string $query): WallosResult|false
    {
        try {
            $statement = $this->pdo->query($this->normalizeSql($query));
            $this->lastRowCount = $statement->rowCount();

            return new WallosResult($statement);
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }
    }

    public function exec(string $query): int|false
    {
        try {
            $this->lastRowCount = $this->pdo->exec($this->normalizeSql($query));
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }

        return $this->lastRowCount;
    }

    public function querySingle(string $query, bool $entireRow = false): mixed
    {
        $row = $this->query($query)->fetchArray($entireRow ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);

        if ($row === false) {
            return false;
        }

        return $entireRow ? $row : $row[0];
    }

    public function lastInsertRowID(): int|string
    {
        return $this->pdo->lastInsertId();
    }

    public function changes(): int
    {
        return $this->lastRowCount;
    }

    public function lastErrorMsg(): string
    {
        return $this->lastError;
    }

    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function beginTransaction(): bool
    {
        return $this->begin();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function close(): void
    {
    }

    public function getConnectionConfig(): array
    {
        return $this->databaseConfig();
    }

    public function execute(PDOStatement $statement): WallosResult|false
    {
        try {
            $statement->execute();
            $this->lastRowCount = $statement->rowCount();
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }

        return new WallosResult($statement);
    }

    private function buildDsn(): string
    {
        $config = $this->databaseConfig();

        return sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['sslmode']
        );
    }

    private function normalizeSql(string $query): string
    {
        $query = str_replace('`', '"', $query);
        $query = preg_replace('/\b(FROM|JOIN|UPDATE|INTO|TABLE|REFERENCES|DELETE\s+FROM)\s+user\b/i', '$1 "user"', $query);

        return $query;
    }

    private function databaseConfig(): array
    {
        $url = getenv('DATABASE_URL');
        if ($url !== false && $url !== '') {
            $parts = parse_url($url);
            if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
                throw new RuntimeException('DATABASE_URL must be a valid PostgreSQL connection URL.');
            }

            parse_str($parts['query'] ?? '', $query);

            return [
                'host' => $parts['host'],
                'port' => $parts['port'] ?? 5432,
                'name' => rawurldecode(ltrim($parts['path'], '/')),
                'user' => rawurldecode($parts['user'] ?? ''),
                'password' => rawurldecode($parts['pass'] ?? ''),
                'sslmode' => $query['sslmode'] ?? getenv('DB_SSLMODE') ?: 'require',
            ];
        }

        $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        foreach ($required as $variable) {
            if (($value = getenv($variable)) === false || $value === '') {
                throw new RuntimeException('Missing required database environment variable: ' . $variable);
            }
        }

        return [
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT') ?: 5432,
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'sslmode' => getenv('DB_SSLMODE') ?: 'require',
        ];
    }
}

class WallosStatement
{
    private WallosDatabase $database;
    private PDOStatement $statement;

    public function __construct(WallosDatabase $database, PDOStatement $statement)
    {
        $this->database = $database;
        $this->statement = $statement;
    }

    public function bindValue(int|string $parameter, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        return $this->statement->bindValue($parameter, $value, $this->pdoType($type));
    }

    public function bindParam(int|string $parameter, mixed &$variable, int $type = PDO::PARAM_STR): bool
    {
        return $this->statement->bindParam($parameter, $variable, $this->pdoType($type));
    }

    public function execute(): WallosResult|false
    {
        return $this->database->execute($this->statement);
    }

    public function close(): void
    {
        $this->statement->closeCursor();
    }

    private function pdoType(int $type): int
    {
        return match ($type) {
            PDO::PARAM_BOOL => PDO::PARAM_BOOL,
            PDO::PARAM_INT => PDO::PARAM_INT,
            PDO::PARAM_NULL => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}

class WallosResult
{
    private PDOStatement $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function fetchArray(int $mode = PDO::FETCH_BOTH): array|false
    {
        return $this->statement->fetch($this->fetchMode($mode));
    }

    private function fetchMode(int $mode): int
    {
        return match ($mode) {
            PDO::FETCH_ASSOC => PDO::FETCH_ASSOC,
            PDO::FETCH_NUM => PDO::FETCH_NUM,
            default => PDO::FETCH_BOTH,
        };
    }
}

?>
