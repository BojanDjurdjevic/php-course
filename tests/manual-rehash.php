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

$users = new PdoUserRepository($pdo);

// Testni korisnik
$user = $users->findByEmail('hash@test.com');

if ($user === null) {
    throw new RuntimeException('Test user not found.');
}

var_dump(
    password_needs_rehash(
        $user->passwordHash(),
        PASSWORD_DEFAULT
    )
);

var_dump(
    password_verify(
        'TestPassword123!',
        $user->passwordHash()
    )
);

/*

$id = $user->id();
$version = $user->authVersion();
$originalHash = $user->passwordHash();

// Novi hash poznate TEST lozinke
$newHash = password_hash('TestPassword123!', PASSWORD_DEFAULT);

// TEST 1 - svi uslovi odgovaraju
$result1 = $users->rehashIfUnchanged(
    $id,
    $originalHash,
    $version,
    $newHash
);

$after1 = $users->findById($id);

var_dump([
    'test' => 'Correct hash and version',
    'expected' => true,
    'actual' => $result1,
    'hash_updated' => $after1->passwordHash() === $newHash
]);

// Sada čuvamo trenutno stanje za naredne testove
$currentHash = $after1->passwordHash();

// Hash koji ćemo pokušati da upišemo
$anotherHash = password_hash('AnotherTest123!', PASSWORD_DEFAULT);

// TEST 2 - pogrešan očekivani hash
$result2 = $users->rehashIfUnchanged(
    $id,
    'wrong_expected_hash',
    $version,
    $anotherHash
);

// TEST 3 - pogrešna verzija
$result3 = $users->rehashIfUnchanged(
    $id,
    $currentHash,
    $version + 1,
    $anotherHash
);

// TEST 4 - nepostojeći korisnik
$result4 = $users->rehashIfUnchanged(
    -1,
    $currentHash,
    $version,
    $anotherHash
);

// Ponovo učitavamo bazu
$afterTests = $users->findById($id);

var_dump([
    'wrong_hash' => $result2,
    'wrong_version' => $result3,
    'missing_user' => $result4,
    'hash_unchanged' => $afterTests->passwordHash() === $currentHash
]);

// Zaseban korak:
$oldHash = password_hash(
    'TestPassword123!',
    PASSWORD_BCRYPT,
    ['cost' => 4]
);

$users->updatePasswordHash($id, $oldHash);

var_dump(
    password_needs_rehash($oldHash, PASSWORD_DEFAULT)
); */