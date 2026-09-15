<?php

/**
 * Fetch the subscriptions whose cancellation reminder is still ahead of us.
 *
 * One-time purchases are excluded: they have no recurring commitment to cancel,
 * and the subscription form clears their cancellation date. The cancellation
 * notification cron applies the same rule.
 *
 * @param WallosDatabase $db
 * @param int     $userId
 * @return array
 */
function get_upcoming_cancellations($db, $userId)
{
    $stmt = $db->prepare("SELECT id, logo, logo_text_color, logo_variant, name, price, currency_id, cycle, frequency, cancellation_date
        FROM subscriptions
        WHERE user_id = :userId
          AND inactive = 0
          AND cancellation_date IS NOT NULL
          AND cancellation_date != ''
          AND cancellation_date >= CURRENT_DATE
          AND cycle != 5
        ORDER BY cancellation_date ASC");
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $result = $stmt->execute();

    $subscriptions = [];
    while ($result && ($row = $result->fetchArray(PDO::FETCH_ASSOC))) {
        $subscriptions[] = $row;
    }

    return $subscriptions;
}

/**
 * Total monthly cost, in the user's main currency, of everything currently
 * flagged for cancellation — what the user would stop spending by cancelling.
 *
 * Expects getPricePerMonth() and getPriceConverted() from stats_calculations.php.
 *
 * @param array   $cancellations rows from get_upcoming_cancellations()
 * @param WallosDatabase $db
 * @param int     $userId
 * @return float
 */
function get_upcoming_cancellations_monthly_value($cancellations, $db, $userId)
{
    $total = 0;

    foreach ($cancellations as $subscription) {
        $perMonth = getPricePerMonth($subscription['cycle'], $subscription['frequency'], $subscription['price']);
        $total += getPriceConverted($perMonth, $subscription['currency_id'], $db, $userId);
    }

    return $total;
}
