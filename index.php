<?php

use App\Auth\RegistrationService;
use App\Controllers\AuthController;
use App\Database\Database;
use App\Enums\UserStatus;
use App\Models\User;
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

$authController = new AuthController(
    $registrationService,
    $session
);

switch ("$method:$route") {
    
    case 'GET:register':
        require __DIR__ . '/views/auth/register.php';
        break;
    case 'POST:register':
        $authController->register();
        break;

    case 'POST:login':
        $authController->login();
        break;

    case 'POST:logout':
        $authController->logout();
        break;
    default:
        http_response_code(404);
        echo '404 - Page not found';
}