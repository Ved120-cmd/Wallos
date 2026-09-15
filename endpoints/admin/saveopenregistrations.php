<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

$openRegistrations = $data['open_registrations'];
$maxUsers = $data['max_users'];
$requireEmailVerification = $data['require_email_validation'];
$serverUrl = $data['server_url'];
$disableLogin = $data['disable_login'];

if ($disableLogin == 1) {
    if ($openRegistrations == 1) {
        echo json_encode([
            "success" => false,
            "message" => translate('error', $i18n)
        ]);
        die();
    }

    $sql = "SELECT COUNT(*) as userCount FROM user";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute();
    $row = $result->fetchArray(PDO::FETCH_ASSOC);
    $userCount = $row['userCount'];

    if ($userCount > 1) {
        echo json_encode([
            "success" => false,
            "message" => translate('error', $i18n)
        ]);
        die();
    }
}

if ($requireEmailVerification == 1 && $serverUrl == "") {
    echo json_encode([
        "success" => false,
        "message" => translate('fill_all_fields', $i18n)
    ]);
    die();
}

$sql = "UPDATE admin SET registrations_open = :openRegistrations, max_users = :maxUsers, require_email_verification = :requireEmailVerification, server_url = :serverUrl, login_disabled = :disableLogin WHERE id = 1";
$stmt = $db->prepare($sql);
$stmt->bindParam(':openRegistrations', $openRegistrations, PDO::PARAM_INT);
$stmt->bindParam(':maxUsers', $maxUsers, PDO::PARAM_INT);
$stmt->bindParam(':requireEmailVerification', $requireEmailVerification, PDO::PARAM_INT);
$stmt->bindParam(':serverUrl', $serverUrl, PDO::PARAM_STR);
$stmt->bindParam(':disableLogin', $disableLogin, PDO::PARAM_INT);
$result = $stmt->execute();

if ($result) {
    echo json_encode([
        "success" => true,
        "message" => translate('success', $i18n)
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => translate('error', $i18n)
    ]);
}