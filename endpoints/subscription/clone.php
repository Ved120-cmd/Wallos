<?php
require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

$subscriptionId = $data["id"];
$query = "SELECT * FROM subscriptions WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $subscriptionId, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$result = $stmt->execute();
$subscriptionToClone = $result->fetchArray(PDO::FETCH_ASSOC);
if ($subscriptionToClone === false) {
    die(json_encode([
        "success" => false,
        "message" => translate("error", $i18n)
    ]));
}

$query = "INSERT INTO subscriptions (name, logo, price, currency_id, next_payment, auto_renew, start_date, cycle, frequency, notes, payment_method_id, payer_user_id, category_id, notify, url, inactive, notify_days_before, user_id, cancellation_date, replacement_subscription_id) VALUES (:name, :logo, :price, :currency_id, :next_payment, :auto_renew, :start_date, :cycle, :frequency, :notes, :payment_method_id, :payer_user_id, :category_id, :notify, :url, :inactive, :notify_days_before, :user_id, :cancellation_date, :replacement_subscription_id)";
$cloneStmt = $db->prepare($query);
$cloneStmt->bindValue(':name', $subscriptionToClone['name'], PDO::PARAM_STR);
$cloneStmt->bindValue(':logo', $subscriptionToClone['logo'], PDO::PARAM_STR);
$cloneStmt->bindValue(':price', $subscriptionToClone['price'], PDO::PARAM_STR);
$cloneStmt->bindValue(':currency_id', $subscriptionToClone['currency_id'], PDO::PARAM_INT);
$cloneStmt->bindValue(':next_payment', $subscriptionToClone['next_payment'], PDO::PARAM_STR);
$cloneStmt->bindValue(':auto_renew', $subscriptionToClone['auto_renew'], PDO::PARAM_INT);
$cloneStmt->bindValue(':start_date', $subscriptionToClone['start_date'], PDO::PARAM_STR);
$cloneStmt->bindValue(':cycle', $subscriptionToClone['cycle'], PDO::PARAM_STR);
$cloneStmt->bindValue(':frequency', $subscriptionToClone['frequency'], PDO::PARAM_INT);
$cloneStmt->bindValue(':notes', $subscriptionToClone['notes'], PDO::PARAM_STR);
$cloneStmt->bindValue(':payment_method_id', $subscriptionToClone['payment_method_id'], PDO::PARAM_INT);
$cloneStmt->bindValue(':payer_user_id', $subscriptionToClone['payer_user_id'], PDO::PARAM_INT);
$cloneStmt->bindValue(':category_id', $subscriptionToClone['category_id'], PDO::PARAM_INT);
$cloneStmt->bindValue(':notify', $subscriptionToClone['notify'], PDO::PARAM_INT);
$cloneStmt->bindValue(':url', $subscriptionToClone['url'], PDO::PARAM_STR);
$cloneStmt->bindValue(':inactive', $subscriptionToClone['inactive'], PDO::PARAM_INT);
$cloneStmt->bindValue(':notify_days_before', $subscriptionToClone['notify_days_before'], PDO::PARAM_INT);
$cloneStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$cloneStmt->bindValue(':cancellation_date', $subscriptionToClone['cancellation_date'], PDO::PARAM_STR);
$cloneStmt->bindValue(':replacement_subscription_id', $subscriptionToClone['replacement_subscription_id'], PDO::PARAM_INT);

if ($cloneStmt->execute()) {
    $response = [
        "success" => true,
        "message" => translate('success', $i18n),
        "id" => $db->lastInsertRowID()
    ];
    echo json_encode($response);
} else {
    die(json_encode([
        "success" => false,
        "message" => translate("error", $i18n)
    ]));
}

$db->close();
?>