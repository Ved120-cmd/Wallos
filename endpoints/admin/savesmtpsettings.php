<?php

require_once '../../includes/connect_endpoint.php';
require_once '../../includes/validate_endpoint_admin.php';
require_once '../../includes/ssrf_helper.php';

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);

if (!is_array($data)) {
    die(json_encode([
        "success" => false,
        "message" => translate('error', $i18n)
    ]));
}

$smtpAddress = $data['smtpaddress'] ?? '';
$smtpPort = $data['smtpport'] ?? '';
$encryption = $data['encryption'] ?? 'tls';
$smtpUsername = $data['smtpusername'] ?? '';
$smtpPassword = $data['smtppassword'] ?? '';
$fromEmail = $data['fromemail'] ?? '';

if (empty($smtpAddress) || empty($smtpPort)) {
    die(json_encode([
        "success" => false,
        "message" => translate('fill_all_fields', $i18n)
    ]));
}

$smtpPortInt = (int) $smtpPort;
if (!validate_smtp_host($smtpAddress, $smtpPortInt, $db)) {
    die(json_encode([
        "success" => false,
        "message" => "Security Error: SMTP host must not target link-local or loopback addresses."
    ]));
}

if ($smtpPortInt < 1 || $smtpPortInt > 65535) {
    die(json_encode([
        "success" => false,
        "message" => translate('fill_all_fields', $i18n)
    ]));
}

$encryption = empty($data['encryption']) ? 'tls' : $data['encryption'];

// Save settings (admin row is always id = 1)
$stmt = $db->prepare(
    'UPDATE admin SET smtp_address = :smtp_address, smtp_port = :smtp_port, encryption = :encryption,
     smtp_username = :smtp_username, smtp_password = :smtp_password, from_email = :from_email WHERE id = 1'
);
if (!$stmt) {
    die(json_encode([
        "success" => false,
        "message" => $db->lastErrorMsg() ?: translate('error', $i18n)
    ]));
}

$stmt->bindValue(':smtp_address', $smtpAddress, PDO::PARAM_STR);
$stmt->bindValue(':smtp_port', $smtpPortInt, PDO::PARAM_INT);
$stmt->bindValue(':encryption', $encryption, PDO::PARAM_STR);
$stmt->bindValue(':smtp_username', $smtpUsername, PDO::PARAM_STR);
$stmt->bindValue(':smtp_password', $smtpPassword, PDO::PARAM_STR);
$stmt->bindValue(':from_email', $fromEmail, PDO::PARAM_STR);
$result = $stmt->execute();

if ($result === false) {
    die(json_encode([
        "success" => false,
        "message" => $db->lastErrorMsg() ?: translate('error', $i18n)
    ]));
}

$adminRowCount = (int) $db->querySingle('SELECT COUNT(*) FROM admin WHERE id = 1');
if ($adminRowCount === 0) {
    die(json_encode([
        "success" => false,
        "message" => translate('error', $i18n)
    ]));
}

die(json_encode([
    "success" => true,
    "message" => translate('success', $i18n)
]));
