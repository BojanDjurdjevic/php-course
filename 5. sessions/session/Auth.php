<?php

require_once __DIR__ . '/../../includes/session.php';

include_once __DIR__ . '/../../validation/errors/validator.php';


function isAuth(string $token, string $hashed, array &$errors): bool {

    if(!isset($_SESSION['user_id']) && !isset($_COOKIE['remember_token'])) return false;

    if(!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

        $remember = validateRequiredString($_COOKIE['remember_token'], 'session', $errors);

        if($remember === null) return false;

        // Povlačimo iz baze usera sa ovim tokenom - pretpostavka: $token i $hashed su iz baze
        $sql = "SELECT id, name, email, remember_token from users WHERE remember_token = :remember_token";

        $user = [
            'id' => 15,
            'name' => 'Bojan',
            'email' => 'bojan@test.com',
            'remember_token' => $remember
        ];

        if(!password_verify($remember, $hashed)) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);

            return false;
        } 

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];

    }

    return isset($_SESSION['user_id']);
}

?>