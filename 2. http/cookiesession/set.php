<?php

setcookie(
    'framework',
    'Laravel',
    [
        'expires' => time() + 8600,

        'path' => '/',

        'secure' => false, // in local

        'httponly' => true,

        'samesite' => 'Lax',
    ]
);

echo 'Cookie response sent';