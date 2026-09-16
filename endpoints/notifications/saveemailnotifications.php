<?php
require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint.php';
require_once '../../includes/ssrf_helper.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

$authMethod = isset($data["authmethod"]) && $data["authmethod"] === "gmail_api" ? "gmail_api" : "smtp";

if ($authMethod === "gmail_api") {
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
} else if (
    !isset($data["smtpaddress"]) || $data["smtpaddress"] == "" ||
    !isset($data["smtpport"]) || $data["smtpport"] == ""
) {
    $response = [
        "success" => false,
        "message" => translate('fill_mandatory_fields', $i18n)
    ];
    echo json_encode($response);
    exit;
}

$enabled = $data["enabled"];
$smtpAddress = $data["smtpaddress"] ?? "";
$smtpPort = $data["smtpport"] ?? "";
$encryption = "tls";
if (isset($data["encryption"])) {
    $encryption = $data["encryption"];
}
$smtpUsername = $data["smtpusername"] ?? "";
$smtpPassword = $data["smtppassword"] ?? "";
$fromEmail = $data["fromemail"] ?? "";
$otherEmails = $data["otheremails"] ?? "";
$gmailClientId = $data["gmailclientid"] ?? "";
$gmailClientSecret = $data["gmailclientsecret"] ?? "";
$gmailRefreshToken = $data["gmailrefreshtoken"] ?? "";

if ($authMethod === "smtp" && !validate_smtp_host($smtpAddress, (int) $smtpPort, $db)) {
    die(json_encode([
        "success" => false,
        "message" => "Security Error: SMTP host must not target link-local or loopback addresses."
    ]));
}

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
        $query = "INSERT INTO email_notifications (enabled, smtp_address, smtp_port, smtp_username, smtp_password, from_email, other_emails, encryption, auth_method, gmail_client_id, gmail_client_secret, gmail_refresh_token, user_id)
                          VALUES (:enabled, :smtpAddress, :smtpPort, :smtpUsername, :smtpPassword, :fromEmail, :otherEmails, :encryption, :authMethod, :gmailClientId, :gmailClientSecret, :gmailRefreshToken, :userId)";
    } else {
        $query = "UPDATE email_notifications
                          SET enabled = :enabled, smtp_address = :smtpAddress, smtp_port = :smtpPort,
                              smtp_username = :smtpUsername, smtp_password = :smtpPassword, from_email = :fromEmail, other_emails = :otherEmails, encryption = :encryption,
                              auth_method = :authMethod, gmail_client_id = :gmailClientId, gmail_client_secret = :gmailClientSecret, gmail_refresh_token = :gmailRefreshToken
                          WHERE user_id = :userId";
    }

    $stmt = $db->prepare($query);
    $stmt->bindValue(':enabled', $enabled, PDO::PARAM_INT);
    $stmt->bindValue(':smtpAddress', $smtpAddress, PDO::PARAM_STR);
    $stmt->bindValue(':smtpPort', $smtpPort !== "" ? (int) $smtpPort : null, $smtpPort !== "" ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':smtpUsername', $smtpUsername, PDO::PARAM_STR);
    $stmt->bindValue(':smtpPassword', $smtpPassword, PDO::PARAM_STR);
    $stmt->bindValue(':fromEmail', $fromEmail, PDO::PARAM_STR);
    $stmt->bindValue(':otherEmails', $otherEmails, PDO::PARAM_STR);
    $stmt->bindValue(':encryption', $encryption, PDO::PARAM_STR);
    $stmt->bindValue(':authMethod', $authMethod, PDO::PARAM_STR);
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