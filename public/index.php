<?php
/**
 * SOciaLINK - Front controller (single entry point / router).
 * URL format:  index.php?r=controller/action/param
 */
require dirname(__DIR__) . '/config/config.php';
require APP_PATH . '/core/helpers.php';

spl_autoload_register(function (string $class) {
    foreach (['core', 'models', 'controllers'] as $dir) {
        $file = APP_PATH . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

session_name('socialink_session');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Whitelisted routes: controller => class
$controllers = [
    'auth'    => 'AuthController',
    'feed'    => 'FeedController',
    'post'    => 'PostController',
    'comment' => 'CommentController',
    'like'    => 'LikeController',
    'follow'  => 'FollowController',
    'profile' => 'ProfileController',
    'search'  => 'SearchController',
];

$route = trim((string)($_GET['r'] ?? ''), '/');
if ($route === '') {
    $route = is_logged_in() ? 'feed/index' : 'auth/login';
}
[$name, $action, $param] = array_pad(explode('/', $route, 3), 3, null);
$action = $action ?: 'index';

try {
    if (!isset($controllers[$name]) || !preg_match('/^[a-z][a-zA-Z0-9]*$/', $action)) {
        throw new RuntimeException('route', 404);
    }
    $controller = new $controllers[$name]();
    if (!is_callable([$controller, $action])) {
        throw new RuntimeException('route', 404);
    }
    $controller->$action($param !== null ? rawurldecode($param) : null);
} catch (PDOException $e) {
    error_log('[SOciaLINK] ' . $e->getMessage());
    http_response_code(500);
    error_page(
        'Database not reachable',
        'SOciaLINK could not talk to MySQL. Start MySQL, import <code>sql/social_app.sql</code> and check <code>config/database.php</code>.',
        true
    );
} catch (RuntimeException $e) {
    http_response_code(404);
    error_page('Page not found', 'The page you are looking for does not exist.', false);
} catch (Throwable $e) {
    error_log('[SOciaLINK] ' . $e->getMessage());
    http_response_code(500);
    error_page('Something went wrong', 'An unexpected error occurred. Please go back and try again.', true);
}

function error_page(string $title, string $html, bool $server): void
{
    $home = BASE_URL . '/index.php';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . e($title) . ' · ' . APP_NAME . '</title><link rel="stylesheet" href="' . e(asset('css/style.css')) . '"></head>'
       . '<body class="error-body"><main class="error-card"><div class="error-code">' . ($server ? '500' : '404') . '</div>'
       . '<h1>' . e($title) . '</h1><p>' . $html . '</p><a class="btn-link-primary" href="' . e($home) . '">Back to ' . APP_NAME . '</a></main></body></html>';
}
