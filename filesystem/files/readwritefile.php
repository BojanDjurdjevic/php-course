<?php

$path = 'note.txt';

$dir = '/myfiles';

$fileDest = __DIR__ . $dir . DIRECTORY_SEPARATOR . $path;

if(!is_dir($dir) && !mkdir($dir, 0075, true) && !is_dir($dir)) {
    throw new RuntimeException('Could not create a directory.');
} /*

$result = file_put_contents($path, "Hello Bojan");

if($result === false) throw new RuntimeException('Could not write the file');

echo 'Rezultat upisa u fajl: ' . $result . "</br>"; */

if(is_file($path)) {
    $text = file_get_contents($path);

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if($text !== false) {
        foreach($lines as $line) echo $line . "</br>";
    } 
    // echo "Čitanje iz fajla: $text" . "</br>";
    
    print_r($lines);
}



