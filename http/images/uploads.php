<?php

header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'POST') {
    header('Allow: POST');

    http_response_code(405);

    exit('Error: Unsupported method!');
}

$file = $_FILES['photo'] ?? null;

$maxSize = 3 * 1024 * 1024;

$allowedTypes = [
    'image/jpeg' => 'jpeg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if($file === null) {
    http_response_code(422);

    exit('Error: The photo is required!');
}

if($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(422);

    exit('Error: Upload failed!');
}

if($file['size'] > $maxSize) {
    http_response_code(413);

    exit('Error: The file is too large!');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($file['tmp_name']);

if(!isset($allowedTypes[$mime])) {
    http_response_code(415);

    exit('Error: Unsupported mime type!');
}

$extension = $allowedTypes[$mime];

$fileName = bin2hex(random_bytes(16)) . '.' . $extension;

$user_id = 15;

$uploadDir = __DIR__ . "/uploads/$user_id";

if(!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);

    exit('Error: COuld not create file directory');
}

$relativePath = "uploads/$user_id/$fileName";

$destination = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . $fileName;

if(!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);

    exit('Error: Could not upload the file!');
}

http_response_code(201);

echo json_encode([
    'data' => [
        'path' => $relativePath
    ]
], JSON_UNESCAPED_UNICODE);