<?php

require_once __DIR__ . '/session_handler.php';

/**
 * Starts the PHP session, backed by Postgres when a database connection is
 * available (see PostgresSessionHandler) so sessions survive container
 * restarts/redeploys instead of quietly disappearing with the local
 * filesystem. Falls back to PHP's default session handling if no database
 * connection has been established yet (e.g. cron scripts).
 *
 * Replaces the session_set_cookie_params()+session_start() boilerplate that
 * used to be duplicated across every entry point.
 */
function wallos_bootstrap_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secondsInMonth = 30 * 24 * 60 * 60;

    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof WallosDatabase) {
        try {
            $handler = new PostgresSessionHandler($GLOBALS['db']->getConnectionConfig(), $secondsInMonth);
            session_set_save_handler($handler, true);
        } catch (Throwable $exception) {
            error_log('Wallos: falling back to default session storage: ' . $exception->getMessage());
        }
    }

    session_set_cookie_params([
        'lifetime' => $secondsInMonth,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

?>
