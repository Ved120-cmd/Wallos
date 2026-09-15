<?php
/*
  Enabling 2FA is one unit of work, or it is nothing.

  The state this exists to prevent is `user.totp_enabled = 1` with no row in
  `totp`. An account in it cannot be logged into by any credential: login.php
  sends the person to totp.php, which then has no secret and no backup codes to
  check against. Neither an authenticator code nor any of the ten backup codes
  they were just shown will ever work.

  Before the fix all three writes were discarded and `success: true` was
  reported unconditionally, so the account that had just been locked out was
  told 2FA was on and handed the codes to prove it.
*/

/**
 * The enrolment endpoint's source.
 *
 * @return string
 */
function totp_enrolment_source()
{
    return file_get_contents(WALLOS_ROOT . '/endpoints/user/enable_totp.php');
}

wallos_test('the enrolment writes are one transaction', function () {
    $source = totp_enrolment_source();

    assert_contains('beginTransaction', $source, 'the enrolment opens a transaction');
    assert_contains('commit', $source, 'and commits it');
    assert_contains('rollBack', $source, 'and rolls back when a write fails');
});

wallos_test('no write in the enrolment is discarded', function () {
    // The defect was not a missing transaction alone. Each of the three writes
    // returned a result nobody read, so a transaction that was never told to
    // roll back would have committed the broken state just as happily.
    $source = totp_enrolment_source();
    $lines = preg_split('/\R/', $source);
    $discarded = [];

    foreach ($lines as $number => $line) {
        // A result is used when it is assigned, compared or returned. A
        // statement on a line of its own is a result thrown away.
        if (preg_match('/^\s*\$stmt->execute\(\)\s*;\s*$/', $line) === 1) {
            $discarded[] = $number + 1;
        }
    }

    assert_same([], $discarded,
        'every execute() in enable_totp.php is read (lines with a discarded one: '
        . implode(', ', $discarded) . ')');
});

wallos_test('a rolled back enrolment leaves the account exactly as it was', function () {
    // The guarantee the fix rests on, checked against the real schema rather
    // than assumed: the rollback has to cover both tables, or the account is
    // left in the state above.
    $db = wallos_test_open_database();
    wallos_test_create_user($db, 1, 'alice');

    $db->beginTransaction();

    $stmt = $db->prepare('DELETE FROM totp WHERE user_id = :userId');
    $stmt->bindValue(':userId', 1, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $db->prepare('INSERT INTO totp (user_id, totp_secret, backup_codes, last_totp_used)
                          VALUES (:userId, :secret, :codes, :step)');
    $stmt->bindValue(':userId', 1, PDO::PARAM_INT);
    $stmt->bindValue(':secret', 'SECRET', PDO::PARAM_STR);
    $stmt->bindValue(':codes', json_encode(['a', 'b']), PDO::PARAM_STR);
    $stmt->bindValue(':step', intdiv(time(), 30), PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $db->prepare('UPDATE user SET totp_enabled = 1 WHERE id = :userId');
    $stmt->bindValue(':userId', 1, PDO::PARAM_INT);
    $stmt->execute();

    // Both halves are in place inside the transaction.
    assert_same(1, (int) $db->querySingle('SELECT COUNT(*) FROM totp WHERE user_id = 1'),
        'the enrolment row exists before the rollback');
    assert_same(1, (int) $db->querySingle('SELECT totp_enabled FROM user WHERE id = 1'),
        'and so does the flag');

    $db->rollBack();

    assert_same(0, (int) $db->querySingle('SELECT COUNT(*) FROM totp WHERE user_id = 1'),
        'the enrolment row is gone again');
    assert_same(0, (int) $db->querySingle('SELECT totp_enabled FROM user WHERE id = 1'),
        'and the flag with it, neither half survives alone');

    $db->close();
});
