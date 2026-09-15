<?php

$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage == 'index.php') {
    // Redirect to subscriptions page if no subscriptions exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = :userId");
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
    $result = $stmt->execute();
    $row = $result->fetchArray(PDO::FETCH_NUM);
    $subscriptionCount = $row[0];

    if ($subscriptionCount === 0) {
        header('Location: subscriptions.php');
        exit;
    }
}