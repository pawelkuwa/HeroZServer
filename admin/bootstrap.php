<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

define('IN_ENGINE', true);
define('BASE_DIR', realpath(__DIR__ . '/..'));
define('SERVER_DIR', BASE_DIR . '/server');
define('CACHE_DIR', SERVER_DIR . '/cache');
define('ADMIN_DIR', __DIR__);

// Load server autoloader, functions, field utils
require_once(SERVER_DIR . '/src/Utils/functions.php');
require_once(SERVER_DIR . '/src/Utils/field.php');
require_once(SERVER_DIR . '/src/Utils/autoloader.php');

\Srv\Config::__init();
\Srv\DB::__init();

$GLOBALS['admin_config'] = include(ADMIN_DIR . '/config.php');

$sessConf = $GLOBALS['admin_config']['session'];
session_name($sessConf['name']);
session_start();

/**
 * Execute raw SQL and return all rows
 */
function db_query(string $sql, array $params = []): array {
    $stmt = \Srv\DB::sql($sql, !empty($params) ? $params : false);
    return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
}

/**
 * Execute raw SQL and return single value
 */
function db_value(string $sql, array $params = [], $default = 0) {
    $rows = db_query($sql, $params);
    return !empty($rows) ? reset($rows[0]) : $default;
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    $conf = $GLOBALS['admin_config']['csrf'];
    if (
        empty($_SESSION[$conf['token_name']]) ||
        empty($_SESSION[$conf['token_name'] . '_time']) ||
        (time() - $_SESSION[$conf['token_name'] . '_time']) > $conf['token_ttl']
    ) {
        $_SESSION[$conf['token_name']] = bin2hex(random_bytes(32));
        $_SESSION[$conf['token_name'] . '_time'] = time();
    }
    return $_SESSION[$conf['token_name']];
}

/**
 * Verify CSRF token from POST or GET
 */
function csrf_verify(): bool {
    $conf = $GLOBALS['admin_config']['csrf'];
    $token = $_POST[$conf['token_name']] ?? $_GET[$conf['token_name']] ?? '';
    return hash_equals($_SESSION[$conf['token_name']] ?? '', $token);
}

/**
 * Generate a hidden input field with CSRF token
 */
function csrf_field(): string {
    $conf = $GLOBALS['admin_config']['csrf'];
    return '<input type="hidden" name="' . $conf['token_name'] . '" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Escape output
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
