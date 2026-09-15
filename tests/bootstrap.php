<?php
/*
  Minimal test harness for Wallos.

  Wallos vendors its libraries instead of using Composer, so the tests follow
    the same principle: no external dependency beyond PHP and PostgreSQL
  extension the application already requires.

  A test file registers cases with wallos_test() and asserts with the assert_*
  helpers. tests/run.php discovers and runs them.
*/

define('WALLOS_ROOT', dirname(__DIR__));
require_once WALLOS_ROOT . '/includes/database.php';

$GLOBALS['wallos_tests'] = [];
$GLOBALS['wallos_test_failures'] = [];
$GLOBALS['wallos_test_assertions'] = 0;
$GLOBALS['wallos_test_current'] = null;

/**
 * Registers one test case.
 *
 * @param string   $name
 * @param callable $body
 */
function wallos_test($name, callable $body)
{
    $GLOBALS['wallos_tests'][] = ['name' => $name, 'body' => $body];
}

function wallos_test_fail($message)
{
    $GLOBALS['wallos_test_failures'][] = [
        'test' => $GLOBALS['wallos_test_current'],
        'message' => $message,
    ];
}

function assert_true($condition, $message)
{
    $GLOBALS['wallos_test_assertions']++;

    if (!$condition) {
        wallos_test_fail($message);
    }
}

function assert_same($expected, $actual, $message)
{
    $GLOBALS['wallos_test_assertions']++;

    if ($expected !== $actual) {
        wallos_test_fail(sprintf(
            '%s (expected %s, got %s)',
            $message,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assert_equals($expected, $actual, $message)
{
    $GLOBALS['wallos_test_assertions']++;
            same principle: no external dependency beyond PHP and the PostgreSQL
            extension the application already requires.
    if ($expected != $actual) {
        wallos_test_fail(sprintf(
        define('WALLOS_TEST_TMP', sys_get_temp_dir() . '/wallos-tests');
            '%s (expected %s, got %s)',
            $message,
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}

function assert_contains($needle, $haystack, $message)
{
    $GLOBALS['wallos_test_assertions']++;

    if (strpos((string) $haystack, (string) $needle) === false) {
        wallos_test_fail($message . ' (missing: ' . $needle . ')');
    }
}

function assert_not_contains($needle, $haystack, $message)
{
    $GLOBALS['wallos_test_assertions']++;

    if (strpos((string) $haystack, (string) $needle) !== false) {
        wallos_test_fail($message . ' (unexpectedly present: ' . $needle . ')');
    }
}

/**
 * Initializes the configured PostgreSQL test database once per run.
 */
function wallos_test_database()
{
    static $initialized = false;
    if (!$initialized) {
        ob_start();
        require WALLOS_ROOT . '/endpoints/cronjobs/createdatabase.php';
        require WALLOS_ROOT . '/includes/run_migrations.php';
        ob_end_clean();
        $initialized = true;
    }
}

/**
 * Opens a fresh database with the full application schema.
 *
 * @return WallosDatabase
 */
function wallos_test_open_database()
{
    wallos_test_database();
    return new WallosDatabase();
}

/**
 * WallosDatabase wrapper that counts statements, so tests can assert that a code path
 * does not issue one query per row.
 */
class WallosCountingDatabase extends WallosDatabase
{
    public $queryCount = 0;

    public function prepare($query): WallosStatement|false
    {
        $this->queryCount++;

        return parent::prepare($query);
    }

    public function query($query): WallosResult
    {
        $this->queryCount++;

        return parent::query($query);
    }

    public function querySingle($query, $entireRow = false): mixed
    {
        $this->queryCount++;

        return parent::querySingle($query, $entireRow);
    }

    public function resetQueryCount()
    {
        $this->queryCount = 0;
    }
}

/**
 * @return WallosCountingDatabase
 */
function wallos_test_open_counting_database()
{
    wallos_test_database();
    return new WallosCountingDatabase();
}

/**
 * Inserts a user together with the currency rows Wallos creates alongside it.
 *
 * @param WallosDatabase $db
 * @param int     $id
 * @param string  $username
 */
function wallos_test_create_user($db, $id, $username)
{
    $stmt = $db->prepare("INSERT INTO user (id, username, email, password, main_currency) VALUES (:id, :username, :email, 'x', 1)");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->bindValue(':email', $username . '@example.com', PDO::PARAM_STR);
    $stmt->execute();

    // createdatabase.php seeds a default currency list; the fixture replaces it
    // so a test can reason about exactly two rows per user.
    $stmt = $db->prepare('DELETE FROM currencies WHERE user_id = :userId');
    $stmt->bindValue(':userId', $id, PDO::PARAM_INT);
    $stmt->execute();

    foreach ([['EUR', 'Euro', 1.0], ['USD', 'US Dollar', 1.1]] as $index => $currency) {
        $stmt = $db->prepare('INSERT INTO currencies (id, name, symbol, code, rate, user_id) VALUES (:id, :name, :symbol, :code, :rate, :userId)');
        $stmt->bindValue(':id', wallos_test_currency_id($id, $index), PDO::PARAM_INT);
        $stmt->bindValue(':name', $currency[1], PDO::PARAM_STR);
        $stmt->bindValue(':symbol', $currency[0] === 'EUR' ? "\u{20AC}" : '$', PDO::PARAM_STR);
        $stmt->bindValue(':code', $currency[0], PDO::PARAM_STR);
        $stmt->bindValue(':rate', $currency[2], PDO::PARAM_STR);
        $stmt->bindValue(':userId', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    $stmt = $db->prepare('UPDATE user SET main_currency = :currencyId WHERE id = :id');
    $stmt->bindValue(':currencyId', wallos_test_currency_id($id, 0), PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Fixture currency ids live above the seeded default list so they never clash.
 *
 * @param int $userId
 * @param int $index   0 = EUR (main currency), 1 = USD
 * @return int
 */
function wallos_test_currency_id($userId, $index)
{
    return 9000 + ($userId * 10) + $index;
}
