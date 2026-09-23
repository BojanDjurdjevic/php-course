<?php 

    $naslov = 'Postani programer';

    $nav = ['Glavna', 'O nama', 'Kontakt'];

    $parafs = array_map(fn($p) => "<a> $p </a>", $nav);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $naslov ?></title>
</head>
<body>
    <h1> <?= $naslov ?> </h1>

    <nav>
        <?php 
        
            foreach($parafs as $p) echo $p;
        ?>
    </nav>
</body>
</html>