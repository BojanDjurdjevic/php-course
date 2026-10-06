<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$dirPath  = __DIR__ . DIRECTORY_SEPARATOR . 'myfiles';
$fileDest = $dirPath . DIRECTORY_SEPARATOR . 'note.txt';

if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true) && !is_dir($dirPath)) {
    throw new RuntimeException('Could not create a directory.');
}
/*
$result = file_put_contents($fileDest, 'Hello Bojan');

if ($result === false) {
    throw new RuntimeException('Could not write the file.');
}

echo "Upisano bajtova: $result<br>"; */

$lines = file($fileDest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false) {
    throw new RuntimeException('Could not read the file.');
}

foreach ($lines as $line) {
    echo htmlspecialchars($line) . '<br>';
}

echo '<pre>';
print_r($lines);
echo '</pre>';