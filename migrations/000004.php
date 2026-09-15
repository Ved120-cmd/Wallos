<?php
    // This migration adds a URL column to the subscriptions table.

    $columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'subscriptions' AND column_name = 'url'");
    $columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

    if ($columnRequired) {
        $db->exec('ALTER TABLE subscriptions ADD COLUMN url VARCHAR(255);');
    }

?>