<?php
// This migration adds a "language" column to the user table and sets all values to english.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'language'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE user ADD COLUMN language TEXT DEFAULT 'en'");
    $db->exec("UPDATE user SET language = 'en'");
}
