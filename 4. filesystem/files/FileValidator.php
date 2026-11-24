<?php

function validateUploadedImage(
    mixed $file, string $field, array &$errors,
    bool $required = true, 
    int $maxSize = 5 * 1024 * 1024,
    int $maxPixels = 25_000_000
): ? array
{
    if ($file === null) {
        if ($required) {
            $errors[$field] = 'The file is required!';
        }
        return null;
    }

    if(!is_array($file)) {
        $errors[$field] = 'The file must be an array type.';

        return null;
    }

    foreach (['tmp_name', 'error', 'size'] as $key) {
        if (!array_key_exists($key, $file)) {
            $errors[$field] = 'There are missing file fields.';
            return null;
        }
    }

    if (!is_int($file['error']) || !is_string($file['tmp_name']) || !is_int($file['size'])) {
        $errors[$field] = 'Invalid upload data.';
        return null;
    }

    $fileError = $file['error'];

    if ($fileError === UPLOAD_ERR_NO_FILE && !$required) {
        return null;
    }

    if($fileError !== UPLOAD_ERR_OK) {
        $errorStr = match($fileError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_PARTIAL => 'The file is partialy uploaded, please try again.',
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION => 'The upload could not be completed.',

            default => 'Unknown upload error.'
        };

        $errors[$field] = $errorStr;

        return null;
    }

    $tmp = $file['tmp_name'];

    if (!is_uploaded_file($tmp)) {
        $errors[$field] = 'Invalid upload.';
        return null;
    }

    $size = filesize($tmp);

    if ($size === false || $size <= 0) {
        $errors[$field] = 'The file is empty or unreadable.';
        return null;
    }

    if ($size > $maxSize) {
        $errors[$field] = 'The file is too large.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file($tmp);

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if ($mime === false || !in_array($mime, $allowedMimes, true)) {
        $errors[$field] = 'Only JPEG, PNG and WebP images are allowed.';
        return null;
    }
    
    
    $imageInfo = getimagesize($tmp);

    if ($imageInfo === false || $imageInfo['mime'] !== $mime) {
        $errors[$field] = 'Invalid image file.';
        return null;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];

    if ($width <= 0 || $height <= 0 || $width * $height > $maxPixels) {
        $errors[$field] = 'The image dimensions are too large.';
        return null;
    }



    return [
        'tmp_path' => $tmp,
        'mime' => $mime,
        'size' => $size,
        'width' => $width,
        'height' => $height,
    ];
}

function normalizeUploadedFiles(mixed $files, string $field, array &$errors): ? array {
    if(!is_array($files)) {
        $errors[$field] = 'Photos must be an array type.';

        return null;
    }

    $keys = ['name', 'type', 'tmp_name', 'error', 'size'];

    foreach ($keys as $key) {
        if (!array_key_exists($key, $files)) {
            $errors[$field] = 'There are missing file fields.';
            return null;
        }

        if (!is_array($files[$key])) {
            $errors[$field] = "The file $key must be an array.";
            return null;
        }
    }

    $indexes = array_keys($files['name']);

    foreach ($keys as $key) {
        if (array_keys($files[$key]) !== $indexes) {
            $errors[$field] = 'The structure of the photo array is corrupted.';
            return null;
        }
    }

    $normalized = [];

    foreach($files['name'] as $index => $name) {

        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index],
            'tmp_name' => $files['tmp_name'][$index],
            'error' => $files['error'][$index],
            'size' => $files['size'][$index]
        ];

    }

    return $normalized;
}

function validateUploadedImages(mixed $files, string $field, array &$errors, int $minFiles = 1, int $maxFiles = 5): ? array {

    $files = normalizeUploadedFiles($files, $field, $errors);

    if($files === null) return null;

    if (count($files) === 1 && ($files[0]['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
        $files = [];
    }

    $count = count($files);

    if($count < $minFiles) {
        $errors[$field] = "At least $minFiles photo is required!";

        return null;
    }

    if($count > $maxFiles) {
        $errors[$field] = "The number of uploaded photos may be up to $maxFiles";

        return null;
    }

    $validated = [];
    $hasErrors = false;

    foreach($files as $index => $file) {
        $valid = validateUploadedImage($file, "$field.$index", $errors, true);

        if ($valid === null) {
            $hasErrors = true;
            continue;
        }

        $validated[] = $valid;
    }

    return $hasErrors ? null : $validated;
}

/*

function normalizeUploadedFiles(mixed $files, string $field, array &$errors): ?array {

    if (!is_array($files)) {
        $errors[$field] = 'Photos must be an array type.';
        return null;
    }

    $keys = ['name', 'type', 'tmp_name', 'error', 'size'];

    foreach ($keys as $key) {
        if (!array_key_exists($key, $files)) {
            $errors[$field] = 'There are missing file fields.';
            return null;
        }

        if (!is_array($files[$key])) {
            $errors[$field] = "The file $key must be an array.";
            return null;
        }
    }

    $indexes = array_keys($files['name']);

    foreach ($keys as $key) {
        if (array_keys($files[$key]) !== $indexes) {
            $errors[$field] = 'The structure of the photo array is corrupted.';
            return null;
        }
    }

    $normalized = [];

    foreach ($indexes as $index) {
        $normalized[] = [
            'name'     => $files['name'][$index],
            'type'     => $files['type'][$index],
            'tmp_name' => $files['tmp_name'][$index],
            'error'    => $files['error'][$index],
            'size'     => $files['size'][$index],
        ];
    }

    return $normalized;
}

function validateUploadedImages(
    mixed $files,
    string $field,
    array &$errors,
    int $minFiles = 1,
    int $maxFiles = 5
): ?array {

    $files = normalizeUploadedFiles($files, $field, $errors);

    if ($files === null) {
        return null;
    }

    // Prazan <input multiple> šalje jedan unos sa UPLOAD_ERR_NO_FILE
    if (count($files) === 1 && ($files[0]['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
        $files = [];
    }

    $count = count($files);

    if ($count < $minFiles) {
        $errors[$field] = $minFiles === 1
            ? 'At least one photo is required.'
            : "At least $minFiles photos are required.";
        return null;
    }

    if ($count > $maxFiles) {
        $errors[$field] = "You may upload a maximum of $maxFiles photos.";
        return null;
    }

    $validated = [];
    $hasErrors = false;

    foreach ($files as $index => $file) {
        $valid = validateUploadedImage($file, "$field.$index", $errors, true);

        if ($valid === null) {
            $hasErrors = true;
            continue;
        }

        $validated[] = $valid;
    }

    return $hasErrors ? null : $validated;
}

*/

/*
    $indexes = [];

    foreach(['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
        if (!array_key_exists($key, $files)) {
            $errors[$field] = 'There are missing file fields.';
            return null;
        }

        if(!is_array($files[$key])) {
            $errors[$field] = "The file $key must be an array.";

            return null;
        }

        if(!in_array($files[$key], $files)) {
            $errors[$field] = 'There are missing file fields.';

            return null;
        }

        $indexes[] = count($files[$key]);
    } 

    if(count(array_unique($indexes)) !== 1) {
        $errors[$field] = 'The structure of photo array is corrupted.';

        return null;
    } */


function processImageToWebp(string $source, string $destination, string $mime, int $maxWidth = 1600, int $quality = 82): bool {

    $image = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($source),
        'image/png'  => imagecreatefrompng($source),
        'image/webp' => imagecreatefromwebp($source),
        default      => false,
    };

    if ($image === false) {
        return false;
    }

    if (!imagepalettetotruecolor($image)) {
        return false;
    }

    imagealphablending($image, false);
    imagesavealpha($image, true);

    if (imagesx($image) > $maxWidth) {
        
        $resized = imagescale($image, $maxWidth, -1, IMG_BICUBIC);

        if ($resized === false) {
            return false;
        }

        $image = $resized;
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    return imagewebp($image, $destination, $quality);
}

function processAndStoreImages(array $validatedImages, int $hotelId, string $storageRoot): array {

    $storageRoot = rtrim($storageRoot, '/\\');

    $hotelDir = $storageRoot . DIRECTORY_SEPARATOR . 'hotels' . DIRECTORY_SEPARATOR . $hotelId;

    if(!is_dir($hotelDir) && !mkdir($hotelDir, 0775, true) && !is_dir($hotelDir)) {
        throw new RuntimeException('Could not make directory.');
    }

    $savedPaths = [];

    $tempPath = null;

    $stored = [];

    try {
        foreach($validatedImages as $image) {
            $name = bin2hex(random_bytes(16)) . '.webp';

            $tempPath = $hotelDir . DIRECTORY_SEPARATOR . $name . 'tmp';

            $finalPath = $hotelDir . DIRECTORY_SEPARATOR . $name;

            if (!processImageToWebp($image['tmp_path'], $tempPath, $image['mime'])) {
                throw new RuntimeException('Could not process the image.');
            }

            if(!rename($tempPath, $finalPath)) {
                throw new RuntimeException('Could not store the image.');
            }

            $tempPath     = null;
            $savedPaths[] = $finalPath;

            $size = filesize($finalPath);

            if($size === false) throw new RuntimeException('Could not read the stored image size.');

            $stored[] = [
                'path' => "hotels/$hotelId/$name",
                'mime' => 'image/webp',
                'size' => $size
            ];
            
        }
    } catch (\Throwable $e) {

        if($tempPath !== null && is_file($tempPath)) {
            unlink($tempPath);
        }

        foreach($savedPaths as $savedPath) {
            if(is_file($savedPath)) {
                unlink($savedPath);
            }
        }

        throw $e;
    }

    

    return $stored;
} 

function saveHotelGallery( PDO $pdo, int $hotelId, array $validatedImages, string $storageRoot): array {

    // 1. Ako ovo padne, processAndStoreImages() je već sam očistio fajlove.
    $storedImages = processAndStoreImages($validatedImages, $hotelId, $storageRoot);

    // 2. Od ovog trenutka fajlovi su naša odgovornost.
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO hotel_images (hotel_id, path, mime, size)
             VALUES (:hotel_id, :path, :mime, :size)'
        );

        foreach ($storedImages as $index => $image) {
            $stmt->execute([
                ':hotel_id' => $hotelId,
                ':path'     => $image['path'],
                ':mime'     => $image['mime'],
                ':size'     => $image['size'],
            ]);

            $storedImages[$index]['id'] = (int) $pdo->lastInsertId();
        }

        $pdo->commit();

    } catch (Throwable $e) {

        // DB cleanup
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (Throwable $rollbackError) {
                // ne smemo da izgubimo originalni izuzetak
            }
        }

        // Filesystem cleanup
        $root = rtrim($storageRoot, '/\\');

        foreach ($storedImages as $image) {
            $file = $root . DIRECTORY_SEPARATOR . $image['path'];

            if (is_file($file)) {
                @unlink($file);
            }
        }

        throw $e;
    }

    return $storedImages;
}