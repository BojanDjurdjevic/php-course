<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domaći</title>
</head>
<body>
    <form action="domaci_rez.php" method="get">

        <input type="number" name="cena" id="">
        
        <select name="proizvod" id="">

            <option value="hrana">Hrana</option>

            <option value="oprema">Oprema za računare</option>

        </select>

        <input type="checkbox" name="porez" id="">

        <button type="submit">Izračunaj cenu</button>
    </form>
</body>
</html>