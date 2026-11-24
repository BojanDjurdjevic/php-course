<?php

declare(strict_types=1);

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/flash_helpers.php';

$errors = flashGet('errors', []);
$old = flashGet('old', []);
$message = flashGet('message', '');

$email = is_array($old) && isset($old['email']) && is_string($old['email'])
    ? $old['email']
    : '';

?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module 5 - Sign in</title>
</head>
<body>

    <h1>Prijava</h1>

    <?php if (is_string($message) && $message !== ''): ?>
        <p>
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form action="signin_submit.php" method="POST">

        <div>
            <label for="email">Email</label>

            <input
                type="email"
                name="email"
                id="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
            >

            <?php if (is_array($errors) && isset($errors['email'])): ?>
                <p>
                    <?= htmlspecialchars(
                        (string) $errors['email'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password">Password</label>

            <input
                type="password"
                name="password"
                id="password"
            >

            <?php if (is_array($errors) && isset($errors['password'])): ?>
                <p>
                    <?= htmlspecialchars(
                        (string) $errors['password'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if (is_array($errors) && isset($errors['credentials'])): ?>
            <p>
                <?= htmlspecialchars(
                    (string) $errors['credentials'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </p>
        <?php endif; ?>

        <button type="submit">Prijavi se</button>

    </form>

    <p>
        Test nalog:
        <strong>bojan@example.com</strong> /
        <strong>Secret123!</strong>
    </p>

</body>
</html>
