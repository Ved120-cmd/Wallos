<?php
// This migration adds columns for a per-subscription themed logo variant.
// logo_text_color records whether the uploaded logo's text/ink is 'dark' or
// 'light' (NULL when neither/ambiguous, e.g. a colorful logo with no plain
// black/white text). logo_variant is the filename of a generated version
// with just that text recolored for the opposite theme, so a black-text
// logo stays legible on the dark theme and vice versa. Existing rows are
// left NULL, which means "always show the original logo" (unchanged
// behavior) until the subscription's logo is re-uploaded.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'subscriptions' AND column_name = 'logo_text_color'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE subscriptions ADD COLUMN logo_text_color TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE subscriptions ADD COLUMN logo_variant TEXT DEFAULT NULL");
}
