<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\LetterController;

require dirname(__DIR__) . '/vendor/autoload.php';

// --- Load environment -------------------------------------------------------
$root = dirname(__DIR__);
if (is_file($root . '/.env')) {
    Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

// --- Secure session cookie --------------------------------------------------
$https = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
session_name('OLWDEMO');
session_start();

// Static assets when using the PHP built-in server.
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// --- Routes -----------------------------------------------------------------
$routes = [
    'GET /'                     => fn() => print LetterController::dashboard(),
    'GET /login'                => fn() => print AuthController::showLogin(),
    'POST /login'               => fn() => AuthController::login(),
    'POST /logout'              => fn() => AuthController::logout(),
    'GET /letters'              => fn() => print LetterController::index(),
    'GET /letters/create'       => fn() => print LetterController::create(),
    'POST /letters'             => fn() => LetterController::store(),
    'GET /letters/show'         => fn() => print LetterController::show(),
    'GET /letters/pdf'          => fn() => LetterController::pdf(),
    'POST /letters/submit'      => fn() => LetterController::action('submit'),
    'POST /letters/verify'      => fn() => LetterController::action('verify'),
    'POST /letters/approve'     => fn() => LetterController::action('approve'),
    'POST /letters/reject'      => fn() => LetterController::action('reject'),
];

$key = $method . ' ' . rtrim($uri, '/');
if ($uri === '/') {
    $key = $method . ' /';
}

try {
    if (isset($routes[$key])) {
        $routes[$key]();
    } else {
        http_response_code(404);
        echo view('error', ['title' => 'Not found', 'message' => 'Page not found.']);
    }
} catch (Throwable $e) {
    $debug = env('APP_DEBUG', 'false') === 'true';
    $code = http_response_code();
    if (!is_int($code) || $code < 400) {
        http_response_code(500);
    }
    echo view('error', [
        'title'   => 'Error',
        'message' => $debug ? $e->getMessage() : 'Something went wrong.',
    ]);
}
