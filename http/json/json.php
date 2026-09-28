<?php

header('Allow: GET, POST');
header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'error' => 'Method not allowed!',
        'method' => $method
    ]);

    exit();
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if(!str_contains(strtolower($contentType), 'application/json')) {
    http_response_code(415);

    echo json_encode([
        'error' => 'Content-Type must be application/json!'
    ]);

    exit;
}

$raw = file_get_contents('php://input'); 

try {
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch(JsonException $e) {

    http_response_code(400);

    echo json_encode([
        'error' => 'Invalid JSON'
    ]);

    exit;
}

if (!is_array($data)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'JSON object expected!'
    ]);

    exit;
}

$name = $data['name'] ?? null;
$email = $data['email'] ?? null;


if(trim($name) === '' || !is_string($name) || trim($email) === '' || !is_string($email)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'All fields are required!'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'Email must be valid!'
    ]);

    exit;
}


echo json_encode([
    'message' => [
        'name' => trim($name),
        'email' => trim($email)
    ]
]);

exit();
?>