<?php
    // This migration adds a "encryption" column to the notifications table so that the encryption type can be stored.

    $columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'notifications' AND column_name = 'encryption'");
    $columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

    if ($columnRequired) {
        $db->exec("ALTER TABLE notifications ADD COLUMN \"encryption\" TEXT DEFAULT 'tls'");
        $db->exec("UPDATE notifications SET \"encryption\" = 'tls'");
    }
?>