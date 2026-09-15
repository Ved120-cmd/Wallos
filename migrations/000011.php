<?php
    // This migration adds a "order" column to the payment_methods table so that they can be sorted and initializes all values to their id.

    $columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'payment_methods' AND column_name = 'order'");
    $columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

    if ($columnRequired) {
        $db->exec('ALTER TABLE payment_methods ADD COLUMN "order" INTEGER DEFAULT 0');
        $db->exec('UPDATE payment_methods SET "order" = id');
    }


?>