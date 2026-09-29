<?php

$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'POST') {
    header('Allow: POST');

    http_response_code(405); // endpoint ne dozvoljava tu HTTP metodu

    exit('Error: The method must be POST');
}

$file = $_FILES['photo'] ?? null;

$name = $_POST['name'] ?? 'Unknown User';

$maxSize = 5 * 1024 * 1024;

$allowedMimes = [
    'image/jpg' => 'jpg',
    'image/jpeg' => 'jpeg',
    'image/png' => 'png',
    'image/webp' => 'webp',
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
    http_response_code(422);

    exit('Error: The photo is too large!');
}

// MIME

$finfo = new finfo(FILEINFO_MIME_TYPE); // proverava koji je mime

$mime = $finfo->file($file['tmp_name']); 

// Da li je poslati mime dozvoljen?

if(!isset($allowedMimes[$mime])) {
    http_response_code(415);

    exit('Error: Unsupported file!');
}

$extension = $allowedMimes[$mime]; //  ?? null - nije potrebno jer je već provereno

// Generišem novo ime fajla:

$fileName = bin2hex(random_bytes(16)) . '.' . $extension;

// Upload folder

$uploadDir = __DIR__ . '/uploads';

// Ako nema foldera -> napravi ga

if(!is_dir($uploadDir)
    && !mkdir($uploadDir, 0755, true)
    && !is_dir($uploadDir)
) {
    http_response_code(500);

    exit('Could not create upload directory.');
}

// Pravimo destinaciju tj path od slike:

$destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

if(!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);

    exit('Could not store file.');
}

http_response_code(201);

echo "User " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . " uploaded: " . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'); 

/*
echo '<pre>';

print_r($file);

echo '</pre>'; */

?>