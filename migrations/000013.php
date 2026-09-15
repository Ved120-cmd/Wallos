<?php

/**
 * This migration script updates the avatar field of the user table to use the new avatar path.
 */

$sql = "SELECT avatar FROM user";
$stmt = $db->prepare($sql);
$result = $stmt->execute();
$row = $result->fetchArray(PDO::FETCH_ASSOC);

if ($row) {
    $avatar = $row['avatar'];

    if (strlen($avatar) < 2) {
        $avatarFullPath = "images/avatars/" . $avatar . ".svg";
        $sql = "UPDATE user SET avatar = :avatarFullPath";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':avatarFullPath', $avatarFullPath, PDO::PARAM_STR);
        $stmt->execute();
    }
}

?>