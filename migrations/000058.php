<?php

// Subscription notes used to be stored HTML-escaped (htmlspecialchars() at
// save time, decoded back only when populating the edit form). Notes are
// now rendered through a Markdown parser instead, which escapes at render
// time, so storage must go back to holding plain text. This one-time
// migration decodes existing rows so old notes don't render double-escaped
// (or with literal &amp;/&#039;) under the new renderer.
//
// Safe to run twice: decoding plain text (no entities) is a no-op.

$result = $db->query("SELECT id, notes FROM subscriptions WHERE notes IS NOT NULL AND notes != ''");

$stmt = $db->prepare('UPDATE subscriptions SET notes = :notes WHERE id = :id');
while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
    $decoded = html_entity_decode($row['notes'], ENT_QUOTES, 'UTF-8');
    $stmt->bindValue(':notes', $decoded, PDO::PARAM_STR);
    $stmt->bindValue(':id', $row['id'], PDO::PARAM_INT);
    $stmt->execute();
}

?>
