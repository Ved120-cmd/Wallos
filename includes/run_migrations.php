<?php
// Expects $db to be set by the caller.

$migrationsDir = __DIR__ . '/../migrations/';

$completedMigrations = [];

$migrationTableExists = $db
    ->query("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'migrations'")
    ->fetchArray(PDO::FETCH_ASSOC) !== false;

if ($migrationTableExists) {
    $migrationQuery = $db->query('SELECT migration FROM migrations');
    while ($row = $migrationQuery->fetchArray(PDO::FETCH_ASSOC)) {
        $completedMigrations[] = str_replace('../../', '', $row['migration']);
    }
}

$allMigrations = array_map(
    fn($path) => 'migrations/' . basename($path),
    glob($migrationsDir . '*.php') ?: []
);
sort($allMigrations, SORT_STRING);

$requiredMigrations = array_diff($allMigrations, $completedMigrations);

if (count($requiredMigrations) === 0) {
    echo "No migrations to run.\n";
}

foreach ($requiredMigrations as $migration) {
    require_once $migrationsDir . basename($migration);

    $stmt = $db->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
    $stmt->bindValue(':migration', $migration, PDO::PARAM_STR);
    $stmt->execute();

    echo sprintf("Migration %s completed successfully.\n", $migration);
}
