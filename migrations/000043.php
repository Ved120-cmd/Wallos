<?php

/* * This migration adds a column to the admin table to store a comma-separated 
* allowlist of hostnames and IPs that can be used in webhook notifications. 
* This prevents SSRF attacks on internal services.
*/

// Check if the column already exists to prevent errors on multiple runs
$query = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'admin'");
$columnExists = false;

while ($row = $query->fetchArray(PDO::FETCH_ASSOC)) {
    if ($row['name'] === 'local_webhook_notifications_allowlist') {
        $columnExists = true;
        break;
    }
}

if (!$columnExists) {
    // Add the column with an empty string as the default
    $db->exec("ALTER TABLE admin ADD COLUMN local_webhook_notifications_allowlist TEXT DEFAULT ''");
}

?>