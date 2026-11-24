<?php

declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/flash_helpers.php';
require_once __DIR__ . '/auth_helpers.php';

requireAuth();

$message = flashGet('message', '');

$userId = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module 5 - Protected page</title>
</head>
<body>

    <h1>Protected page</h1>

    <?php if (is_string($message) && $message !== ''): ?>
        <p>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <p>
        Authenticated user ID:
        <?= htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>
    </p>

    <p>
        Session će isteći posle 30 minuta neaktivnosti.
    </p>

    <form action="signout_submit.php" method="POST">
        <button type="submit">Odjavi se</button>
    </form>

</body>
</html>
