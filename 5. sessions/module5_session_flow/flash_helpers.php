<?php

declare(strict_types=1);

function flashSet(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flashGet(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;

    unset($_SESSION['_flash'][$key]);

    if (isset($_SESSION['_flash']) && $_SESSION['_flash'] === []) {
        unset($_SESSION['_flash']);
    }

    return $value;
}
