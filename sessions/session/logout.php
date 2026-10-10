<?php

require_once __DIR__ . '/../../includes/session.php';

$_SESSION = [];

if(ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

}

session_destroy();

header('Location: index.php', true, 303);
exit();

?>