<?php 

header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'POST') {
    header('Allow: POST');

    http_response_code(405);

    echo json_encode([
        'error' => 'Method not allowed!'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$content = $_SERVER['CONTENT_TYPE'] ?? '';

$contentType = strtolower(
    trim(explode(';', $content)[0])
);

if($contentType !== 'application/json') {
    http_response_code(415);

    echo json_encode([
        'error' => 'Invalid content, expected JSON.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$raw = file_get_contents('php://input');

try {
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(400);

    // opciono: logovati $e->message() 

    echo json_encode([
        'error' => 'Invalid JSON sent.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if(!is_array($data)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'JSON object expected!'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$name = $data['name'] ?? null;
$email = $data['email'] ?? null;

if(!is_string($name) || trim($name) === '') {
    http_response_code(422);

    echo json_encode([
        'error' => 'Name is required and must be string.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$name = trim($name);

if(!is_string($email)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'The email is required and must be valid email address!'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$email = trim($email);

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        'error' => 'The email is required and must be valid email address!'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if(!isset($data['age']) || !is_int($data['age']) || $data['age'] < 18 || $data['age'] > 100) 
{
    http_response_code(422);

    echo json_encode([
        'error' => 'Age must be valid number with range from 18 to 100.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

http_response_code(201);

echo json_encode([
    'data' => [
        'name' => $name,
        'email' => $email,
        'age' => $data['age']
    ]
], JSON_UNESCAPED_UNICODE);

exit;