<?php
require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

if (
    !isset($data["gmailclientid"]) || $data["gmailclientid"] == "" ||
    !isset($data["gmailclientsecret"]) || $data["gmailclientsecret"] == "" ||
    !isset($data["gmailrefreshtoken"]) || $data["gmailrefreshtoken"] == ""
) {
    die(json_encode([
        "success" => false,
        "message" => translate('fill_mandatory_fields', $i18n)
    ]));
}

$enabled = $data["enabled"];
$fromEmail = $data["fromemail"] ?? "";
$otherEmails = $data["otheremails"] ?? "";
$gmailClientId = $data["gmailclientid"];
$gmailClientSecret = $data["gmailclientsecret"];
$gmailRefreshToken = $data["gmailrefreshtoken"];

$query = "SELECT COUNT(*) FROM email_notifications WHERE user_id = :userId";
$stmt = $db->prepare($query);
$stmt->bindParam(":userId", $userId, PDO::PARAM_INT);
$result = $stmt->execute();

if ($result === false) {
    $response = [
        "success" => false,
        "message" => translate('error_saving_notifications', $i18n)
    ];
    echo json_encode($response);
} else {
    $row = $result->fetchArray();
    $count = $row[0];
    if ($count == 0) {
        $query = "INSERT INTO email_notifications (enabled, from_email, other_emails, auth_method, gmail_client_id, gmail_client_secret, gmail_refresh_token, user_id)
                          VALUES (:enabled, :fromEmail, :otherEmails, 'gmail_api', :gmailClientId, :gmailClientSecret, :gmailRefreshToken, :userId)";
    } else {
        $query = "UPDATE email_notifications
                          SET enabled = :enabled, from_email = :fromEmail, other_emails = :otherEmails,
                              auth_method = 'gmail_api', gmail_client_id = :gmailClientId, gmail_client_secret = :gmailClientSecret, gmail_refresh_token = :gmailRefreshToken
                          WHERE user_id = :userId";
    }

    $stmt = $db->prepare($query);
    $stmt->bindValue(':enabled', $enabled, PDO::PARAM_INT);
    $stmt->bindValue(':fromEmail', $fromEmail, PDO::PARAM_STR);
    $stmt->bindValue(':otherEmails', $otherEmails, PDO::PARAM_STR);
    $stmt->bindValue(':gmailClientId', $gmailClientId, PDO::PARAM_STR);
    $stmt->bindValue(':gmailClientSecret', $gmailClientSecret, PDO::PARAM_STR);
    $stmt->bindValue(':gmailRefreshToken', $gmailRefreshToken, PDO::PARAM_STR);
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $response = [
            "success" => true,
            "message" => translate('notifications_settings_saved', $i18n)
        ];
        echo json_encode($response);
    } else {
        $response = [
            "success" => false,
            "message" => translate('error_saving_notifications', $i18n)
        ];
        echo json_encode($response);
    }
}
