<?php

use App\Auth\AuthService;
use App\Auth\RegistrationService;
use App\Database\Database;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationController;
use App\Repositories\PdoUserRepository;
use App\Session\Session;

require_once __DIR__ . '/vendor/autoload.php';

$session = new Session();
$session->start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
);

$pdo = $database->connect();

// Routing
$route = $_GET['route'] ?? 'login';
$method = $_SERVER['REQUEST_METHOD'];

$userRepository = new PdoUserRepository($pdo);
$registrationService = new RegistrationService($userRepository);
$authService = new AuthService($userRepository, $session);

$registrationController = new RegistrationController (
    $registrationService,
    $session
);

$loginController = new LoginController($authService, $session);

switch ("$method:$route") {
    
    case 'GET:register':

        if($session->get('csrf_token') === null) {
            $token = bin2hex(random_bytes(32));
            $session->put('csrf_token', $token);
        }

        require __DIR__ . '/views/auth/register.php';
        break;

    case 'POST:register':

        $expected = $session->get('csrf_token');
        $received = $_POST['_token'] ?? null;

        if (
            !is_string($expected) ||
            !is_string($received) ||
            !hash_equals($expected, $received)
        ) {
            $page = 'register';

            http_response_code(403);
            require __DIR__ . '/views/errors/403.php';
            exit;
        }

        $registrationController->register();
        break;

    case 'GET:login':
        if($session->get('csrf_token') === null) {
            $token = bin2hex(random_bytes(32));
            $session->put('csrf_token', $token);
        }

        require __DIR__ . '/views/auth/loginpage.php';
        break;

    case 'POST:login':
        $loginController->login();
        break;

    case 'POST:logout':
        $loginController->logout();
        break;

    case 'GET:dashboard':

        $user = $authService->user();

        if ($user === null) {
            header('Location: index.php?route=login', true, 302);
            exit;
        }

        require __DIR__ . '/views/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo '404 - Page not found';
}