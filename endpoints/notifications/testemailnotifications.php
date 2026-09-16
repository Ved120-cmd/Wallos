<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/gmail_api_mailer.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

if (
    !isset($data["gmailclientid"]) || $data["gmailclientid"] == "" ||
    !isset($data["gmailclientsecret"]) || $data["gmailclientsecret"] == "" ||
    !isset($data["gmailrefreshtoken"]) || $data["gmailrefreshtoken"] == ""
) {
    die(json_encode([
        "success" => false,
        "message" => translate('fill_all_fields', $i18n)
    ]));
}

$userStmt = $db->prepare('SELECT email, username FROM user WHERE id = :userId');
$userStmt->bindValue(':userId', $userId, PDO::PARAM_INT);
$userResult = $userStmt->execute();
$user = $userResult ? $userResult->fetchArray(PDO::FETCH_ASSOC) : false;

if ($user === false || empty($user['email'])) {
    die(json_encode([
        "success" => false,
        "message" => translate('error', $i18n)
    ]));
}

try {
    send_gmail_api_message(
        $data["gmailclientid"],
        $data["gmailclientsecret"],
        $data["gmailrefreshtoken"],
        $data["fromemail"] ?? "",
        'Wallos App',
        [['email' => $user['email'], 'name' => $user['username']]],
        [],
        translate('wallos_notification', $i18n),
        translate('test_notification', $i18n)
    );

    die(json_encode([
        "success" => true,
        "message" => translate('notification_sent_successfuly', $i18n)
    ]));
} catch (GmailApiMailerException $e) {
    die(json_encode([
        "success" => false,
        "message" => translate('email_error', $i18n) . $e->getMessage()
    ]));
}
