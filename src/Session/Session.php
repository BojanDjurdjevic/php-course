<?php

namespace App\Session;

final class Session {

    public function start(): void {

        if(session_status() === PHP_SESSION_ACTIVE) return;

        ini_set('session.use.only.cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => false,
            'samesite' => 'Lax'
        ]);

        session_start();

    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed 
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    } 

    public function forget(string $key): void {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void {
        session_regenerate_id(true);
    }

    public function unsetCookie(string $name): void {
        setcookie($name, '', -3600, '/', '', false, true);
    }

    public function flashSet(string $key, mixed $value): void {

        $_SESSION['_flash'][$key] = $value;

    }

    public function flashGet(string $key, mixed $default = null): mixed {

        if (
            !isset($_SESSION['_flash'])
            || !is_array($_SESSION['_flash'])
            || !array_key_exists($key, $_SESSION['_flash']) 
        ) {
            return $default;
        }

        $flash = $_SESSION['_flash'][$key];

        unset($_SESSION['_flash'][$key]);

        if(isset($_SESSION['_flash'])  && $_SESSION['_flash'] === []) unset($_SESSION['_flash']);

        return $flash;
    }

    public function destroy(): void {

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        $params = session_get_cookie_params();

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite']
            ]);
        }
        
        session_destroy();
    }

}