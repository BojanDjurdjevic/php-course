<?php

use App\Database\Database;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\PdoUserRepository;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$database = new Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
);

$pdo = $database->connect();
/*
$id = 3;

$userRepo = new PdoUserRepository($pdo);

$user = $userRepo->findById($id);

echo $user->email(); */
