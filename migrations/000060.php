<?php

// Subscription start dates are calendar dates, not integer values. Convert
// legacy integer timestamps while preserving already-valid ISO date strings.
$column = $db->query("SELECT data_type FROM information_schema.columns
    WHERE table_schema = current_schema()
    AND table_name = 'subscriptions'
    AND column_name = 'start_date'")->fetchArray(PDO::FETCH_ASSOC);

if ($column !== false && $column['data_type'] !== 'date') {
    $db->exec("ALTER TABLE subscriptions
        ALTER COLUMN start_date TYPE DATE
        USING CASE
            WHEN start_date IS NULL THEN NULL
            WHEN start_date::text ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN start_date::text::date
            ELSE to_timestamp(start_date::double precision)::date
        END");
}

?>