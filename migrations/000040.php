<?php
// This migration adds a pushplus_notifications table to store PushPlus notification settings.

$tableQuery = $db->query("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'pushplus_notifications'");
$tableExists = $tableQuery->fetchArray(PDO::FETCH_ASSOC);
if ($tableExists === false) {
    $db->exec("
        CREATE TABLE pushplus_notifications (
            enabled INTEGER NOT NULL DEFAULT 0,
            token TEXT,
            user_id INTEGER
        );
    ");
}