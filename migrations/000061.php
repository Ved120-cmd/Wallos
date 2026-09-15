<?php

// PHP sessions used to live only on the container's local disk, which does
// not survive a restart/redeploy and isn't shared across instances. Moving
// them into Postgres was silently dropping logged-in users' sessions
// (surfacing as "Invalid CSRF token" on form submits) whenever the
// container recycled.
$db->exec(
    'CREATE TABLE IF NOT EXISTS sessions (
        id TEXT PRIMARY KEY,
        data TEXT NOT NULL,
        last_access TIMESTAMP NOT NULL DEFAULT NOW()
    )'
);

$db->exec('CREATE INDEX IF NOT EXISTS sessions_last_access_idx ON sessions (last_access)');

?>
