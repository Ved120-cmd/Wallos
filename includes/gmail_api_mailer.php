<?php

/**
 * Sends notification emails through the Gmail API (HTTPS/443) instead of
 * raw SMTP. Added as an alternative to the SMTP path in
 * saveemailnotifications.php / testemailnotifications.php / sendnotifications.php
 * for hosts that block outbound SMTP ports (25/465/587) but allow normal
 * HTTPS egress.
 *
 * Auth is OAuth 2.0: a Google Cloud OAuth client (id + secret) and a
 * long-lived refresh token obtained once via the OAuth consent flow. Each
 * send exchanges the refresh token for a short-lived access token, then
 * calls users.messages.send.
 */
class GmailApiMailerException extends RuntimeException
{
}

function gmail_api_get_access_token(string $clientId, string $clientSecret, string $refreshToken): string
{
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new GmailApiMailerException("Could not reach Google's token endpoint: " . $curlError);
    }

    $data = json_decode($response, true);

    if ($httpCode >= 400 || !isset($data['access_token'])) {
        if (is_array($data) && (isset($data['error_description']) || isset($data['error']))) {
            $message = ($data['error'] ?? '') . (isset($data['error_description']) ? ': ' . $data['error_description'] : '');
        } else {
            // Not the JSON shape Google's token endpoint normally returns -
            // show the raw response so the real cause is visible instead of
            // a generic fallback string.
            $message = 'unexpected response body: ' . substr($response, 0, 500);
        }
        throw new GmailApiMailerException('Google OAuth error (HTTP ' . $httpCode . '): ' . $message);
    }

    return $data['access_token'];
}

/**
 * @param array<int, array{email: string, name: string}> $recipients
 * @param string[] $ccEmails Plain email addresses (no display names)
 */
function gmail_api_build_raw_message(
    string $fromEmail,
    string $fromName,
    array $recipients,
    array $ccEmails,
    string $subject,
    string $body
): string {
    $toHeader = implode(', ', array_map(
        static fn($recipient) => $recipient['name'] !== '' ? "{$recipient['name']} <{$recipient['email']}>" : $recipient['email'],
        $recipients
    ));

    $lines = [];

    // Omit From entirely when no address is known - Gmail fills in the
    // authenticated account's address automatically. Setting it ourselves
    // would need it to exactly match that account anyway (mismatches are
    // rejected), so there's no upside to guessing.
    if ($fromEmail !== '') {
        $lines[] = 'From: ' . ($fromName !== '' ? "{$fromName} <{$fromEmail}>" : $fromEmail);
    }

    $lines[] = 'To: ' . $toHeader;

    if (!empty($ccEmails)) {
        $lines[] = 'Cc: ' . implode(', ', $ccEmails);
    }

    $lines[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
    $lines[] = 'MIME-Version: 1.0';
    $lines[] = 'Content-Type: text/plain; charset=UTF-8';
    $lines[] = 'Content-Transfer-Encoding: base64';
    $lines[] = '';
    $lines[] = chunk_split(base64_encode($body));

    return implode("\r\n", $lines);
}

/**
 * @param array<int, array{email: string, name: string}> $recipients
 * @param string[] $ccEmails
 * @throws GmailApiMailerException
 */
function send_gmail_api_message(
    string $clientId,
    string $clientSecret,
    string $refreshToken,
    string $fromEmail,
    string $fromName,
    array $recipients,
    array $ccEmails,
    string $subject,
    string $body
): void {
    $accessToken = gmail_api_get_access_token($clientId, $clientSecret, $refreshToken);

    // Deliberately not calling users.getProfile to resolve the sender address:
    // the gmail.send scope (the minimum needed to send mail) does not grant
    // permission to read the profile, so that call 403s. Gmail fills in the
    // From address for the authenticated account automatically when the raw
    // message omits it, so leave fromEmail blank and let the API do that.
    $raw = gmail_api_build_raw_message($fromEmail, $fromName, $recipients, $ccEmails, $subject, $body);
    $encodedRaw = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

    $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['raw' => $encodedRaw]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new GmailApiMailerException('Could not reach the Gmail API: ' . $curlError);
    }

    if ($httpCode >= 400) {
        $data = json_decode($response, true);
        $message = $data['error']['message'] ?? $response;
        throw new GmailApiMailerException('Gmail API error: ' . $message);
    }
}

?>
