<?php

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false, // local HTTP
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();