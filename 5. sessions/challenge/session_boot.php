<?php

declare (strict_types=1);

function startSecureSession(): void {

    if(session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Lax' 
    ]);

    session_start();
    
}

function enforceIdleTimeout(int $maxIdle = 1800): bool {

    if(!isset($_SESSION['user_id'])) return false;

    $now = time();

    $lastActivity = $_SESSION['last_activity'] ?? null;

    if(is_int($lastActivity) &&  $now - $lastActivity > $maxIdle) {
        
        unset($_SESSION['user_id']);
        $_SESSION = [];

        if(ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 3600,
                    'domain' => $params['domain'],
                    'path' => $params['path'],
                    'httponly' => $params['httponly'],
                    'secure' => $params['secure'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );

        }

        session_destroy();

        return false;
        
    }

    $_SESSION['last_activity'] = $now;

    return true;
}

function loginUser(array $user): void {

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id']; // ovde sam zaboravio castovanje (int)

    $_SESSION['last_activity'] = time();

    $_SESSION['_flash']['success'] = "Welcome back " . $user['name'] . " !"; 

}

function logoutUser(): void {

    $_SESSION = [];

    if(ini_get('session.use_cookie')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'httponly' => $params['httponly'],
                'secure' => $params['secure'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();

}

function flashSet(string $key, mixed $value): void {

    $_SESSION['_flash'][$key] = $value;

}

function flashGet(string $key, mixed $default = null): mixed {

    //if($key === '') return $default; // nepotrebno

    if (
        !isset($_SESSION['_flash'])
        || !is_array($_SESSION['_flash'])
        || !array_key_exists($key, $_SESSION['_flash']) // Mnogo bolje od onog gore
    ) {
        return $default;
    }

    $flash = $_SESSION['_flash'][$key];

    unset($_SESSION['_flash'][$key]);

    if(isset($_SESSION['_flash'])  && $_SESSION['_flash'] === []) unset($_SESSION['_flash']);

    return $flash;
}

function requireAuthenticatedUser(int $maxIdle = 1800): int {

    $authenticated = enforceIdleTimeout($maxIdle);

    if($authenticated === false) {
        // Ovaj deo sam zaboravio - fora je što sesija može biti uništena, pa samim tim flash neće raditi:
        if(session_status() !== PHP_SESSION_ACTIVE) startSecureSession();
        //------------------------------

        flashSet('error', 'Please login first.');

        header('Location: /signin_view.php', true, 303);
        exit();
    }
    /*
    $userId = (int) $_SESSION['user_id'];

    return $userId; */

    // Može brže i lepše:

    return (int) $_SESSION['user_id'];
}

?>