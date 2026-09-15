<?php
require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';

function addFolderToZip($dir, $zipArchive, $zipdir = '')
{
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            //Add the directory
            if (!empty($zipdir))
                $zipArchive->addEmptyDir($zipdir);
            while (($file = readdir($dh)) !== false) {
                // Skip '.' and '..'
                if ($file == "." || $file == "..") {
                    continue;
                }
                //If it's a folder, run the function again!
                if (is_dir($dir . $file)) {
                    $newdir = $dir . $file . '/';
                    addFolderToZip($newdir, $zipArchive, $zipdir . $file . '/');
                } else {
                    //Add the files
                    $zipArchive->addFile($dir . $file, $zipdir . $file);
                }
            }
        }
    } else {
        die(json_encode([
            "success" => false,
            "message" => "Directory does not exist: $dir"
        ]));
    }
}

function createPostgresDump(WallosDatabase $db): string
{
    $config = $db->getConnectionConfig();
    $dumpPath = tempnam(sys_get_temp_dir(), 'wallos_dump_');
    if ($dumpPath === false) {
        throw new RuntimeException('Unable to create PostgreSQL dump file.');
    }

    $arguments = [
        'pg_dump', '--format=plain', '--no-owner', '--no-privileges',
        '--file', $dumpPath, '--host', $config['host'], '--port', (string) $config['port'],
        '--username', $config['user'], $config['name'],
    ];
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    $environment = ['PGPASSWORD' => $config['password']];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment);
    if (!is_resource($process)) {
        @unlink($dumpPath);
        throw new RuntimeException('Unable to start pg_dump.');
    }

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        @unlink($dumpPath);
        throw new RuntimeException('PostgreSQL backup failed: ' . trim($error));
    }

    return $dumpPath;
}

// Build the archive OUTSIDE the web root. Previously it was written to
// ../../.tmp/ with a uniqid()-based name and served statically by nginx, which
// let anyone who could guess the (timestamp-derived, low-entropy) filename
// download the full database unauthenticated. The backup is now streamed
// directly to the authenticated admin below and never persists in a
// web-accessible location.
$zipname = tempnam(sys_get_temp_dir(), 'wallos_backup_');
if ($zipname === false) {
    die(json_encode([
        "success" => false,
        "message" => translate('cannot_open_zip', $i18n)
    ]));
}

$dumpPath = null;
$zip = new ZipArchive();
if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    @unlink($zipname);
    die(json_encode([
        "success" => false,
        "message" => translate('cannot_open_zip', $i18n)
    ]));
}

try {
    $dumpPath = createPostgresDump($db);
    $zip->addFile($dumpPath, 'wallos.sql');
} catch (Throwable $exception) {
    $zip->close();
    @unlink($zipname);
    die(json_encode([
        "success" => false,
        "message" => $exception->getMessage()
    ]));
}
addFolderToZip('../../images/uploads/', $zip);

if ($zip->close() === false) {
    @unlink($zipname);
    die(json_encode([
        "success" => false,
        "message" => "Failed to finalize the zip file"
    ]));
}

// Discard any buffered output (e.g. a stray newline from an included file)
// so it cannot corrupt the binary archive that follows.
while (ob_get_level() > 0) {
    ob_end_clean();
}

// ZipArchive wrote through its own handle after tempnam() created the file at
// 0 bytes, so clear the stat cache before reading its size for Content-Length.
clearstatcache(true, $zipname);

$downloadName = 'Wallos-Backup-' . date('Ymd-His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($zipname));
header('Cache-Control: no-store');

readfile($zipname);
unlink($zipname);
if ($dumpPath !== null) {
    unlink($dumpPath);
}
exit;