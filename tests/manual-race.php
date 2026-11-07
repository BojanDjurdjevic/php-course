<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Repositories\PdoUserRepository;

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();

$database = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$pdo = $database->connect();
$pdo2 = $database->connect();

$usersA = new PdoUserRepository($pdo);
$usersB = new PdoUserRepository($pdo2);

// Testni korisnici
$user = $usersA->findByEmail('hash@test.com');
$userB = $usersB->findByEmail('hash@test.com');

if ($user === null) {
    throw new RuntimeException('Test user not found.');
}

$version = $user->authVersion();
$originalHash = $user->passwordHash();

$newHash = password_hash('NewPassword!123', PASSWORD_DEFAULT);

if (!password_verify('TestPassword123!', $originalHash)) {
    throw new RuntimeException('Test user does not have the expected old password.');
}

$pdo2->beginTransaction();

try {
    $usersB->updatePasswordHash($userB->id(), $newHash);

    $usersB->incrementAuthVersion($userB->id());

    $pdo2->commit();
} catch (\Throwable $e) {
    if($pdo2->inTransaction()) $pdo2->rollBack();

    throw $e;
}

$rehash = password_hash('TestPassword123!', PASSWORD_DEFAULT);

$casResult = $usersA->rehashIfUnchanged(
    $user->id(),
    $originalHash,
    $version,
    $rehash
);

// Ponovo učitavamo trenutno stanje iz baze
$freshUser = $usersA->findById($user->id());

if ($freshUser === null) {
    throw new RuntimeException('User disappeared during test.');
}

var_dump([
    'cas_rejected' => $casResult === false,

    'new_password_valid' => password_verify(
        'NewPassword!123',
        $freshUser->passwordHash()
    ),

    'old_password_invalid' => !password_verify(
        'TestPassword123!',
        $freshUser->passwordHash()
    ),

    'version_incremented' =>
        $freshUser->authVersion() === $version + 1
]);