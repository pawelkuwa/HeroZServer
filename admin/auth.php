<?php
if (!defined('IN_ENGINE')) exit(http_response_code(404));

/**
 * Check if admin is logged in; redirect to login if not.
 */
function requireAdmin(): void {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * Check if the current admin session is active (without redirect).
 */
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Handle login attempt with rate limiting.
 * Returns ['success' => bool, 'error' => string|null]
 */
function handleLogin(string $username, string $password): array {
    $config = $GLOBALS['admin_config'];
    $rateConf = $config['rate_limit'];

    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $attempts = &$_SESSION[$key];

    // Reset window if expired
    if ((time() - $attempts['first_attempt']) > $rateConf['window']) {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    if ($attempts['count'] >= $rateConf['max_attempts']) {
        $remaining = $rateConf['window'] - (time() - $attempts['first_attempt']);
        return [
            'success' => false,
            'error'   => "Too many login attempts. Please try again in {$remaining} seconds."
        ];
    }

    $attempts['count']++;

    if ($username !== $config['admin']['username']) {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    if (!password_verify($password, $config['admin']['password'])) {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    if (!empty($config['ip_whitelist']) && !in_array($ip, $config['ip_whitelist'])) {
        return ['success' => false, 'error' => 'Access denied from your IP address.'];
    }

    $attempts = ['count' => 0, 'first_attempt' => time()];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_time'] = time();
    session_regenerate_id(true);

    return ['success' => true, 'error' => null];
}

function handleLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}
