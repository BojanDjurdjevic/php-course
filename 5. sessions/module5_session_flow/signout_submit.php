<?php

declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: member_area.php', true, 303);
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. Brišemo podatke trenutnog request-a
|--------------------------------------------------------------------------
*/
$_SESSION = [];

/*
|--------------------------------------------------------------------------
| 2. Brišemo session cookie iz browser-a
|--------------------------------------------------------------------------
*/
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| 3. Brišemo server-side session state
|--------------------------------------------------------------------------
*/
session_destroy();

/*
|--------------------------------------------------------------------------
| 4. Nova anonymous session samo da prenese logout flash poruku
|--------------------------------------------------------------------------
|
| setcookie() menja response header, ali ne menja trenutni $_COOKIE.
| Zato eksplicitno uklanjamo stari ID iz trenutnog request-a pre nego
| što otvorimo novu anonymous session.
|
*/
unset($_COOKIE[session_name()]);
session_id('');
session_start();

$_SESSION['_flash']['message'] = 'Uspešno ste se odjavili.';

header('Location: signin_view.php', true, 303);
exit;
