<?php

declare(strict_types=1);

const IDLE_TIMEOUT_SECONDS = 30 * 60;

function isAuthenticated(): bool
{
    return isset($_SESSION['user_id'])
        && is_int($_SESSION['user_id'])
        && $_SESSION['user_id'] > 0;
}

function enforceIdleTimeout(int $maxIdleSeconds = IDLE_TIMEOUT_SECONDS): void
{
    if (!isAuthenticated()) {
        return;
    }

    $now = time();
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (
        is_int($lastActivity)
        && ($now - $lastActivity) > $maxIdleSeconds
    ) {
        unset(
            $_SESSION['user_id'],
            $_SESSION['last_activity']
        );

        session_regenerate_id(true);

        flashSet(
            'message',
            'Sesija je istekla zbog 30 minuta neaktivnosti.'
        );

        header('Location: signin_view.php', true, 303);
        exit;
    }

    $_SESSION['last_activity'] = $now;
}

function requireAuth(): void
{
    enforceIdleTimeout();

    if (!isAuthenticated()) {
        flashSet(
            'message',
            'Morate biti prijavljeni da biste otvorili ovu stranicu.'
        );

        header('Location: signin_view.php', true, 303);
        exit;
    }
}
