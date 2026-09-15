<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';

$paymentMethods = $_POST['paymentMethodIds'];
$order = 1;

foreach ($paymentMethods as $paymentMethodId) {
    $sql = "UPDATE payment_methods SET \"order\" = :order WHERE id = :paymentMethodId and user_id = :userId";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':order', $order, PDO::PARAM_INT);
    $stmt->bindParam(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $result = $stmt->execute();
    $order++;
}

$response = [
    "success" => true,
    "message" => translate("sort_order_saved", $i18n)
];
echo json_encode($response);

?>