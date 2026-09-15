<?php

require_once __DIR__ . '/database.php';

try {
    $db = new WallosDatabase();
} catch (Throwable $exception) {
    die('Connection to the PostgreSQL database failed: ' . $exception->getMessage());
}

require_once 'i18n/languages.php';
require_once 'i18n/getlang.php';
require_once 'i18n/' . $lang . '.php';
require_once 'remember_me.php';
require_once __DIR__ . '/session_bootstrap.php';

wallos_bootstrap_session();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $userId = $_SESSION['userId'];
} else {
    // The PHP session can be garbage-collected (default ~24 min) long before
    // the "remember me" cookie should expire (30 days). Fall back to it here,
    // the same way full page loads do via checksession.php, so AJAX/API
    // endpoints don't silently behave as logged-out after an idle period.
    $restoredUser = restoreSessionFromRememberMeCookie($db);
    $userId = $restoredUser !== false ? $restoredUser['id'] : 0;
}

?>