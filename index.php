<?php

use App\Auth\AuthService;
use App\Auth\RegistrationService;
use App\Auth\RememberMeService;

use App\Database\Database;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationController;

use App\Repositories\PdoUserRepository;
use App\Repositories\PdoRememberMeTokenRepository;

use App\Session\Session;

require_once __DIR__ . '/vendor/autoload.php';


// =================================================
// 1. SESSION + GLOBAL CSRF
// =================================================

$session = new Session();
$session->start();

if (!is_string($session->get('csrf_token'))) {
    $session->put(
        'csrf_token',
        bin2hex(random_bytes(32))
    );
}


// =================================================
// 2. ENV + DATABASE
// =================================================

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$pdo = $database->connect();


// =================================================
// 3. REPOSITORIES
// =================================================

$userRepository = new PdoUserRepository($pdo);

$rememberRepository = new PdoRememberMeTokenRepository($pdo);


// =================================================
// 4. SERVICES
// =================================================

$registrationService = new RegistrationService(
    $userRepository
);

$authService = new AuthService(
    $userRepository,
    $session
);

$rememberMeService = new RememberMeService(
    $userRepository,
    $rememberRepository,
    $authService
);


// =================================================
// 5. CONTROLLERS
// =================================================

$registrationController = new RegistrationController(
    $registrationService,
    $session
);

$loginController = new LoginController(
    $authService,
    $rememberMeService,
    $session
);


// =================================================
// 6. ROUTING
// =================================================

$route = $_GET['route'] ?? 'login';
$route = is_string($route) ? $route : '';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ("$method:$route") {

    // ---------------------------------------------
    // REGISTER - GET
    // ---------------------------------------------

    case 'GET:register':

        // Ako postoji Remember Me, pokušaj restore.
        if ($authService->guest()) {
            $rememberMeService->restore();
        }

        // Već prijavljeni korisnik ne treba registraciju.
        if ($authService->check()) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }

        require __DIR__ . '/views/auth/register.php';
        break;


    // ---------------------------------------------
    // REGISTER - POST
    // ---------------------------------------------

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


    // ---------------------------------------------
    // LOGIN - GET
    // ---------------------------------------------

    case 'GET:login':
    case 'GET:loginpage':

        // Probaj automatsku prijavu preko Remember Me.
        if ($authService->guest()) {
            $rememberMeService->restore();
        }

        // Ako je korisnik prijavljen, idi na dashboard.
        if ($authService->check()) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }

        require __DIR__ . '/views/auth/loginpage.php';
        break;


    // ---------------------------------------------
    // LOGIN - POST
    // ---------------------------------------------

    case 'POST:login':

        // CSRF + validacija se rade u kontroleru.
        $loginController->login();
        break;


    // ---------------------------------------------
    // LOGOUT - POST
    // ---------------------------------------------

    case 'POST:logout':

        // CSRF + revoke + logout su u kontroleru.
        $loginController->logout();
        break;


    // ---------------------------------------------
    // DASHBOARD - GET
    // ---------------------------------------------

    case 'GET:dashboard':

        // Ako nema aktivne sesije, pokušaj Remember Me.
        if ($authService->guest()) {
            $rememberMeService->restore();
        }

        // VAŽNO: učitaj korisnika NAKON restore().
        $user = $authService->user();

        if ($user === null) {
            header('Location: index.php?route=login', true, 302);
            exit;
        }

        require __DIR__ . '/views/dashboard.php';
        break;


    // ---------------------------------------------
    // 404
    // ---------------------------------------------

    default:

        http_response_code(404);
        echo '404 - Page not found';
        break;
}

/*
use App\Auth\AuthService;
use App\Auth\RegistrationService;
use App\Auth\RememberMeService;
use App\Database\Database;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrationController;
use App\Repositories\PdoRememberMeTokenRepository;
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
$rememberRepository = new PdoRememberMeTokenRepository($pdo);

$registrationService = new RegistrationService($userRepository);
$authService = new AuthService($userRepository, $session);
$rememberMeService = new RememberMeService($userRepository, $rememberRepository, $authService);

$registrationController = new RegistrationController (
    $registrationService,
    $session
);

$loginController = new LoginController($authService, $rememberMeService, $session);

switch ("$method:$route") {
    
    case 'GET:register':

        $user = $authService->user();

        if ($user !== null) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }

        if($session->get('csrf_token') === null) {
            $token = bin2hex(random_bytes(32));
            $session->put('csrf_token', $token);
        }

        require __DIR__ . '/views/auth/register.php';
        break;

    case 'POST:register':

        $user = $authService->user();

        if ($user !== null) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }

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

    case 'GET:loginpage':
        $user = $authService->user();

        if ($user !== null) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }

        if($session->get('csrf_token') === null) {
            $token = bin2hex(random_bytes(32));
            $session->put('csrf_token', $token);
        }

        require __DIR__ . '/views/auth/loginpage.php';
        break;

    case 'POST:login':
        $user = $authService->user();

        if ($user !== null) {
            header('Location: index.php?route=dashboard', true, 302);
            exit;
        }
        $loginController->login();
        break;

    case 'POST:logout':
        $user = $authService->user();

        if ($user === null) {
            header('Location: index.php?route=loginpage', true, 302);
            exit;
        }

        if($session->get('csrf_token') === null) {
            $token = bin2hex(random_bytes(32));
            $session->put('csrf_token', $token);
        }

        $loginController->logout();
        break;

    case 'GET:dashboard':
        /*
        if ($authService->guest()) {
            $rememberMeService->restore();
        } */
/*
        $user = $authService->user();

        if($user === null) $rememberMeService->restore();

        if ($authService->guest()) {
            header('Location: index.php?route=loginpage', true, 302);
            exit;
        }

        require __DIR__ . '/views/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo '404 - Page not found';
} */