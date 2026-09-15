<?php

require_once __DIR__ . '/../../includes/session_bootstrap.php';

wallos_bootstrap_session();

$userId = 0;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $userId = $_SESSION['userId'];
}

if (php_sapi_name() !== 'cli') {
    if ($userId !== 1) {
        die("Unauthorized");
    }
}

?>