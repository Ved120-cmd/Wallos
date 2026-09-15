<?php
require_once 'validate.php';
require_once __DIR__ . '/../../includes/connect_endpoint_crontabs.php';
require_once __DIR__ . '/../../includes/exchange_rate_freshness.php';

require 'settimezone.php';

// Get all user ids

if (php_sapi_name() == 'cli') {
    $date = new DateTime('now');
    echo "\n" . $date->format('Y-m-d') . " " . $date->format('H:i:s') . "<br />\n";
}

$query = "SELECT id, username FROM user";
$stmt = $db->prepare($query);
$usersToUpdateExchange = $stmt->execute();

while ($userToUpdateExchange = $usersToUpdateExchange->fetchArray(PDO::FETCH_ASSOC)) {
    $userId = $userToUpdateExchange['id'];
    echo "For user: " . $userToUpdateExchange['username'] . "<br />";

    // Asked before anything is read or fetched. This job also runs on every
    // container start, so without this a deploy costs one provider request per
    // account, and a free plan's monthly allowance goes on refreshing rates
    // that were already current.
    if (wallos_rates_refreshed_today($db, $userId)) {
        echo "Rates are already current today.<br />";
        continue;
    }

    $query = "SELECT api_key, provider FROM fixer WHERE user_id = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $result = $stmt->execute();

    if ($result) {
        $row = $result->fetchArray(PDO::FETCH_ASSOC);

        if ($row) {
            $apiKey = $row['api_key'];
            $provider = $row['provider'];

            $codes = "";
            $query = "SELECT id, name, symbol, code FROM currencies WHERE user_id = :userId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
                $codes .= $row['code'] . ",";
            }
            $codes = rtrim($codes, ',');
            $query = "SELECT u.main_currency, c.code FROM user u LEFT JOIN currencies c ON u.main_currency = c.id WHERE u.id = :userId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            $row = $result->fetchArray(PDO::FETCH_ASSOC);
            $mainCurrencyCode = $row['code'];
            $mainCurrencyId = $row['main_currency'];

            if ($provider === 1) {
                $api_url = "https://api.apilayer.com/fixer/latest?base=EUR&symbols=" . $codes;
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'apikey: ' . $apiKey,
                    ]
                ]);
                $response = file_get_contents($api_url, false, $context);
            } else {
                $api_url = "http://data.fixer.io/api/latest?access_key=" . $apiKey . "&base=EUR&symbols=" . $codes;
                $response = file_get_contents($api_url);
            }

            $apiData = json_decode($response, true);

            $mainCurrencyToEUR = $apiData['rates'][$mainCurrencyCode];

            if ($apiData !== null && isset($apiData['rates'])) {
                // One user's rates and their refresh date are one unit of work:
                // a failure halfway through would otherwise leave some rows
                // converted against the new base and some against the old one.
                $db->beginTransaction();

                // Every user has their own currency rows, converted against their own
                // main currency, so the write must be scoped to the user being refreshed.
                $updateQuery = "UPDATE currencies SET rate = :rate WHERE code = :code AND user_id = :userId";
                $updateStmt = $db->prepare($updateQuery);
                $updateFailed = false;

                foreach ($apiData['rates'] as $currencyCode => $rate) {
                    if ($currencyCode === $mainCurrencyCode) {
                        $exchangeRate = 1.0;
                    } else {
                        $exchangeRate = $rate / $mainCurrencyToEUR;
                    }

                    $updateStmt->bindValue(':rate', $exchangeRate, PDO::PARAM_STR);
                    $updateStmt->bindValue(':code', $currencyCode, PDO::PARAM_STR);
                    $updateStmt->bindValue(':userId', $userId, PDO::PARAM_INT);
                    $updateResult = $updateStmt->execute();
                    $updateStmt->reset();

                    if (!$updateResult) {
                        echo "Error updating rate for currency: $currencyCode <br />";
                        $updateFailed = true;
                        break;
                    }
                }

                if ($updateFailed) {
                    $db->rollBack();
                    echo "Exchange rates update rolled back for this user.<br />";
                } else {
                    $currentDate = new DateTime();
                    $formattedDate = $currentDate->format('Y-m-d');

                    $deleteQuery = "DELETE FROM last_exchange_update WHERE user_id = :userId";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                    $deleteResult = $deleteStmt->execute();

                    $query = "INSERT INTO last_exchange_update (date, user_id) VALUES (:formattedDate, :userId)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':formattedDate', $formattedDate, PDO::PARAM_STR);
                    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                    $result = $stmt->execute();

                    $db->commit();

                    echo "Rates updated successfully!<br />";
                }
            }
        } else {
            echo "Exchange rates update skipped. No fixer.io api key provided<br />";
            $apiKey = null;
        }
    } else {
        echo "Exchange rates update skipped. No fixer.io api key provided<br />";
        $apiKey = null;
    }
}
$db->close();

?>