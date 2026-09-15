<?php

/* * This migration adds a column to the admin table letting the admin opt
* standard (non-admin) users into the existing Webhook Allowlist for
* notification SSRF checks. Without it, any private/internal address is
* always blocked for standard users regardless of the allowlist.
*/

$column = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'admin' AND column_name = 'allow_standard_users_local_webhooks'");
if ($column->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE admin ADD COLUMN allow_standard_users_local_webhooks INTEGER DEFAULT 0');
}

?>
