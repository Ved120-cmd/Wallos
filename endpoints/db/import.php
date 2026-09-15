<?php

require_once '../../includes/connect_endpoint.php';

function clearImportFolder(): void
{
    if (!is_dir('../../.tmp')) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('../../.tmp', RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        ($fileinfo->isDir() ? 'rmdir' : 'unlink')($fileinfo->getRealPath());
    }
}

function importPostgresDump(WallosDatabase $db, string $dumpPath): void
{
    $config = $db->getConnectionConfig();
    $arguments = [
        'psql', '--set', 'ON_ERROR_STOP=1', '--single-transaction', '--clean', '--if-exists',
        '--host', $config['host'], '--port', (string) $config['port'], '--username', $config['user'],
        '--dbname', $config['name'], '--file', $dumpPath,
    ];
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['PGPASSWORD' => $config['password']]);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start psql.');
    }

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException('PostgreSQL import failed: ' . trim($error));
    }
}

$count = $db->query('SELECT COUNT(*) FROM "user"')->fetchArray(PDO::FETCH_NUM);
if ((int) $count[0] > 0) {
    die(json_encode(["success" => false, "message" => "Denied"]));
}

$setupToken = getenv('WALLOS_SETUP_TOKEN') ?: '';
$submittedToken = $_POST['setup_token'] ?? '';
if ($setupToken === '' || !hash_equals($setupToken, $submittedToken)) {
    die(json_encode(["success" => false, "message" => "Invalid setup token"]));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    die(json_encode(["success" => false, "message" => "No valid file uploaded"]));
}

if (!is_dir('../../.tmp')) {
    mkdir('../../.tmp', 0700, true);
}
$fileDestination = '../../.tmp/import.zip';
move_uploaded_file($_FILES['file']['tmp_name'], $fileDestination);
$zip = new ZipArchive();
if ($zip->open($fileDestination) !== true) {
    clearImportFolder();
    die(json_encode(["success" => false, "message" => "Failed to extract the uploaded file"]));
}

for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = str_replace('\\', '/', $zip->getNameIndex($i));
    if ($entry === '' || $entry[0] === '/' || in_array('..', explode('/', $entry), true)) {
        $zip->close();
        clearImportFolder();
        die(json_encode(["success" => false, "message" => "Invalid backup file"]));
    }
}
$zip->extractTo('../../.tmp/import/');
$zip->close();

$dumpPath = '../../.tmp/import/wallos.sql';
if (!file_exists($dumpPath)) {
    clearImportFolder();
    die(json_encode(["success" => false, "message" => "wallos.sql does not exist in the backup file"]));
}

try {
    importPostgresDump($db, $dumpPath);
    clearImportFolder();
    require_once __DIR__ . '/../../includes/run_migrations.php';
    echo json_encode(["success" => true, "message" => translate("success", $i18n)]);
} catch (Throwable $exception) {
    clearImportFolder();
    die(json_encode(["success" => false, "message" => $exception->getMessage()]));
}

?>
