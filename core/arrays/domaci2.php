<?php 

    $naslov = 'Postani programer';

    $nav = ['Glavna' => 'home.php', 'O nama' => 'about.php', 'Kontakt' => 'contact.php'];

    

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
        
            foreach($nav as $a => $link) echo "<a href='$link'> $a </a>";
        ?>
    </nav>

    <footer>
        <p> Moj sajt <?= date('Y') ?></p>
    </footer>
</body>
</html>