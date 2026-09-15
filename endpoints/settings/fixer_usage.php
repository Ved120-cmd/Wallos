<?php
require_once '../../includes/connect_endpoint.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(["success" => false]));
}

// Usage is only available for the apilayer provider; it is captured from the
// response headers of rate updates, so this endpoint never calls the API.
if ($db->querySingle("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'fixer' AND column_name = 'usage_used'") == 0) {
    die(json_encode(["success" => false]));
}

$stmt = $db->prepare("SELECT provider, usage_used, usage_limit FROM fixer WHERE user_id = :userId");
$stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
$result = $stmt->execute();
$row = $result ? $result->fetchArray(PDO::FETCH_ASSOC) : false;

if (!$row || (int) $row['provider'] !== 1 || $row['usage_used'] === null || !$row['usage_limit']) {
    die(json_encode(["success" => false]));
}

die(json_encode([
    "success" => true,
    "used" => (int) $row['usage_used'],
    "total" => (int) $row['usage_limit'],
]));
