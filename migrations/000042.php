<?php

/* 
* This migration adds a table to store Serverchan notification settings
*/

$tableQuery = $db->query("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'serverchan_notifications'");
$tableExists = $tableQuery->fetchArray(PDO::FETCH_ASSOC);

if (!$tableExists) {
    $db->exec("CREATE TABLE serverchan_notifications (
        enabled INTEGER DEFAULT 0,
        sendkey TEXT DEFAULT '',
        user_id INTEGER,
        FOREIGN KEY (user_id) REFERENCES user(id)
    )");
}

?>