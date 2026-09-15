<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';

function emptyRestoreFolder(): void
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

function restorePostgresDump(WallosDatabase $db, string $dumpPath): void
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
        throw new RuntimeException('PostgreSQL restore failed: ' . trim($error));
    }
}

function replaceLogosFromRestore(): void
{
    if (!is_dir('../../.tmp/restore/logos/')) {
        return;
    }

    $destinationRoot = '../../images/uploads/';
    if (!is_dir($destinationRoot)) {
        mkdir($destinationRoot, 0755, true);
    }

    $source = new RecursiveDirectoryIterator('../../.tmp/restore/logos/', FilesystemIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($source) as $filePath) {
        if (!in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            continue;
        }

        $destination = str_replace('../../.tmp/restore/', $destinationRoot, (string) $filePath);
        $destinationDir = pathinfo($destination, PATHINFO_DIRNAME);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        copy($filePath, $destination);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    echo json_encode(["success" => false, "message" => "No valid file uploaded"]);
    exit;
}

$fileDestination = '../../.tmp/restore.zip';
if (!is_dir('../../.tmp')) {
    mkdir('../../.tmp', 0700, true);
}
move_uploaded_file($_FILES['file']['tmp_name'], $fileDestination);
$zip = new ZipArchive();
if ($zip->open($fileDestination) !== true) {
    emptyRestoreFolder();
    die(json_encode(["success" => false, "message" => "Failed to extract the uploaded file"]));
}

for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = str_replace('\\', '/', $zip->getNameIndex($i));
    if ($entry === '' || $entry[0] === '/' || in_array('..', explode('/', $entry), true)
        || in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'htaccess'], true)) {
        $zip->close();
        emptyRestoreFolder();
        die(json_encode(["success" => false, "message" => "Invalid backup file"]));
    }
}

$zip->extractTo('../../.tmp/restore/');
$zip->close();
$dumpPath = '../../.tmp/restore/wallos.sql';
if (!file_exists($dumpPath)) {
    emptyRestoreFolder();
    die(json_encode(["success" => false, "message" => "wallos.sql does not exist in the backup file"]));
}

try {
    restorePostgresDump($db, $dumpPath);
    replaceLogosFromRestore();
    emptyRestoreFolder();
    require_once __DIR__ . '/../../includes/run_migrations.php';
    echo json_encode(["success" => true, "message" => translate("success", $i18n)]);
} catch (Throwable $exception) {
    emptyRestoreFolder();
    die(json_encode(["success" => false, "message" => $exception->getMessage()]));
}

?>
