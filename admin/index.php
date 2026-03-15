<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    handleLogout();
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

if ($page === 'login') {
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_verify()) {
            $error = 'Invalid security token. Please try again.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $result = handleLogin($username, $password);

            if ($result['success']) {
                header('Location: index.php');
                exit;
            }
            $error = $result['error'];
        }
    }

    if (isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }

    include __DIR__ . '/views/login.php';
    exit;
}

requireAdmin();

// Whitelist of valid pages
$validPages = [
    'dashboard', 'users', 'characters', 'guilds',
    'items', 'messages', 'email', 'vouchers', 'config'
];

if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

$controllerMap = [
    'dashboard'  => 'DashboardController',
    'users'      => 'UsersController',
    'characters' => 'CharactersController',
    'guilds'     => 'GuildsController',
    'items'      => 'ItemsController',
    'messages'   => 'MessagesController',
    'email'      => 'EmailController',
    'vouchers'   => 'VouchersController',
    'config'     => 'ConfigController',
];

$controllerName = $controllerMap[$page] ?? 'DashboardController';
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

$pageData = [];
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controllerClass = "Admin\\{$controllerName}";
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        if (method_exists($controller, $action)) {
            $pageData = $controller->$action() ?? [];
        } elseif (method_exists($controller, 'index')) {
            $pageData = $controller->index() ?? [];
        }
    }
}

$currentPage = $page;

extract($pageData);

// Determine the view file (controller may set $viewFile via pageData)
if (empty($viewFile) || !file_exists($viewFile)) {
    $viewFile = __DIR__ . '/views/' . $currentPage . '.php';
    if (!file_exists($viewFile)) {
        $viewFile = __DIR__ . '/views/dashboard.php';
        $currentPage = 'dashboard';
    }
}

include __DIR__ . '/views/layout.php';
